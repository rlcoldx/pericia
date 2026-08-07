<?php

namespace Agencia\Close\Services\Login;

use Agencia\Close\Conn\Read;
use Agencia\Close\Models\Login;
use Agencia\Close\Models\User\User;

class Logon
{
    public function loginByEmail($email, $password): bool
    {
        $login = new Login();
        $result = $login->getUserByEmailAndPassword($email, $password);
        if (($result->getResult()) && ($result->getResult()[0]['tipo'] != '4')) {
            $this->actionsAfterFoundUser($result);

            return true;
        }

        return false;
    }

    public function loginByOnlyEmail(string $email): bool
    {
        $login = new Login();
        $result = $login->getUserByEmail($email);
        if ($result->getResult()) {
            $this->actionsAfterFoundUser($result);

            return true;
        }

        return false;
    }

    public function loginByCookie(): bool
    {
        if (!isset($_COOKIE['CookieLoginEmail'], $_COOKIE['CookieLoginHash'])) {
            return false;
        }

        $login = new Login();
        $email = (string) $_COOKIE['CookieLoginEmail'];
        $cookieRaw = (string) $_COOKIE['CookieLoginHash'];

        $cookieHashed = hash('sha256', $cookieRaw);
        $result = $login->getUserByEmailAndCookie($email, $cookieHashed);

        if (!$result->getResult()) {
            $result = $login->getUserByEmailAndCookie($email, $cookieRaw);
        }

        if (!$result->getResult()) {
            return false;
        }

        $user = $result->getResult()[0];
        $this->saveInSession($user);

        // Renova expiração do cookie legado (não rotaciona o token — evita derrubar outros PCs)
        (new User())->saveCookie($user['email'], $cookieRaw);

        return true;
    }

    private function loginCookie($idUser, $email): string
    {
        $loginCookie = new LoginCookie();

        return $loginCookie->createCookie($idUser, $email);
    }

    private function saveInSession(array $login): void
    {
        $loginSession = new LoginSession();
        $loginSession->loginUser($login);
    }

    private function actionsAfterFoundUser(Read $result): void
    {
        $loginResult = $result->getResult()[0];

        // Cookie legado (backup)
        $token = $this->loginCookie($loginResult['id'], $loginResult['email']);
        (new User())->saveCookie($loginResult['email'], $token);

        // Cookie permanente (fonte da verdade para "nunca deslogar")
        PersistentLoginService::issueForUser((int) $loginResult['id']);

        $this->saveInSession($loginResult);
        PersistentLoginService::renewPhpSessionCookie();
    }
}
