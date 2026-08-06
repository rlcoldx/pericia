<?php

namespace Agencia\Close\Services\Login;

class LoginSession
{
    public function loginUser(array $login): void
    {
        // Não zera $_SESSION inteiro — preserva outros dados e só define o perfil.
        $_SESSION['pericia_perfil_id'] = $login['id'];
        $_SESSION['pericia_perfil_empresa'] = $login['empresa'];
        $_SESSION['pericia_perfil_tipo'] = $login['tipo'];
        $_SESSION['pericia_perfil_cargo'] = $login['cargo'] ?? null;
        $_SESSION['pericia_perfil_slug'] = $login['slug'] ?? null;
        $_SESSION['pericia_perfil_nome'] = $login['nome'];
        $_SESSION['pericia_perfil_email'] = $login['email'];
        $_SESSION['_pericia_last_touch'] = time();
    }

    public function userIsLogged(): bool
    {
        return isset($_SESSION['pericia_perfil_id']) && (int) $_SESSION['pericia_perfil_id'] > 0;
    }

    public function getUserId()
    {
        return $_SESSION['pericia_perfil_id'] ?? null;
    }
}
