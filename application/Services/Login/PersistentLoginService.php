<?php

namespace Agencia\Close\Services\Login;

use Agencia\Close\Conn\Read;
use Agencia\Close\Models\User\User;
use PDOException;

/**
 * Login permanente: cookie opaco no navegador + hash SHA-256 em `usuarios.loginHash`
 * (vários hashes separados por vírgula = vários PCs/dispositivos).
 *
 * Só é revogado no logout explícito deste dispositivo.
 */
class PersistentLoginService
{
    public const COOKIE_NAME = 'PericiaLoginPersist';

    /** ~10 anos — praticamente permanente até logout */
    private const COOKIE_LIFETIME = 315360000;

    public static function cookieOptions(?int $expires = null): array
    {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $hostNoPort = preg_replace('/:\d+$/', '', $host) ?? $host;
        $domain = null;
        if ($hostNoPort !== '' && $hostNoPort !== 'localhost' && !filter_var($hostNoPort, FILTER_VALIDATE_IP)) {
            $domain = '.' . ltrim($hostNoPort, '.');
        }

        return [
            'expires' => $expires ?? (time() + self::COOKIE_LIFETIME),
            'path' => '/',
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    /**
     * Garante coluna loginHash (idempotente) para o login permanente funcionar sem depender
     * de alguém abrir o painel de migrations.
     */
    public static function ensureSchema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $read = new Read();
            $read->FullRead("SHOW COLUMNS FROM `usuarios` LIKE 'loginHash'");
            if ($read->getResult()) {
                return;
            }

            // Usa Update/FullUpdate path via PDO do Read: executa ALTER direto
            $conn = (new class extends \Agencia\Close\Conn\Conn {
                public function pdo()
                {
                    return $this->getConn();
                }
            })->pdo();

            $conn->exec(
                "ALTER TABLE `usuarios`
                 ADD COLUMN `loginHash` TEXT NULL DEFAULT NULL
                 COMMENT 'Hashes SHA-256 de tokens persistentes (vírgula)'"
            );
        } catch (PDOException $e) {
            // coluna já existe / sem permissão
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Emite cookie permanente para o dispositivo atual (não invalida outros PCs).
     */
    public static function issueForUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        self::ensureSchema();

        $rawToken = bin2hex(random_bytes(32));
        $hashHex = hash('sha256', $rawToken);

        $userModel = new User();
        $saved = $userModel->appendLoginHash($userId, $hashHex);
        if (!$saved) {
            // Última tentativa: se a coluna acabou de ser criada, tenta de novo
            self::ensureSchema();
            $saved = $userModel->appendLoginHash($userId, $hashHex);
        }
        if (!$saved) {
            return;
        }

        self::setPersistCookie($rawToken);
        $_COOKIE[self::COOKIE_NAME] = $rawToken;
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
        $read = (new User())->findUserByLoginHash($hashHex);
        $row = $read->getResult()[0] ?? null;
        if (!$row || (string) ($row['tipo'] ?? '') === '4') {
            return false;
        }

        $loginSession->loginUser($row);
        self::setPersistCookie($raw);
        self::renewPhpSessionCookie();

        return true;
    }

    /**
     * Renova cookies de sessão + persistência enquanto o usuário está logado.
     * Se estiver logado sem cookie permanente, emite um (migração de sessões antigas).
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

            return;
        }

        $userId = (int) $loginSession->getUserId();
        if ($userId > 0) {
            self::issueForUser($userId);
        }
    }

    public static function clearCookie(): void
    {
        $opts = self::cookieOptions(time() - 3600);
        setcookie(self::COOKIE_NAME, '', $opts);
        // Também limpa sem domain (caso o cookie tenha sido gravado sem domain)
        setcookie(self::COOKIE_NAME, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        unset($_COOKIE[self::COOKIE_NAME]);
    }

    public static function revokeCurrentDevice(int $userId): void
    {
        $raw = self::rawTokenFromRequest();
        if ($raw === null || $userId <= 0) {
            return;
        }
        $hashHex = hash('sha256', $raw);
        (new User())->removeLoginHash($userId, $hashHex);
    }

    public static function lifetimeSeconds(): int
    {
        return self::COOKIE_LIFETIME;
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
        setcookie(self::COOKIE_NAME, $rawToken, self::cookieOptions());
    }

    /**
     * Renova o cookie PHPSESSID com a mesma longevidade do login permanente.
     */
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

        $opts = self::cookieOptions();
        setcookie($name, $id, $opts);
        // Marca atividade para o GC não considerar a sessão “morta”
        $_SESSION['_pericia_last_touch'] = time();
    }
}
