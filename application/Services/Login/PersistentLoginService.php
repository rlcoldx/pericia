<?php

namespace Agencia\Close\Services\Login;

use Agencia\Close\Models\User\User;

/**
 * Login persistente: um cookie com token opaco; no banco, SHA-256 do token em `usuarios.loginHash`
 * (vários hashes separados por vírgula para vários PCs).
 */
class PersistentLoginService
{
    public const COOKIE_NAME = 'PericiaLoginPersist';

    /** @var int Tempo de vida do cookie (1 ano) */
    private const COOKIE_LIFETIME = 3600 * 24 * 365;

    public static function cookieOptions(): array
    {
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
        $hostNoPort = preg_replace('/:\d+$/', '', $host) ?? $host;
        $domain = null;
        if ($hostNoPort !== '' && $hostNoPort !== 'localhost' && !filter_var($hostNoPort, FILTER_VALIDATE_IP)) {
            $domain = '.' . ltrim($hostNoPort, '.');
        }

        return [
            'expires' => time() + self::COOKIE_LIFETIME,
            'path' => '/',
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ];
    }

    /**
     * Gera token, grava hash em `loginHash` e envia cookie único.
     */
    public static function issueForUser(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }
        $rawToken = bin2hex(random_bytes(32));
        $hashHex = hash('sha256', $rawToken);

        $userModel = new User();
        if (!$userModel->appendLoginHash($userId, $hashHex)) {
            return;
        }

        setcookie(self::COOKIE_NAME, $rawToken, self::cookieOptions());
    }

    /**
     * Restaura sessão a partir do cookie, se o hash existir em `loginHash`.
     */
    public static function tryRestoreSession(): bool
    {
        $loginSession = new LoginSession();
        if ($loginSession->userIsLogged()) {
            return true;
        }
        $raw = $_COOKIE[self::COOKIE_NAME] ?? null;
        if ($raw === null || $raw === '') {
            return false;
        }
        if (is_array($raw)) {
            $raw = (string) ($raw[0] ?? '');
        }
        $raw = trim((string) $raw);
        if ($raw === '' || strlen($raw) < 32) {
            return false;
        }

        $hashHex = hash('sha256', $raw);
        $read = (new User())->findUserByLoginHash($hashHex);
        $row = $read->getResult()[0] ?? null;
        if (!$row || (string) ($row['tipo'] ?? '') === '4') {
            return false;
        }

        $loginSession->loginUser($row);
        // Renova expiração do cookie no browser
        setcookie(self::COOKIE_NAME, $raw, self::cookieOptions());

        return true;
    }

    public static function clearCookie(): void
    {
        $opts = self::cookieOptions();
        $opts['expires'] = time() - 3600;
        setcookie(self::COOKIE_NAME, '', $opts);
    }

    public static function revokeCurrentDevice(int $userId): void
    {
        $raw = $_COOKIE[self::COOKIE_NAME] ?? null;
        if ($raw === null || $raw === '' || $userId <= 0) {
            return;
        }
        if (is_array($raw)) {
            $raw = (string) ($raw[0] ?? '');
        }
        $raw = trim((string) $raw);
        if ($raw === '') {
            return;
        }
        $hashHex = hash('sha256', $raw);
        (new User())->removeLoginHash($userId, $hashHex);
    }
}
