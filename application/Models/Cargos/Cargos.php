<?php

namespace Agencia\Close\Models\Cargos;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class Cargos extends Model 
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
     * Lista todos os cargos da empresa
     */
    public function getCargos($company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("cargos", 
            "WHERE empresa = :empresa AND status = 'Ativo' ORDER BY nome ASC", 
            "empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Busca um cargo por ID
     */
    public function getCargo($id, $company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("cargos", 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Cria um novo cargo
     */
    public function criarCargo($data): Create
    {
        $this->create = new Create();
        $this->create->ExeCreate("cargos", $data);
        return $this->create;
    }

    /**
     * Atualiza um cargo
     */
    public function atualizarCargo($id, $data, $company): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate("cargos", $data, 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->update;
    }

    /**
     * Remove um cargo
     */
    public function removerCargo($id, $company): Delete
    {
        $this->delete = new Delete();
        $this->delete->ExeDelete("cargos", 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->delete;
    }

    /**
     * Verifica se o nome do cargo já existe
     */
    public function nomeExiste($nome, $company, $excludeId = null): Read
    {
        $this->read = new Read();
        $where = "WHERE nome = :nome AND empresa = :empresa";
        $params = "nome={$nome}&empresa={$company}";
        
        if ($excludeId) {
            $where .= " AND id != :exclude_id";
            $params .= "&exclude_id={$excludeId}";
        }
        
        $this->read->ExeRead("cargos", $where, $params);
        return $this->read;
    }

    /**
     * Busca um cargo por nome
     */
    public function getCargoPorNome($nome, $company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("cargos", 
            "WHERE nome = :nome AND empresa = :empresa AND status = 'Ativo'", 
            "nome={$nome}&empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Lista todas as permissões disponíveis
     */
    public function getPermissoesDisponiveis(): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("permissoes", "ORDER BY grupo ASC, titulo ASC");
        return $this->read;
    }

    /**
     * Busca permissões de um cargo
     */
    public function getPermissoesCargo($cargoId): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("cargo_permissoes", 
            "WHERE cargo_id = :cargo_id", 
            "cargo_id={$cargoId}"
        );
        return $this->read;
    }

    /**
     * Salva permissões de um cargo
     */
    public function salvarPermissoesCargo($cargoId, $permissoes): bool
    {
        // Remove permissões existentes
        $this->delete = new Delete();
        $this->delete->ExeDelete("cargo_permissoes", 
            "WHERE cargo_id = :cargo_id", 
            "cargo_id={$cargoId}"
        );

        // Insere novas permissões
        if (!empty($permissoes)) {
            foreach ($permissoes as $permissaoId) {
                $this->create = new Create();
                $this->create->ExeCreate("cargo_permissoes", [
                    'cargo_id' => $cargoId,
                    'permissao_id' => $permissaoId
                ]);
            }
        }

        return true;
    }
}

