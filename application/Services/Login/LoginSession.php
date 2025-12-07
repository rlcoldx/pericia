<?php

namespace Agencia\Close\Services\Login;

class LoginSession
{
    public function loginUser(array $login)
    {
        $_SESSION = [
            'pericia_perfil_id' => $login['id'],
            'pericia_perfil_empresa' => $login['empresa'],
            'pericia_perfil_tipo' => $login['tipo'],
            'pericia_perfil_cargo' => $login['cargo'],
            'pericia_perfil_slug' => $login['slug'],
            'pericia_perfil_nome' => $login['nome'],
            'pericia_perfil_email' => $login['email']
        ];
    }

    public function userIsLogged(): bool
    {
        if (isset($_SESSION['pericia_perfil_id'])){
            return true;
        }
        return false;
    }

    public function getUserId() {
        return $_SESSION['pericia_perfil_id'];
    }
}