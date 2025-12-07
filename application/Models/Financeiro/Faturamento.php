<?php

namespace Agencia\Close\Models\Financeiro;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class Faturamento extends Model 
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
     * Lista todas as faturas da empresa
     */
    public function getFaturas($company, $filtros = []): Read
    {
        $this->read = new Read();
        $where = "WHERE empresa = :empresa";
        $params = "empresa={$company}";

        if (isset($filtros['status']) && !empty($filtros['status'])) {
            $where .= " AND status = :status";
            $params .= "&status={$filtros['status']}";
        }

        if (isset($filtros['data_inicio']) && !empty($filtros['data_inicio'])) {
            $where .= " AND data_emissao >= :data_inicio";
            $params .= "&data_inicio={$filtros['data_inicio']}";
        }

        if (isset($filtros['data_fim']) && !empty($filtros['data_fim'])) {
            $where .= " AND data_emissao <= :data_fim";
            $params .= "&data_fim={$filtros['data_fim']}";
        }

        if (isset($filtros['numero_fatura']) && !empty($filtros['numero_fatura'])) {
            $where .= " AND numero_fatura LIKE :numero_fatura";
            $params .= "&numero_fatura=%{$filtros['numero_fatura']}%";
        }

        $orderBy = "ORDER BY data_emissao DESC, id DESC";
        
        $this->read->ExeRead("faturas", $where . " " . $orderBy, $params);
        return $this->read;
    }

    /**
     * Busca uma fatura por ID
     */
    public function getFatura($id, $company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("faturas", 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Busca faturas por agendamento
     */
    public function getFaturasPorAgendamento($agendamentoId, $company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("faturas", 
            "WHERE agendamento_id = :agendamento_id AND empresa = :empresa ORDER BY data_emissao DESC", 
            "agendamento_id={$agendamentoId}&empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Gera próximo número de fatura
     */
    public function gerarProximoNumeroFatura($company): string
    {
        $this->read = new Read();
        $query = "SELECT MAX(CAST(SUBSTRING_INDEX(numero_fatura, '-', -1) AS UNSIGNED)) as ultimo_numero 
                  FROM faturas 
                  WHERE empresa = :empresa 
                  AND numero_fatura LIKE CONCAT(YEAR(NOW()), '-%')";
        $this->read->FullRead($query, "empresa={$company}");
        $result = $this->read->getResult();
        
        $ultimoNumero = $result[0]['ultimo_numero'] ?? 0;
        $novoNumero = $ultimoNumero + 1;
        
        return date('Y') . '-' . str_pad($novoNumero, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Cria uma nova fatura
     */
    public function criar($data): bool
    {
        // Calcula valor líquido
        $valorTotal = $data['valor_total'] ?? 0;
        $valorDesconto = $data['valor_desconto'] ?? 0;
        $valorAcrescimo = $data['valor_acrescimo'] ?? 0;
        $data['valor_liquido'] = $valorTotal - $valorDesconto + $valorAcrescimo;

        // Gera número da fatura se não fornecido
        if (empty($data['numero_fatura'])) {
            $data['numero_fatura'] = $this->gerarProximoNumeroFatura($data['empresa']);
        }

        $this->create->ExeCreate("faturas", $data);
        return $this->create->getResult();
    }

    /**
     * Atualiza uma fatura
     */
    public function atualizar($id, $company, $data): bool
    {
        // Recalcula valor líquido se necessário
        if (isset($data['valor_total']) || isset($data['valor_desconto']) || isset($data['valor_acrescimo'])) {
            $fatura = $this->getFatura($id, $company);
            $faturaData = $fatura->getResult()[0] ?? [];
            
            $valorTotal = $data['valor_total'] ?? $faturaData['valor_total'] ?? 0;
            $valorDesconto = $data['valor_desconto'] ?? $faturaData['valor_desconto'] ?? 0;
            $valorAcrescimo = $data['valor_acrescimo'] ?? $faturaData['valor_acrescimo'] ?? 0;
            $data['valor_liquido'] = $valorTotal - $valorDesconto + $valorAcrescimo;
        }

        $this->update->ExeUpdate("faturas", $data, "WHERE id = :id AND empresa = :empresa", "id={$id}&empresa={$company}");
        return $this->update->getResult();
    }

    /**
     * Remove uma fatura
     */
    public function remover($id, $company): bool
    {
        $this->delete->ExeDelete("faturas", "WHERE id = :id AND empresa = :empresa", "id={$id}&empresa={$company}");
        return $this->delete->getResult();
    }

    /**
     * Busca faturas para DataTable (AJAX)
     */
    public function getFaturasDataTable(int $company, array $filtros = []): array
    {
        $where = "WHERE f.empresa = :empresa";
        $params = "empresa={$company}";

        if (!empty($filtros['status'])) {
            $where .= " AND f.status = :status";
            $params .= "&status={$filtros['status']}";
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND f.data_emissao >= :data_inicio";
            $params .= "&data_inicio={$filtros['data_inicio']}";
        }
        if (!empty($filtros['data_fim'])) {
            $where .= " AND f.data_emissao <= :data_fim";
            $params .= "&data_fim={$filtros['data_fim']}";
        }
        if (!empty($filtros['numero_fatura'])) {
            $where .= " AND f.numero_fatura LIKE :numero_fatura";
            $params .= "&numero_fatura=%{$filtros['numero_fatura']}%";
        }

        $query = "SELECT f.*, 
                         a.cliente_nome as agendamento_cliente,
                         a.tipo_pericia
                  FROM faturas f
                  LEFT JOIN agendamentos a ON f.agendamento_id = a.id AND f.empresa = a.empresa
                  {$where}
                  ORDER BY f.data_emissao DESC, f.id DESC";

        $this->read->FullRead($query, $params);
        return $this->read->getResult() ?? [];
    }

    /**
     * Conta faturas por status
     */
    public function contarPorStatus($company): Read
    {
        $this->read = new Read();
        $query = "SELECT status, COUNT(*) as total 
                  FROM faturas 
                  WHERE empresa = :empresa 
                  GROUP BY status";
        $this->read->FullRead($query, "empresa={$company}");
        return $this->read;
    }
}

