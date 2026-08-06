<?php
/**
 * Sessão praticamente permanente (alinhada ao cookie PericiaLoginPersist).
 * Só some de fato no logout explícito; o middleware restaura via cookie se o PHP limpar a sessão.
 */
if (session_status() !== PHP_SESSION_NONE) {
    return;
}

$lifetimeSeconds = 315360000; // ~10 anos
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$host = (string) ($_SERVER['HTTP_HOST'] ?? '');
$hostNoPort = preg_replace('/:\d+$/', '', $host) ?? $host;
$domain = null;
if ($hostNoPort !== '' && $hostNoPort !== 'localhost' && !filter_var($hostNoPort, FILTER_VALIDATE_IP)) {
    $domain = '.' . ltrim($hostNoPort, '.');
}

ini_set('session.gc_maxlifetime', (string) $lifetimeSeconds);
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '1000');
ini_set('session.cookie_lifetime', (string) $lifetimeSeconds);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

if (PHP_VERSION_ID >= 70000) {
    ini_set('session.lazy_write', '0');
}

if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'lifetime' => $lifetimeSeconds,
        'path' => '/',
        'domain' => $domain,
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
} else {
    session_set_cookie_params($lifetimeSeconds, '/', (string) ($domain ?? ''), $secure, true);
}

session_start();
