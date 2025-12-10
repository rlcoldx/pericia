<?php

namespace Agencia\Close\Models\Perito;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class Perito extends Model 
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
     * Lista todos os peritos da empresa
     */
    public function getPeritos($company, $filtros = []): Read
    {
        $this->read = new Read();
        $where = "WHERE empresa = :empresa";
        $params = "empresa={$company}";

        // Filtros opcionais
        if (isset($filtros['status']) && !empty($filtros['status'])) {
            $where .= " AND status = :status";
            $params .= "&status={$filtros['status']}";
        }

        if (isset($filtros['especialidade']) && !empty($filtros['especialidade'])) {
            $where .= " AND especialidade LIKE :especialidade";
            $params .= "&especialidade=%{$filtros['especialidade']}%";
        }

        $orderBy = "ORDER BY nome ASC";
        
        $this->read->ExeRead("peritos", $where . " " . $orderBy, $params);
        return $this->read;
    }

    /**
     * Busca um perito por ID
     */
    public function getPerito($id, $company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("peritos", 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Cria um novo perito
     */
    public function criarPerito($data): Create
    {
        $this->create = new Create();
        $this->create->ExeCreate("peritos", $data);
        return $this->create;
    }

    /**
     * Atualiza um perito
     */
    public function atualizarPerito($id, $data, $company): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate("peritos", $data, 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->update;
    }

    /**
     * Remove um perito
     */
    public function removerPerito($id, $company): Delete
    {
        $this->delete = new Delete();
        $this->delete->ExeDelete("peritos", 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->delete;
    }

    /**
     * Busca peritos ativos para select
     */
    public function getPeritosAtivos($company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("peritos", 
            "WHERE empresa = :empresa AND status = 'Ativo' ORDER BY nome ASC", 
            "empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Busca peritos para DataTables (Server-Side Processing)
     * 
     * @param int $company ID da empresa
     * @param array $params Parâmetros do DataTables (start, length, search, order_column, order_dir)
     * @param array $filtros Filtros adicionais (status, especialidade)
     * @return array ['data' => [], 'total' => int, 'filtered' => int]
     */
    public function getPeritosDataTable(int $company, array $params, array $filtros = []): array
    {
        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 10;
        $search = $params['search'] ?? '';
        $orderColumn = $params['order_column'] ?? 0;
        $orderDir = $params['order_dir'] ?? 'ASC';

        // Mapeamento de colunas do DataTable para colunas do banco
        $columnMap = [
            0 => 'nome',
            1 => 'email',
            2 => 'telefone',
            3 => 'especialidade',
            4 => 'status'
        ];

        $orderBy = 'nome ASC';
        if (isset($columnMap[$orderColumn])) {
            $orderBy = $columnMap[$orderColumn] . ' ' . $orderDir;
        }

        // Monta WHERE base
        $where = "WHERE empresa = :empresa";
        $whereParams = "empresa={$company}";

        // Aplica filtros adicionais
        if (!empty($filtros['status'])) {
            $where .= " AND status = :status";
            $whereParams .= "&status={$filtros['status']}";
        }
        if (!empty($filtros['especialidade'])) {
            $where .= " AND especialidade LIKE :especialidade";
            $whereParams .= "&especialidade=%{$filtros['especialidade']}%";
        }

        // Busca global
        $searchWhere = $where;
        $searchParams = $whereParams;
        if (!empty($search)) {
            $searchWhere .= " AND (
                nome LIKE :search OR 
                email LIKE :search OR 
                telefone LIKE :search OR 
                documento LIKE :search OR 
                especialidade LIKE :search
            )";
            $searchParams .= "&search=%{$search}%";
        }

        // Conta total de registros (sem filtros de busca)
        $this->read = new Read();
        $this->read->ExeRead("peritos", $where, $whereParams);
        $totalRecords = $this->read->getRowCount();

        // Conta registros filtrados (com busca)
        $this->read = new Read();
        $this->read->ExeRead("peritos", $searchWhere, $searchParams);
        $filteredRecords = $this->read->getRowCount();

        // Busca dados paginados
        $this->read = new Read();
        $limitClause = "LIMIT :limit OFFSET :offset";
        $finalParams = $searchParams . "&limit={$length}&offset={$start}";
        $this->read->ExeRead("peritos", $searchWhere . " ORDER BY " . $orderBy . " " . $limitClause, $finalParams);
        
        return [
            'data' => $this->read->getResult() ?? [],
            'total' => $totalRecords,
            'filtered' => $filteredRecords
        ];
    }
}

