<?php

namespace Agencia\Close\Models\Reclamante;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class Reclamante extends Model
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

    public function listar(int $empresa): Read
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'reclamantes',
            'WHERE empresa = :empresa ORDER BY nome ASC',
            "empresa={$empresa}"
        );
        return $this->read;
    }

    public function getPorId(int $id, int $empresa): Read
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'reclamantes',
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->read;
    }

    public function criar(array $data): Create
    {
        $this->create = new Create();
        $this->create->ExeCreate('reclamantes', $data);
        return $this->create;
    }

    public function atualizar(int $id, int $empresa, array $data): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate(
            'reclamantes',
            $data,
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->update;
    }

    public function remover(int $id, int $empresa): Delete
    {
        $this->delete = new Delete();
        $this->delete->ExeDelete(
            'reclamantes',
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->delete;
    }

    /**
     * Busca reclamantes para DataTables (Server-Side Processing)
     */
    public function getReclamantesDataTable(int $empresa, array $params, array $filtros = []): array
    {
        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 10;
        $search = $params['search'] ?? '';
        $orderColumn = $params['order_column'] ?? 0;
        $orderDir = $params['order_dir'] ?? 'ASC';

        $columnMap = [
            0 => 'nome',
            1 => 'nome_contato',
            2 => 'email_contato',
            3 => 'telefone_contato',
        ];

        $orderBy = 'nome ASC';
        if (isset($columnMap[$orderColumn])) {
            $orderBy = $columnMap[$orderColumn] . ' ' . $orderDir;
        }

        $where = "WHERE empresa = :empresa";
        $whereParams = "empresa={$empresa}";

        $searchWhere = $where;
        $searchParams = $whereParams;
        if (!empty($search)) {
            $searchWhere .= " AND (
                nome LIKE :search OR
                nome_contato LIKE :search OR
                email_contato LIKE :search OR
                telefone_contato LIKE :search
            )";
            $searchParams .= "&search=%{$search}%";
        }

        $baseQuery = "SELECT * FROM reclamantes";

        $this->read = new Read();
        $this->read->FullRead($baseQuery . ' ' . $where, $whereParams);
        $totalRecords = $this->read->getRowCount();

        $this->read = new Read();
        $this->read->FullRead($baseQuery . ' ' . $searchWhere, $searchParams);
        $filteredRecords = $this->read->getRowCount();

        $this->read = new Read();
        $limitClause = 'LIMIT :limit OFFSET :offset';
        $finalParams = $searchParams . "&limit={$length}&offset={$start}";
        $this->read->FullRead($baseQuery . ' ' . $searchWhere . ' ORDER BY ' . $orderBy . ' ' . $limitClause, $finalParams);

        return [
            'data' => $this->read->getResult() ?? [],
            'total' => $totalRecords,
            'filtered' => $filteredRecords,
        ];
    }
}
