<?php

namespace Agencia\Close\Models\Quesito;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class Quesito extends Model
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
        $this->create->ExeCreate('quesitos', $data);
        return $this->create;
    }

    public function atualizar(int $id, int $empresa, array $data): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate(
            'quesitos',
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
            'quesitos',
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->delete;
    }

    /**
     * Retorna lista distinta de tipos para Select2 com tags
     */
    public function getTiposDistinct(int $empresa): Read
    {
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT DISTINCT tipo 
             FROM quesitos 
             WHERE empresa = :empresa 
               AND tipo IS NOT NULL 
               AND tipo <> '' 
             ORDER BY tipo ASC",
            "empresa={$empresa}"
        );

        return $this->read;
    }

    public function getPorId(int $id, int $empresa): Read
    {
        $this->read = new Read();
        $this->read->ExeRead(
            'quesitos',
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->read;
    }

    public function listar(int $empresa, array $filtros = []): Read
    {
        $this->read = new Read();

        $where = 'WHERE empresa = :empresa';
        $params = "empresa={$empresa}";

        if (!empty($filtros['status'])) {
            $where .= ' AND status = :status';
            $params .= "&status={$filtros['status']}";
        }

        if (!empty($filtros['data_inicio'])) {
            $where .= ' AND data >= :data_inicio';
            $params .= "&data_inicio={$filtros['data_inicio']}";
        }

        if (!empty($filtros['data_fim'])) {
            $where .= ' AND data <= :data_fim';
            $params .= "&data_fim={$filtros['data_fim']}";
        }

        if (!empty($filtros['tipo'])) {
            $where .= ' AND tipo LIKE :tipo';
            $params .= "&tipo=%{$filtros['tipo']}%";
        }

        if (!empty($filtros['vara'])) {
            $where .= ' AND vara LIKE :vara';
            $params .= "&vara=%{$filtros['vara']}%";
        }

        if (!empty($filtros['reclamante'])) {
            $where .= ' AND reclamante LIKE :reclamante';
            $params .= "&reclamante=%{$filtros['reclamante']}%";
        }

        if (!empty($filtros['reclamada'])) {
            $where .= ' AND reclamada LIKE :reclamada';
            $params .= "&reclamada=%{$filtros['reclamada']}%";
        }

        $where .= ' ORDER BY data DESC, id DESC';

        $this->read->ExeRead('quesitos', $where, $params);
        return $this->read;
    }

    /**
     * Retorna dados paginados para DataTables (Server-Side)
     *
     * @param int   $empresa
     * @param array $params  Parâmetros do DataTables (start, length, search, order_column, order_dir)
     * @param array $filtros Filtros adicionais (status, datas, etc.)
     * @return array ['data' => [], 'total' => int, 'filtered' => int]
     */
    public function getQuesitosDataTable(int $empresa, array $params, array $filtros = []): array
    {
        $start       = $params['start'] ?? 0;
        $length      = $params['length'] ?? 10;
        $search      = $params['search'] ?? '';
        $orderColumn = $params['order_column'] ?? 0;
        $orderDir    = $params['order_dir'] ?? 'ASC';

        // Mapeamento colunas DataTables -> banco
        $columnMap = [
            0 => 'q.data',
            1 => 'q.tipo',
            2 => 'q.vara',
            3 => 'rq.nome',
            4 => 'rd.nome',
            5 => 'q.status',
        ];

        $orderBy = 'q.data DESC, q.id DESC';
        if (isset($columnMap[$orderColumn])) {
            $orderBy = $columnMap[$orderColumn] . ' ' . $orderDir;
        }

        // WHERE base
        $where       = 'WHERE q.empresa = :empresa';
        $whereParams = "empresa={$empresa}";

        // Filtros adicionais
        if (!empty($filtros['status'])) {
            $where       .= ' AND q.status = :status';
            $whereParams .= "&status={$filtros['status']}";
        }

        if (!empty($filtros['data_inicio'])) {
            $where       .= ' AND q.data >= :data_inicio';
            $whereParams .= "&data_inicio={$filtros['data_inicio']}";
        }

        if (!empty($filtros['data_fim'])) {
            $where       .= ' AND q.data <= :data_fim';
            $whereParams .= "&data_fim={$filtros['data_fim']}";
        }

        if (!empty($filtros['tipo'])) {
            $where       .= ' AND q.tipo LIKE :tipo';
            $whereParams .= '&tipo=%' . $filtros['tipo'] . '%';
        }

        if (!empty($filtros['vara'])) {
            $where       .= ' AND q.vara LIKE :vara';
            $whereParams .= '&vara=%' . $filtros['vara'] . '%';
        }

        if (!empty($filtros['reclamante'])) {
            $where       .= ' AND rq.nome LIKE :reclamante';
            $whereParams .= '&reclamante=%' . $filtros['reclamante'] . '%';
        }

        if (!empty($filtros['reclamada'])) {
            $where       .= ' AND rd.nome LIKE :reclamada';
            $whereParams .= '&reclamada=%' . $filtros['reclamada'] . '%';
        }

        // Busca global
        $searchWhere  = $where;
        $searchParams = $whereParams;
        if (!empty($search)) {
            $searchWhere .= " AND (
                q.tipo LIKE :search OR
                q.vara LIKE :search OR
                rq.nome LIKE :search OR
                rd.nome LIKE :search
            )";
            $searchParams .= "&search=%{$search}%";
        }

        // Query base com JOINs
        $baseQuery = "SELECT q.*, 
                             rq.nome as reclamante_nome,
                             rd.nome as reclamada_nome
                      FROM quesitos q
                      LEFT JOIN reclamantes rq ON q.reclamante_id = rq.id AND rq.empresa = q.empresa
                      LEFT JOIN reclamadas rd ON q.reclamada_id = rd.id AND rd.empresa = q.empresa";

        // Total sem busca
        $this->read = new Read();
        $this->read->FullRead($baseQuery . ' ' . $where, $whereParams);
        $totalRecords = $this->read->getRowCount();

        // Total com busca
        $this->read = new Read();
        $this->read->FullRead($baseQuery . ' ' . $searchWhere, $searchParams);
        $filteredRecords = $this->read->getRowCount();

        // Dados paginados
        $this->read = new Read();
        $limitClause = 'LIMIT :limit OFFSET :offset';
        $finalParams = $searchParams . "&limit={$length}&offset={$start}";
        $this->read->FullRead($baseQuery . ' ' . $searchWhere . ' ORDER BY ' . $orderBy . ' ' . $limitClause, $finalParams);

        return [
            'data'     => $this->read->getResult() ?? [],
            'total'    => $totalRecords,
            'filtered' => $filteredRecords,
        ];
    }
}

