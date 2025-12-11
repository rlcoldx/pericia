<?php

namespace Agencia\Close\Models\Equipe;

use Agencia\Close\Conn\Conn;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;
use Agencia\Close\Models\Cargos\Cargos;

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

    /**
     * Copia permissões do cargo para o usuário
     * Remove permissões antigas e adiciona as novas do cargo
     */
    public function copiarPermissoesDoCargo(int $usuarioId, ?string $cargoNome, int $empresa): bool
    {
        try {
            // Remove todas as permissões atuais do usuário
            $this->delete = new Delete();
            $this->delete->ExeDelete("usuario_permissoes", 
                "WHERE usuario_id = :usuario_id", 
                "usuario_id={$usuarioId}"
            );

            // Se não houver cargo, apenas remove as permissões
            if (empty($cargoNome)) {
                return true;
            }

            // Busca o cargo pelo nome
            $cargosModel = new Cargos();
            $cargo = $cargosModel->getCargoPorNome($cargoNome, $empresa);
            $cargoData = $cargo->getResult()[0] ?? null;

            if (!$cargoData) {
                return true; // Cargo não encontrado, apenas remove permissões
            }

            $cargoId = (int) $cargoData['id'];

            // Busca permissões do cargo
            $permissoesCargo = $cargosModel->getPermissoesCargo($cargoId);
            $permissoes = $permissoesCargo->getResult() ?? [];

            // Copia permissões do cargo para o usuário
            if (!empty($permissoes)) {
                foreach ($permissoes as $permissao) {
                    $permissaoId = (int) $permissao['permissao_id'];
                    $this->create = new Create();
                    $this->create->ExeCreate("usuario_permissoes", [
                        'usuario_id' => $usuarioId,
                        'permissao_id' => $permissaoId
                    ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        } catch (\Error $e) {
            return false;
        }
    }

    /**
     * Atualiza permissões de todos os usuários que possuem um determinado cargo
     * Usado quando as permissões de um cargo são alteradas
     */
    public function atualizarPermissoesUsuariosPorCargo(string $cargoNome, int $empresa): bool
    {
        try {
            // Busca o cargo pelo nome
            $cargosModel = new Cargos();
            $cargo = $cargosModel->getCargoPorNome($cargoNome, $empresa);
            $cargoData = $cargo->getResult()[0] ?? null;

            if (!$cargoData) {
                return false;
            }

            $cargoId = (int) $cargoData['id'];

            // Busca todos os usuários com esse cargo
            $this->read = new Read();
            $this->read->ExeRead("usuarios", 
                "WHERE cargo = :cargo AND empresa = :empresa AND tipo = 3 AND status = 'Ativo'", 
                "cargo={$cargoNome}&empresa={$empresa}"
            );
            $usuarios = $this->read->getResult() ?? [];

            // Atualiza permissões de cada usuário
            foreach ($usuarios as $usuario) {
                $this->copiarPermissoesDoCargo((int) $usuario['id'], $cargoNome, $empresa);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        } catch (\Error $e) {
            return false;
        }
    }

    /**
     * Atualiza permissões de todos os usuários da empresa baseado nos cargos
     * Útil para sincronizar permissões após mudanças nos cargos
     */
    public function sincronizarPermissoesTodosUsuarios(int $empresa): bool
    {
        try {
            // Busca todos os usuários tipo 3 (equipe) da empresa
            $this->read = new Read();
            $this->read->ExeRead("usuarios", 
                "WHERE empresa = :empresa AND tipo = 3 AND status = 'Ativo'", 
                "empresa={$empresa}"
            );
            $usuarios = $this->read->getResult() ?? [];

            // Atualiza permissões de cada usuário baseado no cargo
            foreach ($usuarios as $usuario) {
                $cargoNome = $usuario['cargo'] ?? null;
                if ($cargoNome) {
                    $this->copiarPermissoesDoCargo((int) $usuario['id'], $cargoNome, $empresa);
                }
            }

            return true;
        } catch (\Exception $e) {
            return false;
        } catch (\Error $e) {
            return false;
        }
    }
}