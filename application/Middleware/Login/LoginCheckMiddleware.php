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

        // 1) Sessão PHP viva? Se não, restaura pelo cookie permanente.
        if (!$loginSession->userIsLogged()) {
            PersistentLoginService::tryRestoreSession();
        }

        // 2) Fallback legado CookieLoginEmail + CookieLoginHash
        if (!$loginSession->userIsLogged()) {
            try {
                (new Logon())->loginByCookie();
            } catch (\Throwable $e) {
            }
            if ($loginSession->userIsLogged()) {
                PersistentLoginService::issueForUser((int) $loginSession->getUserId());
            }
        }

        // 3) Logado: renova cookies (sessão + permanente) em TODA request
        if ($loginSession->userIsLogged()) {
            PersistentLoginService::touchWhileLoggedIn();

            return;
        }

        $path = $this->getCurrentUrl();
        if (strpos($path, 'login') !== false) {
            return;
        }

        $isAjax = (
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && str_contains((string) $_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '') === 'empty')
            || (isset($_SERVER['REQUEST_URI']) && str_contains((string) $_SERVER['REQUEST_URI'], '/save'))
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

        $loginUrl = defined('DOMAIN') ? (rtrim(DOMAIN, '/') . '/login') : '/login';
        header('Location: ' . $loginUrl);
        exit;
    }

    protected function getCurrentUrl(): string
    {
        return parse_url(
            (PersistentLoginService::isHttps() ? 'https' : 'http')
            . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'),
            PHP_URL_PATH
        ) ?: '';
    }
}
