<?php

namespace Agencia\Close\Middleware\Login;

use Agencia\Close\Middleware\Middleware;
use Agencia\Close\Services\Login\Logon;
use Agencia\Close\Services\Login\LoginSession;
use Agencia\Close\Services\Login\PersistentLoginService;

class LoginCheckMiddleware extends Middleware
{
    public function run()
    {
        $loginSession = new LoginSession();

        // 1) Sessão PHP viva
        if (!$loginSession->userIsLogged()) {
            // 2) Cookie permanente (multi-dispositivo)
            PersistentLoginService::tryRestoreSession();
        }

        // 3) Fallback legado CookieLoginEmail/Hash
        if (!$loginSession->userIsLogged()) {
            (new Logon())->loginByCookie();
            if ($loginSession->userIsLogged()) {
                // Migra sessão legada para cookie permanente
                PersistentLoginService::issueForUser((int) $loginSession->getUserId());
            }
        }

        // 4) Enquanto logado: renova cookies (nunca “expira sozinho”)
        if ($loginSession->userIsLogged()) {
            PersistentLoginService::touchWhileLoggedIn();

            return;
        }

        $path = $this->getCurrentUrl();
        if (strpos($path, 'login') === false) {
            $isAjax = (
                (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                    && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_ACCEPT']) && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json'))
            );

            if ($isAjax) {
                http_response_code(401);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Sessão expirada. Faça login novamente e tente salvar de novo.',
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '';
            $loginUrl = $host !== ''
                ? ($scheme . '://' . $host . '/login')
                : (DOMAIN . '/login');
            header('Location: ' . $loginUrl);
            exit;
        }
    }

    protected function getCurrentUrl(): string
    {
        return parse_url(
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            PHP_URL_PATH
        ) ?: '';
    }
}
