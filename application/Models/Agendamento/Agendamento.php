<?php

namespace Agencia\Close\Models\Agendamento;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class Agendamento extends Model 
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
     * Lista todos os agendamentos da empresa
     */
    public function getAgendamentos($company, $filtros = []): Read
    {
        $this->read = new Read();
        $where = "WHERE empresa = :empresa";
        $params = "empresa={$company}";

        // Filtros opcionais
        if (isset($filtros['status']) && !empty($filtros['status'])) {
            $where .= " AND status = :status";
            $params .= "&status={$filtros['status']}";
        }

        if (isset($filtros['data_inicio']) && !empty($filtros['data_inicio'])) {
            $where .= " AND data_agendamento >= :data_inicio";
            $params .= "&data_inicio={$filtros['data_inicio']}";
        }

        if (isset($filtros['data_fim']) && !empty($filtros['data_fim'])) {
            $where .= " AND data_agendamento <= :data_fim";
            $params .= "&data_fim={$filtros['data_fim']}";
        }

        if (isset($filtros['perito_id']) && !empty($filtros['perito_id'])) {
            $where .= " AND perito_id = :perito_id";
            $params .= "&perito_id={$filtros['perito_id']}";
        }

        $orderBy = "ORDER BY data_agendamento DESC, hora_agendamento DESC";
        
        $this->read->ExeRead("agendamentos", $where . " " . $orderBy, $params);
        return $this->read;
    }

    /**
     * Busca um agendamento por ID
     */
    public function getAgendamento($id, $company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("agendamentos", 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Cria um novo agendamento
     */
    public function criarAgendamento($data): Create
    {
        $this->create = new Create();
        $this->create->ExeCreate("agendamentos", $data);
        return $this->create;
    }

    /**
     * Atualiza um agendamento
     */
    public function atualizarAgendamento($id, $data, $company): Update
    {
        $this->update = new Update();
        $this->update->ExeUpdate("agendamentos", $data, 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->update;
    }

    /**
     * Remove um agendamento
     */
    public function removerAgendamento($id, $company): Delete
    {
        $this->delete = new Delete();
        $this->delete->ExeDelete("agendamentos", 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->delete;
    }

    /**
     * Verifica conflito de horário para o perito
     */
    public function verificarConflitoHorario($peritoId, $dataAgendamento, $horaAgendamento, $excludeId = null): Read
    {
        $this->read = new Read();
        // Considera conflito apenas para agendamentos ainda ativos (Pendente ou Agendado)
        $where = "WHERE perito_id = :perito_id AND data_agendamento = :data_agendamento AND hora_agendamento = :hora_agendamento AND status IN ('Pendente', 'Agendado')";
        $params = "perito_id={$peritoId}&data_agendamento={$dataAgendamento}&hora_agendamento={$horaAgendamento}";
        
        if ($excludeId) {
            $where .= " AND id != :exclude_id";
            $params .= "&exclude_id={$excludeId}";
        }
        
        $this->read->ExeRead("agendamentos", $where, $params);
        return $this->read;
    }

    /**
     * Conta agendamentos por status
     */
    public function contarPorStatus($company): Read
    {
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT status, COUNT(*) as total FROM agendamentos WHERE empresa = :empresa GROUP BY status",
            "empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Busca agendamentos para DataTables (Server-Side Processing)
     * 
     * @param int $company ID da empresa
     * @param array $params Parâmetros do DataTables (start, length, search, order_column, order_dir)
     * @param array $filtros Filtros adicionais (status, data_inicio, data_fim, perito_id)
     * @return array ['data' => [], 'total' => int, 'filtered' => int]
     */
    public function getAgendamentosDataTable(int $company, array $params, array $filtros = []): array
    {
        $start = $params['start'] ?? 0;
        $length = $params['length'] ?? 10;
        $search = $params['search'] ?? '';
        $orderColumn = $params['order_column'] ?? 0;
        $orderDir = $params['order_dir'] ?? 'ASC';

        // Mapeamento de colunas do DataTable para colunas do banco
        $columnMap = [
            0 => 'a.cliente_nome',
            1 => 'a.data_agendamento',
            2 => 'a.perito_id',
            3 => 'a.tipo_pericia',
            4 => 'a.status',
            5 => 'a.local_pericia'
        ];

        $orderBy = 'a.data_agendamento DESC, a.hora_agendamento DESC';
        if (isset($columnMap[$orderColumn])) {
            $orderBy = $columnMap[$orderColumn] . ' ' . $orderDir;
        }

        // Monta WHERE base (sem alias para contagens)
        $where = "WHERE empresa = :empresa";
        $whereParams = "empresa={$company}";

        // Aplica filtros adicionais
        if (!empty($filtros['status'])) {
            $where .= " AND status = :status";
            $whereParams .= "&status={$filtros['status']}";
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND data_agendamento >= :data_inicio";
            $whereParams .= "&data_inicio={$filtros['data_inicio']}";
        }
        if (!empty($filtros['data_fim'])) {
            $where .= " AND data_agendamento <= :data_fim";
            $whereParams .= "&data_fim={$filtros['data_fim']}";
        }
        if (!empty($filtros['perito_id'])) {
            $where .= " AND perito_id = :perito_id";
            $whereParams .= "&perito_id={$filtros['perito_id']}";
        }

        // Busca global (sem alias para contagens)
        $searchWhere = $where;
        $searchParams = $whereParams;
        if (!empty($search)) {
            $searchWhere .= " AND (
                cliente_nome LIKE :search OR 
                cliente_email LIKE :search OR 
                cliente_cpf LIKE :search OR 
                tipo_pericia LIKE :search OR 
                local_pericia LIKE :search
            )";
            $searchParams .= "&search=%{$search}%";
        }

        // Conta total de registros (sem filtros de busca)
        $this->read = new Read();
        $this->read->ExeRead("agendamentos", $where, $whereParams);
        $totalRecords = $this->read->getRowCount();

        // Conta registros filtrados (com busca)
        $this->read = new Read();
        $this->read->ExeRead("agendamentos", $searchWhere, $searchParams);
        $filteredRecords = $this->read->getRowCount();

        // Monta WHERE com alias para a query com JOIN
        $whereWithAlias = "WHERE a.empresa = :empresa";
        $whereParamsWithAlias = "empresa={$company}";

        // Aplica filtros adicionais com alias
        if (!empty($filtros['status'])) {
            $whereWithAlias .= " AND a.status = :status";
            $whereParamsWithAlias .= "&status={$filtros['status']}";
        }
        if (!empty($filtros['data_inicio'])) {
            $whereWithAlias .= " AND a.data_agendamento >= :data_inicio";
            $whereParamsWithAlias .= "&data_inicio={$filtros['data_inicio']}";
        }
        if (!empty($filtros['data_fim'])) {
            $whereWithAlias .= " AND a.data_agendamento <= :data_fim";
            $whereParamsWithAlias .= "&data_fim={$filtros['data_fim']}";
        }
        if (!empty($filtros['perito_id'])) {
            $whereWithAlias .= " AND a.perito_id = :perito_id";
            $whereParamsWithAlias .= "&perito_id={$filtros['perito_id']}";
        }

        // Busca global com alias
        $searchWhereWithAlias = $whereWithAlias;
        $searchParamsWithAlias = $whereParamsWithAlias;
        if (!empty($search)) {
            $searchWhereWithAlias .= " AND (
                a.cliente_nome LIKE :search OR 
                a.cliente_email LIKE :search OR 
                a.cliente_cpf LIKE :search OR 
                a.tipo_pericia LIKE :search OR 
                a.local_pericia LIKE :search
            )";
            $searchParamsWithAlias .= "&search=%{$search}%";
        }

        // Busca dados paginados com JOIN para pegar nome do perito
        $this->read = new Read();
        $query = "SELECT a.*, p.nome as perito_nome 
                  FROM agendamentos a 
                  LEFT JOIN peritos p ON a.perito_id = p.id AND a.empresa = p.empresa 
                  {$searchWhereWithAlias} 
                  ORDER BY {$orderBy} 
                  LIMIT :limit OFFSET :offset";
        $finalParams = $searchParamsWithAlias . "&limit={$length}&offset={$start}";
        $this->read->FullRead($query, $finalParams);
        
        return [
            'data' => $this->read->getResult() ?? [],
            'total' => $totalRecords,
            'filtered' => $filteredRecords
        ];
    }

    /**
     * Busca agendamentos para o calendário (formato FullCalendar)
     * 
     * @param int $company ID da empresa
     * @param string $start Data de início (Y-m-d)
     * @param string $end Data de fim (Y-m-d)
     * @param array $filtros Filtros adicionais (status, perito_id)
     * @return array Array de eventos no formato FullCalendar
     */
    public function getAgendamentosCalendario(int $company, string $start, string $end, array $filtros = []): array
    {
        $where = "WHERE a.empresa = :empresa AND a.data_agendamento >= :start AND a.data_agendamento <= :end";
        $params = "empresa={$company}&start={$start}&end={$end}";

        // Aplica filtros adicionais
        if (!empty($filtros['status'])) {
            $where .= " AND a.status = :status";
            $params .= "&status={$filtros['status']}";
        }
        if (!empty($filtros['perito_id'])) {
            $where .= " AND a.perito_id = :perito_id";
            $params .= "&perito_id={$filtros['perito_id']}";
        }

        // Busca agendamentos com JOIN para pegar nome do perito
        $query = "SELECT a.*, p.nome as perito_nome 
                  FROM agendamentos a 
                  LEFT JOIN peritos p ON a.perito_id = p.id AND a.empresa = p.empresa 
                  {$where} 
                  ORDER BY a.data_agendamento ASC, a.hora_agendamento ASC";
        
        $this->read->FullRead($query, $params);
        $agendamentos = $this->read->getResult() ?? [];

        $eventos = [];
        foreach ($agendamentos as $agendamento) {
            // Define cor baseada no status
            $cor = $this->getCorPorStatus($agendamento['status']);
            
            // Monta data/hora completa
            $dataHoraInicio = $agendamento['data_agendamento'] . ' ' . ($agendamento['hora_agendamento'] ?? '09:00:00');
            $dataHoraFim = date('Y-m-d H:i:s', strtotime($dataHoraInicio . ' +1 hour')); // Assume 1 hora de duração
            
            // Monta título do evento
            $titulo = $agendamento['cliente_nome'];
            if ($agendamento['perito_nome']) {
                $titulo .= ' - ' . $agendamento['perito_nome'];
            }
            if ($agendamento['tipo_pericia']) {
                $titulo .= ' (' . $agendamento['tipo_pericia'] . ')';
            }
            
            $eventos[] = [
                'id' => $agendamento['id'],
                'title' => $titulo,
                'start' => $dataHoraInicio,
                'end' => $dataHoraFim,
                'color' => $cor,
                'backgroundColor' => $cor,
                'borderColor' => $cor,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'cliente_nome' => $agendamento['cliente_nome'],
                    'cliente_email' => $agendamento['cliente_email'],
                    'cliente_telefone' => $agendamento['cliente_telefone'],
                    'tipo_pericia' => $agendamento['tipo_pericia'],
                    'local_pericia' => $agendamento['local_pericia'],
                    'status' => $agendamento['status'],
                    'perito_id' => $agendamento['perito_id'],
                    'perito_nome' => $agendamento['perito_nome'] ?? null,
                    'observacoes' => $agendamento['observacoes']
                ]
            ];
        }

        return $eventos;
    }

    /**
     * Retorna cor hexadecimal baseada no status (cores escuras para tema escuro)
     */
    private function getCorPorStatus(string $status): string
    {
        $cores = [
            'Pendente' => '#8b6914',    // Amarelo escuro
            'Agendado' => '#0a4d8a',    // Azul escuro
            'Realizado' => '#0f5132',    // Verde escuro
            'Cancelado' => '#842029',    // Vermelho escuro
            'Aprovado' => '#146c43',     // Verde água escuro
            'Rejeitado' => '#495057'     // Cinza escuro
        ];

        return $cores[$status] ?? '#495057';
    }
}

