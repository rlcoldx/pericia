<?php

namespace Agencia\Close\Middleware\Login;

use Agencia\Close\Middleware\Middleware;
use Agencia\Close\Services\Login\LoginSession;
use Agencia\Close\Services\Login\Logon;

class LoginMiddleware extends Middleware
{

    public function run()
    {
        $loginSession = new LoginSession();
        if (!$loginSession->userIsLogged()) {
            $logon = new Logon();
            $logon->loginByCookie();
        }
    }
}