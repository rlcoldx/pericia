<?php

namespace Agencia\Close\Models\Parecer;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class Parecer extends Model
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

    public function criar(array $data): Create
    {
        $this->create = new Create();
        $this->create->ExeCreate('pareceres', $data);
        return $this->create;
    }

    public function atualizar(int $id, int $empresa, array $data): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate(
            'pareceres',
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
            'pareceres',
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->delete;
    }

    public function getPorId(int $id, int $empresa): Read
    {
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT p.*, 
                    r.nome as reclamada_nome,
                    r2.nome as reclamante_nome
             FROM pareceres p
             LEFT JOIN reclamadas r ON p.reclamada_id = r.id AND p.empresa = r.empresa
             LEFT JOIN reclamantes r2 ON p.reclamante_id = r2.id AND p.empresa = r2.empresa
             WHERE p.id = :id AND p.empresa = :empresa",
            "id={$id}&empresa={$empresa}"
        );
        return $this->read;
    }

    public function getPareceresDataTable(int $empresa, array $params, array $filtros = []): array
    {
        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 10;
        $search = $params['search'] ?? '';
        $orderColumn = $params['order_column'] ?? 0;
        $orderDir = $params['order_dir'] ?? 'ASC';

        $columnMap = [
            0 => 'p.data_realizacao',
            1 => 'p.data_fatal',
            2 => 'p.tipo',
            3 => 'p.assistente',
            4 => 'r.nome',
            5 => 'r2.nome',
        ];

        $orderBy = 'p.data_realizacao DESC, p.id DESC';
        if (isset($columnMap[$orderColumn])) {
            $orderBy = $columnMap[$orderColumn] . ' ' . $orderDir;
        }

        $where = 'WHERE p.empresa = :empresa';
        $whereParams = "empresa={$empresa}";

        if (!empty($filtros['data_inicio'])) {
            $where .= ' AND p.data_realizacao >= :data_inicio';
            $whereParams .= "&data_inicio={$filtros['data_inicio']}";
        }

        if (!empty($filtros['data_fim'])) {
            $where .= ' AND p.data_realizacao <= :data_fim';
            $whereParams .= "&data_fim={$filtros['data_fim']}";
        }

        if (!empty($filtros['tipo'])) {
            $where .= ' AND p.tipo LIKE :tipo';
            $whereParams .= '&tipo=%' . $filtros['tipo'] . '%';
        }

        if (!empty($filtros['reclamada_id'])) {
            $where .= ' AND p.reclamada_id = :reclamada_id';
            $whereParams .= "&reclamada_id={$filtros['reclamada_id']}";
        }

        if (!empty($filtros['reclamante_id'])) {
            $where .= ' AND p.reclamante_id = :reclamante_id';
            $whereParams .= "&reclamante_id={$filtros['reclamante_id']}";
        }

        $searchWhere = $where;
        $searchParams = $whereParams;
        if (!empty($search)) {
            $searchWhere .= " AND (
                p.tipo LIKE :search OR
                p.assistente LIKE :search OR
                r.nome LIKE :search OR
                r2.nome LIKE :search
            )";
            $searchParams .= "&search=%{$search}%";
        }

        $baseQuery = "SELECT p.*, 
                             r.nome as reclamada_nome,
                             r2.nome as reclamante_nome
                      FROM pareceres p
                      LEFT JOIN reclamadas r ON p.reclamada_id = r.id AND p.empresa = r.empresa
                      LEFT JOIN reclamantes r2 ON p.reclamante_id = r2.id AND p.empresa = r2.empresa";

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

    public function listarTipos(int $empresa): Read
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'tipos_parecer',
            'WHERE empresa = :empresa ORDER BY nome ASC',
            "empresa={$empresa}"
        );
        return $this->read;
    }

    public function criarTipo(int $empresa, string $nome): Create
    {
        $this->create = new Create();
        $this->create->ExeCreate('tipos_parecer', [
            'empresa' => $empresa,
            'nome' => mb_strtoupper($nome, 'UTF-8')
        ]);
        return $this->create;
    }
}
