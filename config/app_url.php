<?php
/**
 * Helpers de URL da aplicação (localhost vs produção).
 */

if (!function_exists('pericia_request_is_https')) {
    function pericia_request_is_https(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        if (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443) {
            return true;
        }
        if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https') {
            return true;
        }

        return false;
    }
}

if (!function_exists('pericia_request_host')) {
    function pericia_request_host(): string
    {
        $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        $host = preg_replace('/:\d+$/', '', $host) ?? $host;

        return $host;
    }
}

if (!function_exists('pericia_is_local_host')) {
    function pericia_is_local_host(): bool
    {
        $host = pericia_request_host();

        return $host === 'localhost'
            || $host === '127.0.0.1'
            || $host === '::1'
            || filter_var($host, FILTER_VALIDATE_IP) !== false
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local');
    }
}

if (!function_exists('pericia_detect_base_url')) {
    /**
     * Ex.: http://localhost/pericia  |  https://fast4.com.br
     */
    function pericia_detect_base_url(): string
    {
        $scheme = pericia_request_is_https() ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        $dir = rtrim(dirname($script), '/');
        if ($dir === '/' || $dir === '\\' || $dir === '.' || $dir === '') {
            $basePath = '';
        } else {
            $basePath = $dir;
        }

        return $scheme . '://' . $host . $basePath;
    }
}
