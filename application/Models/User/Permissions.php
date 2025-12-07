<?php

namespace Agencia\Close\Models\User;

use Agencia\Close\Conn\Read;
use Agencia\Close\Models\Model;

class Permissions extends Model
{
    public function getPermissions(string $user): Read
    {
        $read = new Read();
        $read->FullRead('SELECT permissoes.nome FROM usuario_permissoes
        INNER JOIN usuarios ON usuarios.id = usuario_permissoes.usuario_id
        INNER JOIN permissoes ON usuario_permissoes.permissao_id = permissoes.id
        WHERE usuario_permissoes.usuario_id = :id', "id={$user}");
        return $read;
    }
}