<?php

namespace Agencia\Close\Models\Equipe;

use Agencia\Close\Conn\Conn;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class Equipe extends Model 
{
    protected Create $create;
    protected Read $read;
    protected Update $update;
    protected Delete $delete;

    public function __construct()
    {
        $this->create = new Create();
        $this->read = new Read();
        $this->update = new Update();
        $this->delete = new Delete();
    }

    /**
     * Lista todos os usuários tipo 3 (equipe) da empresa logada
     */
    public function getEquipe($company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("usuarios", 
            "WHERE empresa = :empresa AND tipo = 3 AND status = 'Ativo' ORDER BY nome ASC", 
            "empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Lista todos os usuários ativos da empresa (para tarefas)
     */
    public function getUsuariosAtivos($company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("usuarios", 
            "WHERE empresa = :empresa AND status = 'Ativo' ORDER BY nome ASC", 
            "empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Busca um membro da equipe por ID
     */
    public function getMembroEquipe($id, $company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("usuarios", 
            "WHERE id = :id AND empresa = :empresa AND tipo = 3", 
            "id={$id}&empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Cria um novo membro da equipe
     */
    public function criarMembroEquipe($data): Create
    {
        $this->create = new Create();
        $this->create->ExeCreate("usuarios", $data);
        return $this->create;
    }

    /**
     * Atualiza um membro da equipe
     */
    public function atualizarMembroEquipe($id, $data, $company): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate("usuarios", $data, 
            "WHERE id = :id AND empresa = :empresa AND tipo = 3", 
            "id={$id}&empresa={$company}"
        );
        return $this->update;
    }

    /**
     * Remove um membro da equipe (hard delete - remove permanentemente do banco)
     */
    public function removerMembroEquipe($id, $company): Delete
    {
        $this->delete = new Delete();
        $this->delete->ExeDelete("usuarios", 
            "WHERE id = :id AND empresa = :empresa AND tipo = 3", 
            "id={$id}&empresa={$company}"
        );
        return $this->delete;
    }

    /**
     * Verifica se o email já existe para outro usuário da empresa
     */
    public function emailExiste($email, $company, $excludeId = null): Read
    {
        $this->read = new Read();
        $where = "WHERE email = :email AND empresa = :empresa AND tipo = 3";
        $params = "email={$email}&empresa={$company}";
        
        if ($excludeId) {
            $where .= " AND id != :exclude_id";
            $params .= "&exclude_id={$excludeId}";
        }
        
        $this->read->ExeRead("usuarios", $where, $params);
        return $this->read;
    }
}