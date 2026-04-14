<?php
/**
 * Sessão persistente (mínimo 7 dias): cookie de sessão + tempo de vida no servidor.
 * Sem isso, o cookie PHPSESSION costuma ser "de sessão" (some ao fechar o navegador)
 * e o GC do PHP pode apagar dados da sessão em ~24 minutos.
 */
if (session_status() !== PHP_SESSION_NONE) {
    return;
}

$lifetimeSeconds = 60 * 60 * 24 * 7; // 7 dias
$secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
$host = (string) ($_SERVER['HTTP_HOST'] ?? '');
$hostNoPort = preg_replace('/:\d+$/', '', $host) ?? $host;
$domain = null;
if ($hostNoPort !== '' && $hostNoPort !== 'localhost' && !filter_var($hostNoPort, FILTER_VALIDATE_IP)) {
    $domain = '.' . ltrim($hostNoPort, '.');
}

ini_set('session.gc_maxlifetime', (string) $lifetimeSeconds);
// Evita que o coletor apague sessões válidas em ambientes com baixo tráfego
ini_set('session.gc_probability', '1');
ini_set('session.gc_divisor', '100');
/*
 * PHP 7+ (padrão On): com lazy_write, se $_SESSION não mudar entre requisições o arquivo
 * da sessão pode não ser regravado — o mtime fica antigo e o GC apaga a sessão mesmo com
 * o usuário navegando. Isso derruba o login antes dos 7 dias e impede o cookie persistente
 * de “salvar” a tempo. Desligar força gravação a cada request e renova o ciclo de vida.
 */
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
    // assinatura antiga: lifetime, path, domain, secure, httponly
    session_set_cookie_params($lifetimeSeconds, '/', (string) ($domain ?? ''), $secure, true);
}

session_start();
