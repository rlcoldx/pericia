<?php
/**
 * Sessão longa (~10 anos) + cookies alinhados ao login permanente.
 * A sessão PHP pode ser limpa pelo hosting; o cookie PericiaLoginPersist restaura o login.
 */
if (session_status() !== PHP_SESSION_NONE) {
    return;
}

$lifetimeSeconds = 315360000; // ~10 anos

$https = false;
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $https = true;
} elseif (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
    $https = true;
} elseif (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
    $https = true;
}

$host = (string) ($_SERVER['HTTP_HOST'] ?? '');
$hostNoPort = preg_replace('/:\d+$/', '', $host) ?? $host;
$hostNoPort = strtolower(trim($hostNoPort));
$domain = null;
if ($hostNoPort !== '' && $hostNoPort !== 'localhost' && !filter_var($hostNoPort, FILTER_VALIDATE_IP)) {
    if (str_starts_with($hostNoPort, 'www.')) {
        $hostNoPort = substr($hostNoPort, 4);
    }
    if ($hostNoPort !== '' && $hostNoPort !== 'localhost') {
        $domain = '.' . ltrim($hostNoPort, '.');
    }
}

ini_set('session.gc_maxlifetime', (string) $lifetimeSeconds);
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '1000');
ini_set('session.cookie_lifetime', (string) $lifetimeSeconds);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if ($https) {
    ini_set('session.cookie_secure', '1');
}

if (PHP_VERSION_ID >= 70000) {
    ini_set('session.lazy_write', '0');
}

$cookieParams = [
    'lifetime' => $lifetimeSeconds,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
];
if ($domain !== null) {
    $cookieParams['domain'] = $domain;
}

if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params($cookieParams);
} else {
    session_set_cookie_params($lifetimeSeconds, '/', (string) ($domain ?? ''), $https, true);
}

session_name('PERICIASESSID');
session_start();
