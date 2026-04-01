<?php

namespace Agencia\Close\Controllers\Login;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Helpers\User\EmailUser;
use Agencia\Close\Helpers\User\Identification;
use Agencia\Close\Models\Log\RegisterLog;
use Agencia\Close\Models\User\User;
use Agencia\Close\Services\Login\Logon;

class LoginController extends Controller
{
    public function index(array $params)
    {
        $this->setParams($params);
        $this->render('pages/login/login.twig', []);
    }

    public function recover(array $params)
    {
        $this->setParams($params);
        $this->render('pages/login/recover.twig', []);
    }

    public function sign(array $params)
    {
        $this->setParams($params);
        $logon = new Logon();
        if ($logon->loginByEmail($this->params['email'], $this->params['password'])) {
            echo '1';
        } else {
            echo '0';
        } 
    }

    public function logout(array $params)
    {
        $this->setParams($params);

        $email = $_SESSION['pericia_perfil_email'] ?? null;
        $userId = $_SESSION['pericia_perfil_id'] ?? null;

        session_destroy();

        // Limpa cookie no browser (mesmos parâmetros de path/samesite)
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        setcookie('CookieLoginEmail', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        setcookie('CookieLoginHash', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Revoga no banco (remove cookie_key) se tiver id
        if ($userId) {
            try {
                (new User())->saveDatabase(null, (int) $userId);
            } catch (\Throwable $e) {
                // não bloqueia logout
            }
        }

        $this->router->redirect("login");
    }

    private function createUser(string $name, string $email, array $arrayIdentification): void
    {
        $identification = new Identification();
        $identification->setIdentification($email);
        $identification->setType('email');

        if (!EmailUser::verifyIfEmailExist($identification)) {
            $user = new User();
            $createdId = $user->saveUserByOauth($name, $email, $arrayIdentification);
        }
    }
}