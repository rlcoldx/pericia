<?php

namespace Agencia\Close\Services\Login;

use Agencia\Close\Models\User\User;
use PDO;
use Throwable;

/**
 * Login permanente no computador (mínimo ~10 anos).
 *
 * Fluxo:
 * 1) Cookie httponly `PericiaLoginPersist` no navegador
 * 2) Hash SHA-256 em `user_persistent_tokens` (e espelho em `usuarios.loginHash`)
 * 3) A cada request: se a sessão PHP morreu, restaura pelo cookie
 *
 * Só sai no Logout explícito deste dispositivo.
 */
class PersistentLoginService
{
    public const COOKIE_NAME = 'PericiaLoginPersist';

    /** ~10 anos */
    private const COOKIE_LIFETIME = 315360000;

    public static function lifetimeSeconds(): int
    {
        return self::COOKIE_LIFETIME;
    }

    public static function isHttps(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        if ($forwarded === 'https') {
            return true;
        }

        // NÃO usar DOMAIN=https em localhost/HTTP — cookie Secure impede o login local.
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        $isLocal = (
            $host === 'localhost'
            || $host === '127.0.0.1'
            || $host === '::1'
            || filter_var($host, FILTER_VALIDATE_IP) !== false
        );
        if (
            !$isLocal
            && defined('DOMAIN')
            && is_string(DOMAIN)
            && str_starts_with(DOMAIN, 'https://')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Domínio do cookie: remove www e usa .dominio.tld para www e sem-www.
     * localhost / IP → host-only (sem atributo Domain).
     */
    public static function cookieDomain(): ?string
    {
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;
        $host = strtolower(trim($host));

        if ($host === '' || $host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        // www.fast4.com.br → fast4.com.br → .fast4.com.br
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        if ($host === '' || $host === 'localhost') {
            return null;
        }

        return '.' . ltrim($host, '.');
    }

    public static function cookieOptions(?int $expires = null): array
    {
        $opts = [
            'expires' => $expires ?? (time() + self::COOKIE_LIFETIME),
            'path' => '/',
            'secure' => self::isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        $domain = self::cookieDomain();
        if ($domain !== null && $domain !== '') {
            $opts['domain'] = $domain;
        }

        return $opts;
    }

    public static function ensureSchema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $pdo = self::pdo();

            $pdo->exec(
                "CREATE TABLE IF NOT EXISTS `user_persistent_tokens` (
                    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    `user_id` INT NOT NULL,
                    `token_hash` CHAR(64) NOT NULL,
                    `created_at` DATETIME NOT NULL,
                    `last_used_at` DATETIME NOT NULL,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uniq_token_hash` (`token_hash`),
                    KEY `idx_user_id` (`user_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );

            $check = $pdo->query("SHOW COLUMNS FROM `usuarios` LIKE 'loginHash'");
            if ($check && !$check->fetch(PDO::FETCH_ASSOC)) {
                $pdo->exec(
                    "ALTER TABLE `usuarios`
                     ADD COLUMN `loginHash` TEXT NULL DEFAULT NULL
                     COMMENT 'Hashes SHA-256 de tokens persistentes (vírgula)'"
                );
            }
        } catch (Throwable $e) {
            // segue; restore tenta o que existir
        }
    }

    /**
     * Emite cookie permanente para ESTE dispositivo (não derruba outros PCs).
     */
    public static function issueForUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        self::ensureSchema();

        $rawToken = bin2hex(random_bytes(32));
        $hashHex = hash('sha256', $rawToken);
        $now = date('Y-m-d H:i:s');

        $saved = self::insertToken($userId, $hashHex, $now);

        // Espelho legado em loginHash (melhor esforço)
        try {
            (new User())->appendLoginHash($userId, $hashHex);
        } catch (Throwable $e) {
        }

        if (!$saved) {
            // Última tentativa: só loginHash
            try {
                $saved = (new User())->appendLoginHash($userId, $hashHex);
            } catch (Throwable $e) {
                $saved = false;
            }
        }

        if (!$saved) {
            // Ainda grava o cookie: restore tentará loginHash / legado
            // mas sem registro no banco não restaura — tenta insert de novo
            self::ensureSchema();
            $saved = self::insertToken($userId, $hashHex, $now);
        }

        if (!$saved) {
            return;
        }

        self::setPersistCookie($rawToken);
        $_COOKIE[self::COOKIE_NAME] = $rawToken;
        self::renewPhpSessionCookie();
    }

    /**
     * Restaura sessão a partir do cookie permanente.
     */
    public static function tryRestoreSession(): bool
    {
        $loginSession = new LoginSession();
        if ($loginSession->userIsLogged()) {
            return true;
        }

        self::ensureSchema();

        $raw = self::rawTokenFromRequest();
        if ($raw === null) {
            return false;
        }

        $hashHex = hash('sha256', $raw);
        $user = self::findUserByTokenHash($hashHex);
        if ($user === null) {
            return false;
        }

        if ((string) ($user['tipo'] ?? '') === '4') {
            return false;
        }

        $loginSession->loginUser($user);
        self::touchToken($hashHex);
        self::setPersistCookie($raw);
        self::renewPhpSessionCookie();

        return true;
    }

    /**
     * Renova cookies enquanto logado. Se não houver cookie permanente, emite um.
     */
    public static function touchWhileLoggedIn(): void
    {
        $loginSession = new LoginSession();
        if (!$loginSession->userIsLogged()) {
            return;
        }

        self::renewPhpSessionCookie();

        $raw = self::rawTokenFromRequest();
        if ($raw !== null) {
            self::setPersistCookie($raw);
            self::touchToken(hash('sha256', $raw));

            return;
        }

        $userId = (int) $loginSession->getUserId();
        if ($userId > 0) {
            self::issueForUser($userId);
        }
    }

    public static function clearCookie(): void
    {
        $expired = time() - 3600;
        $secure = self::isHttps();

        // Com domínio canônico
        $withDomain = self::cookieOptions($expired);
        setcookie(self::COOKIE_NAME, '', $withDomain);

        // Host-only (caso antigo sem Domain)
        setcookie(self::COOKIE_NAME, '', [
            'expires' => $expired,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Variantes www / sem-www
        $host = strtolower(preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? '')) ?? '');
        if ($host !== '' && $host !== 'localhost' && !filter_var($host, FILTER_VALIDATE_IP)) {
            $base = str_starts_with($host, 'www.') ? substr($host, 4) : $host;
            foreach (['.' . $base, $base, '.www.' . $base, 'www.' . $base] as $d) {
                setcookie(self::COOKIE_NAME, '', [
                    'expires' => $expired,
                    'path' => '/',
                    'domain' => $d,
                    'secure' => $secure,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
        }

        unset($_COOKIE[self::COOKIE_NAME]);
    }

    public static function revokeCurrentDevice(int $userId): void
    {
        $raw = self::rawTokenFromRequest();
        if ($raw === null || $userId <= 0) {
            return;
        }

        $hashHex = hash('sha256', $raw);

        try {
            $stmt = self::pdo()->prepare('DELETE FROM `user_persistent_tokens` WHERE `token_hash` = :h LIMIT 1');
            $stmt->execute(['h' => $hashHex]);
        } catch (Throwable $e) {
        }

        try {
            (new User())->removeLoginHash($userId, $hashHex);
        } catch (Throwable $e) {
        }
    }

    public static function renewPhpSessionCookie(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $name = session_name();
        $id = session_id();
        if ($name === '' || $id === '') {
            return;
        }

        setcookie($name, $id, self::cookieOptions());
        $_SESSION['_pericia_last_touch'] = time();
    }

    private static function rawTokenFromRequest(): ?string
    {
        $raw = $_COOKIE[self::COOKIE_NAME] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_array($raw)) {
            $raw = (string) ($raw[0] ?? '');
        }
        $raw = trim((string) $raw);
        if ($raw === '' || strlen($raw) < 32) {
            return null;
        }

        return $raw;
    }

    private static function setPersistCookie(string $rawToken): void
    {
        // Grava com domínio canônico (www + não-www)
        setcookie(self::COOKIE_NAME, $rawToken, self::cookieOptions());
    }

    private static function pdo(): PDO
    {
        return (new class extends \Agencia\Close\Conn\Conn {
            public function pdo(): PDO
            {
                return $this->getConn();
            }
        })->pdo();
    }

    private static function insertToken(int $userId, string $hashHex, string $now): bool
    {
        try {
            $pdo = self::pdo();
            $stmt = $pdo->prepare(
                'INSERT INTO `user_persistent_tokens` (`user_id`, `token_hash`, `created_at`, `last_used_at`)
                 VALUES (:uid, :h, :c, :u)'
            );
            $ok = $stmt->execute([
                'uid' => $userId,
                'h' => $hashHex,
                'c' => $now,
                'u' => $now,
            ]);

            // Limpa tokens antigos do mesmo usuário (mantém os 40 mais recentes)
            if ($ok) {
                try {
                    $pdo->exec(
                        "DELETE FROM `user_persistent_tokens`
                         WHERE `user_id` = " . (int) $userId . "
                           AND `id` NOT IN (
                             SELECT `id` FROM (
                               SELECT `id` FROM `user_persistent_tokens`
                               WHERE `user_id` = " . (int) $userId . "
                               ORDER BY `last_used_at` DESC
                               LIMIT 40
                             ) AS keep_rows
                           )"
                    );
                } catch (Throwable $e) {
                }
            }

            return (bool) $ok;
        } catch (Throwable $e) {
            return false;
        }
    }

    private static function touchToken(string $hashHex): void
    {
        try {
            $stmt = self::pdo()->prepare(
                'UPDATE `user_persistent_tokens` SET `last_used_at` = :u WHERE `token_hash` = :h LIMIT 1'
            );
            $stmt->execute(['u' => date('Y-m-d H:i:s'), 'h' => $hashHex]);
        } catch (Throwable $e) {
        }
    }

    private static function findUserByTokenHash(string $hashHex): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/i', $hashHex)) {
            return null;
        }

        // 1) Tabela dedicada
        try {
            $stmt = self::pdo()->prepare(
                'SELECT u.*
                 FROM `user_persistent_tokens` t
                 INNER JOIN `usuarios` u ON u.id = t.user_id
                 WHERE t.token_hash = :h AND u.tipo <> \'4\'
                 LIMIT 1'
            );
            $stmt->execute(['h' => $hashHex]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return $row;
            }
        } catch (Throwable $e) {
        }

        // 2) Fallback loginHash (versões antigas)
        try {
            $read = (new User())->findUserByLoginHash($hashHex);
            $row = $read->getResult()[0] ?? null;
            if ($row) {
                return $row;
            }
        } catch (Throwable $e) {
        }

        return null;
    }
}
