<?php

namespace Agencia\Close\Services\Login;

use Agencia\Close\Models\User\User;

class LoginCookie
{
    public function createCookie($userId, $email): string
    {
        $time = microtime();
        $cookieKey = md5($time);

        $user = new User();
        $user->saveUserCookie($userId, $email, $cookieKey);
        return $cookieKey;
    }
}