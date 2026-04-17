<?php

namespace Agencia\Close\Middleware\Login;

use Agencia\Close\Middleware\Middleware;

class LoginMiddleware extends Middleware
{

    public function run()
    {
        // Mantido por compatibilidade: fluxo de login por cookie foi movido para LoginCheckMiddleware.
    }
}