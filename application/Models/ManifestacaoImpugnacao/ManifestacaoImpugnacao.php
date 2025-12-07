<?php

namespace Agencia\Close\Models\ManifestacaoImpugnacao;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class ManifestacaoImpugnacao extends Model
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
        $this->create->ExeCreate('manifestacoes_impugnacoes', $data);
        return $this->create;
    }

    public function atualizar(int $id, int $empresa, array $data): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate(
            'manifestacoes_impugnacoes',
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
            'manifestacoes_impugnacoes',
            'WHERE id = :id AND empresa = :empresa',
            "id={$id}&empresa={$empresa}"
        );
        return $this->delete;
    }

    public function getPorId(int $id, int $empresa): Read
    {
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT mi.*, 
                    r.nome as reclamada_nome,
                    r2.nome as reclamante_nome,
                    p.nome as perito_nome
             FROM manifestacoes_impugnacoes mi
             LEFT JOIN reclamadas r ON mi.reclamada_id = r.id AND mi.empresa = r.empresa
             LEFT JOIN reclamantes r2 ON mi.reclamante_id = r2.id AND mi.empresa = r2.empresa
             LEFT JOIN peritos p ON mi.perito_id = p.id AND mi.empresa = p.empresa
             WHERE mi.id = :id AND mi.empresa = :empresa",
            "id={$id}&empresa={$empresa}"
        );
        return $this->read;
    }

    public function getManifestacoesDataTable(int $empresa, array $params, array $filtros = []): array
    {
        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 10;
        $search = $params['search'] ?? '';
        $orderColumn = $params['order_column'] ?? 0;
        $orderDir = $params['order_dir'] ?? 'ASC';

        $columnMap = [
            0 => 'mi.data',
            1 => 'mi.tipo',
            2 => 'mi.numero',
            3 => 'r.nome',
            4 => 'r2.nome',
            5 => 'mi.favoravel',
            6 => 'p.nome',
        ];

        $orderBy = 'mi.data DESC, mi.id DESC';
        if (isset($columnMap[$orderColumn])) {
            $orderBy = $columnMap[$orderColumn] . ' ' . $orderDir;
        }

        $where = 'WHERE mi.empresa = :empresa';
        $whereParams = "empresa={$empresa}";

        if (!empty($filtros['tipo'])) {
            $where .= ' AND mi.tipo LIKE :tipo';
            $whereParams .= '&tipo=%' . $filtros['tipo'] . '%';
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= ' AND mi.data >= :data_inicio';
            $whereParams .= "&data_inicio={$filtros['data_inicio']}";
        }
        if (!empty($filtros['data_fim'])) {
            $where .= ' AND mi.data <= :data_fim';
            $whereParams .= "&data_fim={$filtros['data_fim']}";
        }
        if (!empty($filtros['favoravel'])) {
            $where .= ' AND mi.favoravel = :favoravel';
            $whereParams .= "&favoravel={$filtros['favoravel']}";
        }
        if (!empty($filtros['reclamada_id'])) {
            $where .= ' AND mi.reclamada_id = :reclamada_id';
            $whereParams .= "&reclamada_id={$filtros['reclamada_id']}";
        }
        if (!empty($filtros['reclamante_id'])) {
            $where .= ' AND mi.reclamante_id = :reclamante_id';
            $whereParams .= "&reclamante_id={$filtros['reclamante_id']}";
        }
        if (!empty($filtros['perito_id'])) {
            $where .= ' AND mi.perito_id = :perito_id';
            $whereParams .= "&perito_id={$filtros['perito_id']}";
        }

        $searchWhere = $where;
        $searchParams = $whereParams;
        if (!empty($search)) {
            $searchWhere .= " AND (
                mi.tipo LIKE :search OR
                mi.numero LIKE :search OR
                r.nome LIKE :search OR
                r2.nome LIKE :search OR
                p.nome LIKE :search
            )";
            $searchParams .= "&search=%{$search}%";
        }

        $baseQuery = "SELECT mi.*, 
                             r.nome as reclamada_nome,
                             r2.nome as reclamante_nome,
                             p.nome as perito_nome
                      FROM manifestacoes_impugnacoes mi
                      LEFT JOIN reclamadas r ON mi.reclamada_id = r.id AND mi.empresa = r.empresa
                      LEFT JOIN reclamantes r2 ON mi.reclamante_id = r2.id AND mi.empresa = r2.empresa
                      LEFT JOIN peritos p ON mi.perito_id = p.id AND mi.empresa = p.empresa";

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
