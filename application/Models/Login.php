<?php

namespace Agencia\Close\Models;

use Agencia\Close\Conn\Conn;
use Agencia\Close\Conn\Read;

class Login extends Model
{
    public function getUserByEmailAndPassword($email, $password): Read
    {
        $password = sha1($password);
        $this->read = new Read();
        $this->read->FullRead("SELECT * FROM usuarios WHERE email = :email AND (senha = :password OR senha_padrao = :password) AND tipo <> '4'", "email={$email}&password={$password}");
        return $this->read;
    }

    public function getUserByEmail($email): Read
    {
        $this->read = new Read();
        $this->read->FullRead("SELECT * FROM usuarios WHERE email = :email AND tipo <> '4'", "email={$email}");
        return $this->read;
    }

    public function getUserByEmailAndCookie($email, $cookie): Read
    {
        $this->read = new Read();
        $this->read->FullRead("SELECT * FROM usuarios WHERE email = :email AND cookie_key = :cookie AND tipo <> '4'", "email={$email}&cookie={$cookie}");
        return $this->read;
    }
}