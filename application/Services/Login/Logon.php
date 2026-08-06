<?php

namespace Agencia\Close\Services\Login;

use Agencia\Close\Conn\Read;
use Agencia\Close\Models\Log\LoginLog;
use Agencia\Close\Models\Login;
use Agencia\Close\Services\Login\LoginSession;
use Agencia\Close\Services\Login\LoginCookie;
use Agencia\Close\Services\Login\PersistentLoginService;

class Logon
{
    public function loginByEmail($email, $password): bool
    {
        $login = new Login();
        $result = $login->getUserByEmailAndPassword($email, $password);
        if (($result->getResult()) AND ($result->getResult()[0]['tipo'] != '4') ) {
            $this->actionsAfterFoundUser($result);
            return true;
        } else {
            return false;
        }
    }

    public function loginByOnlyEmail(string $email): bool
    {
        $login = new Login();
        $result = $login->getUserByEmail($email);
        if ($result->getResult()) {
            $this->actionsAfterFoundUser($result);
            return true;
        } else {
            return false;
        }
    }

    public function loginByCookie(): bool
    {
        if (isset($_COOKIE['CookieLoginEmail'], $_COOKIE['CookieLoginHash'])) {
            $login = new Login();
            $email = (string) $_COOKIE['CookieLoginEmail'];
            $cookieRaw = (string) $_COOKIE['CookieLoginHash'];

            $cookieHashed = hash('sha256', $cookieRaw);
            $result = $login->getUserByEmailAndCookie($email, $cookieHashed);

            if (!$result->getResult()) {
                $result = $login->getUserByEmailAndCookie($email, $cookieRaw);
            }
            if ($result->getResult()) {
                $user = $result->getResult()[0];
                $this->actionAfterFoundUser($user);

                // NÃO rotaciona o token legado (isso derrubava outros dispositivos).
                // Só renova a expiração do cookie no navegador.
                (new \Agencia\Close\Models\User\User())->saveCookie($user['email'], $cookieRaw);

                return true;
            }
        }
        return false;
    }

    private function actionAfterFoundUser($login)
    {
        $this->saveInSession($login);
    }

    private function loginCookie($idUser, $email): string
    {
        $loginCookie = new LoginCookie();
        return $loginCookie->createCookie($idUser, $email);
    }

    private function saveInSession(array $login)
    {
        $loginSession = new LoginSession();
        $loginSession->loginUser($login);
    }

    // private function saveLog($id, $idCompany)
    // {
    //     $loginLog = new LoginLog();
    //     $loginLog->save($id, $idCompany);
    // }

    private function actionsAfterFoundUser(Read $result): void
    {
        $loginResult = $result->getResult()[0];
        $token = $this->loginCookie($loginResult['id'], $loginResult['email']);
        (new \Agencia\Close\Models\User\User())->saveCookie($loginResult['email'], $token);
        PersistentLoginService::issueForUser((int) $loginResult['id']);
        //$this->saveLog($loginResult['id'], $company);
        $this->actionAfterFoundUser($loginResult);
    }
}