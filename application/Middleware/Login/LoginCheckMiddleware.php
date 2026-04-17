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
        if (!$loginSession->userIsLogged()) {
            PersistentLoginService::tryRestoreSession();
        }
        if (!$loginSession->userIsLogged()) {
            (new Logon())->loginByCookie();
        }
        if (!$loginSession->userIsLogged() && strpos($this->getCurrentUrl(), 'login') === false) {
            header('Location: ' . DOMAIN . '/login');
            exit;
        }
    }

    protected function getCurrentUrl(): string
    {
        return parse_url((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]", PHP_URL_PATH);
    }

}