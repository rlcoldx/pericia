<?php

namespace Agencia\Close\Models\User;

use Agencia\Close\Conn\Conn;
use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Helpers\User\Identification;
use Agencia\Close\Helpers\User\UserIdentification;
use Agencia\Close\Models\Model;

class User extends Model
{
    private string $table = 'usuarios';

    public function getUserByID(string $user): Read
    {
        $read = new Read();
        $read->ExeRead($this->table, 'WHERE id = :id', "id={$user}");
        return $read;
    }

    public function emailExist(string $email): Read
    {
        $read = new Read();
        $read->ExeRead($this->table, 'WHERE email = :email', "email={$email}");
        return $read;
    }

    public function saveUserCookie($idUser, $email, $cookieHash)
    {
        $this->saveDatabase($cookieHash, $idUser);
        // O cookie enviado ao browser deve conter o token "raw" (o hash fica no banco).
        // Para manter compatibilidade com a assinatura atual, este método recebe $cookieHash
        // já como o valor que deve ir ao banco. O valor do cookie será definido externamente.
    }

    public function saveDatabase($cookieHash, $idUser): void
    {
        $data = ['cookie_key' => $cookieHash];
        $update = new Update();
        $update->ExeUpdate($this->table, $data, 'WHERE id = :idUser', "idUser={$idUser}");
    }

    public function saveCookie($email, $cookieValue): void
    {
        // 7 dias
        $expire = time() + 3600 * 24 * 7;
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        setcookie('CookieLoginEmail', (string) $email, [
            'expires' => $expire,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        setcookie('CookieLoginHash', (string) $cookieValue, [
            'expires' => $expire,
            'path' => '/',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }


    public function saveUser(string $name, Identification $identification, string $sector, string $password)
    {
        $secondIdentification = new Identification();
        if ($identification->getType() !== 'email'){
            $userIdentification = new UserIdentification();
            $secondIdentification = $userIdentification->getFakeEmail();
        }

        $data = [
            'nome' => $name,
            'user_setor' => $sector,
            'senha' => sha1($password),
        ];

        $data = array_merge($data, [$identification->getColumn() => $identification->getIdentification()]);

        if($secondIdentification->getColumn() !== ''){
            $data = array_merge($data, [$secondIdentification->getColumn() => $secondIdentification->getIdentification()]);
        }

        $create = new Create();
        $create->ExeCreate($this->table, $data);
        return $create->getResult();
    }

    public function saveUserByOauth(string $name, string $email, array $arrayWithFieldAndId)
    {
        $data = [
            'nome' => $name,
            'email' => $email
        ];

        $data = array_merge($data, $arrayWithFieldAndId);

        $create = new Create();
        $create->ExeCreate($this->table, $data);
        return $create->getResult();
    }

    public function changePasswordByEmail(string $email, string $password): bool
    {
        $data = [
            'senha' => sha1($password),
        ];
        $this->update = new Update();
        $this->update->ExeUpdate($this->table, $data, "WHERE email = :email", "email={$email}");
        return $this->update->getResult();
    }
}