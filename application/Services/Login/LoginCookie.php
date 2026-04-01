<?php

namespace Agencia\Close\Services\Login;

use Agencia\Close\Models\User\User;

class LoginCookie
{
    public function createCookie($userId, $email): string
    {
        // Token forte e imprevisível para "manter conectado"
        $cookieKey = bin2hex(random_bytes(32)); // 64 chars

        $user = new User();
        // Salva no banco apenas o hash do token (não o token em si)
        $user->saveUserCookie($userId, $email, hash('sha256', $cookieKey));
        return $cookieKey;
    }
}