<?php
/**
 * Canônico: sempre sem www (ex.: www.fast4.com.br → fast4.com.br).
 * Deve rodar ANTES de sessão, cookies e rotas.
 */
$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$host = preg_replace('/:\d+$/', '', $host) ?? $host;

if ($host !== '' && str_starts_with($host, 'www.')) {
    $canonical = substr($host, 4);
    if ($canonical !== '') {
        $https = false;
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            $https = true;
        } elseif (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            $https = true;
        } elseif (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
            $https = true;
        } elseif (defined('DOMAIN') && is_string(DOMAIN) && str_starts_with(DOMAIN, 'https://')) {
            $https = true;
        } else {
            // Produção do sistema é HTTPS
            $https = true;
        }

        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $target = ($https ? 'https' : 'http') . '://' . $canonical . $uri;
        header('Location: ' . $target, true, 301);
        exit;
    }
}
