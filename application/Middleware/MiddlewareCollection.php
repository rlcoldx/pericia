<?php

namespace Agencia\Close\Middleware;

use Agencia\Close\Middleware\Login\LoginCheckMiddleware;
use Agencia\Close\Middleware\Login\UserPermissionMiddleware;

class MiddlewareCollection
{
    private array $middlewares = [];

    public function default()
    {
        // Restauração de sessão (cookie `loginHash` + legado) centralizada no LoginCheckMiddleware
        $this->push(new LoginCheckMiddleware());
        $this->push(new UserPermissionMiddleware());
    }

    public function run()
    {
        foreach ($this->middlewares as $middleware) {
            $middleware->run();
        }
    }

    private function push(Middleware $middleware)
    {
        array_push($this->middlewares, $middleware);
    }
}