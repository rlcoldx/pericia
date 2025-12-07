<?php

namespace Agencia\Close\Models\User;

use Agencia\Close\Conn\Read;
use Agencia\Close\Models\Model;

class UserPermissions extends Model
{
    /**
     * Busca usuários que possuem uma permissão específica
     */
    public function getUsuariosPorPermissao(string $permissao, int $empresa): Read
    {
        $read = new Read();
        $read->FullRead(
            "SELECT DISTINCT u.id, u.nome, u.email, u.tipo
             FROM usuarios u
             INNER JOIN usuario_permissoes up ON u.id = up.usuario_id
             INNER JOIN permissoes p ON up.permissao_id = p.id
             WHERE p.nome = :permissao 
             AND u.empresa = :empresa 
             AND u.status = 'Ativo'
             AND u.email IS NOT NULL 
             AND u.email <> ''",
            "permissao={$permissao}&empresa={$empresa}"
        );
        return $read;
    }

    /**
     * Busca todos os administradores da empresa (tipo 1)
     */
    public function getAdministradores(int $empresa): Read
    {
        $read = new Read();
        $read->ExeRead(
            'usuarios',
            'WHERE empresa = :empresa AND tipo = 1 AND status = "Ativo" AND email IS NOT NULL AND email <> ""',
            "empresa={$empresa}"
        );
        return $read;
    }

    /**
     * Busca usuários que possuem qualquer uma das permissões especificadas
     */
    public function getUsuariosPorPermissoes(array $permissoes, int $empresa): Read
    {
        if (empty($permissoes)) {
            $read = new Read();
            return $read;
        }

        $permissoesEscapadas = array_map(function($p) {
            return "'" . addslashes($p) . "'";
        }, $permissoes);

        $permissoesList = implode(',', $permissoesEscapadas);

        $read = new Read();
        $read->FullRead(
            "SELECT DISTINCT u.id, u.nome, u.email, u.tipo
             FROM usuarios u
             INNER JOIN usuario_permissoes up ON u.id = up.usuario_id
             INNER JOIN permissoes p ON up.permissao_id = p.id
             WHERE p.nome IN ({$permissoesList})
             AND u.empresa = :empresa 
             AND u.status = 'Ativo'
             AND u.email IS NOT NULL 
             AND u.email <> ''",
            "empresa={$empresa}"
        );
        return $read;
    }
}
