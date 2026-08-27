<?php

namespace Agencia\Close\Controllers\Login;

use Agencia\Close\Controllers\Controller;
use Agencia\Close\Helpers\User\EmailUser;
use Agencia\Close\Helpers\User\Identification;
use Agencia\Close\Models\Log\RegisterLog;
use Agencia\Close\Models\User\User;
use Agencia\Close\Services\Login\Logon;
use Agencia\Close\Services\Login\PersistentLoginService;

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

        $userId = $_SESSION['pericia_perfil_id'] ?? null;

        // Revoga apenas ESTE dispositivo no login permanente
        if ($userId) {
            try {
                PersistentLoginService::revokeCurrentDevice((int) $userId);
            } catch (\Throwable $e) {
                // não bloqueia logout
            }
        }
        PersistentLoginService::clearCookie();

        // Limpa cookies legados
        $opts = PersistentLoginService::cookieOptions(time() - 3600);
        setcookie('CookieLoginEmail', '', $opts);
        setcookie('CookieLoginHash', '', $opts);
        setcookie('CookieLoginEmail', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        setcookie('CookieLoginHash', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Não zera cookie_key no banco: isso derrubaria login legado em outros dispositivos.
        // Neste PC os cookies já foram apagados; o hash permanente deste dispositivo já foi removido.

        // Destrói sessão PHP de forma completa
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $paramsCookie = session_get_cookie_params();
            $clearOpts = [
                'expires' => time() - 3600,
                'path' => $paramsCookie['path'] ?? '/',
                'secure' => (bool) ($paramsCookie['secure'] ?? false),
                'httponly' => (bool) ($paramsCookie['httponly'] ?? true),
                'samesite' => $paramsCookie['samesite'] ?? 'Lax',
            ];
            if (!empty($paramsCookie['domain'])) {
                $clearOpts['domain'] = $paramsCookie['domain'];
            }
            setcookie(session_name(), '', $clearOpts);
            // Limpa também nomes antigos/novos de sessão
            setcookie('PHPSESSID', '', $clearOpts);
            setcookie('PERICIASESSID', '', $clearOpts);
            session_destroy();
        }

        $loginUrl = defined('DOMAIN') ? (rtrim((string) DOMAIN, '/') . '/login') : '/login';
        header('Location: ' . $loginUrl);
        exit;
    }

    private function createUser(string $name, string $email, array $arrayIdentification): void
    {
        $identification = new Identification();
        $identification->setIdentification($email);
        $identification->setType('email');

        if (!EmailUser::verifyIfEmailExist($identification)) {
            $user = new User();
            $user->saveUserByOauth($name, $email, $arrayIdentification);
        }
    }
}
