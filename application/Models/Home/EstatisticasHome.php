<?php

namespace Agencia\Close\Models\Home;

use Agencia\Close\Conn\Read;
use Agencia\Close\Models\Model;

class EstatisticasHome extends Model
{
    protected Read $read;

    public function __construct()
    {
        $this->read = new Read();
    }

    /**
     * Retorna estatísticas de Quesitos
     */
    public function getEstatisticasQuesitos(int $empresa, string $dataInicio = '', string $dataFim = ''): array
    {
        $where = 'WHERE empresa = :empresa';
        $params = "empresa={$empresa}";

        if (!empty($dataInicio) && !empty($dataFim)) {
            $where .= ' AND DATE(data) BETWEEN :data_inicio AND :data_fim';
            $params .= "&data_inicio={$dataInicio}&data_fim={$dataFim}";
        } elseif (!empty($dataInicio)) {
            $where .= ' AND DATE(data) >= :data_inicio';
            $params .= "&data_inicio={$dataInicio}";
        } elseif (!empty($dataFim)) {
            $where .= ' AND DATE(data) <= :data_fim';
            $params .= "&data_fim={$dataFim}";
        } else {
            // Mês atual por padrão
            $where .= ' AND YEAR(data) = YEAR(CURRENT_DATE()) AND MONTH(data) = MONTH(CURRENT_DATE())';
        }

        // Total por status
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT status, COUNT(*) as total 
             FROM quesitos 
             {$where}
             GROUP BY status",
            $params
        );
        $porStatus = $this->read->getResult() ?? [];

        // Total geral
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT COUNT(*) as total 
             FROM quesitos 
             {$where}",
            $params
        );
        $total = $this->read->getResult()[0]['total'] ?? 0;

        // Por dia do mês (para gráfico)
        $whereDia = $where;
        $paramsDia = $params;

        $this->read = new Read();
        $this->read->FullRead(
            "SELECT DAY(data) as dia, COUNT(*) as total 
             FROM quesitos 
             {$whereDia}
             GROUP BY DAY(data)
             ORDER BY dia ASC",
            $paramsDia
        );
        $porDia = $this->read->getResult() ?? [];

        return [
            'total' => (int)$total,
            'por_status' => $porStatus,
            'por_dia' => $porDia
        ];
    }

    /**
     * Retorna estatísticas de Manifestações/Impugnações
     */
    public function getEstatisticasManifestacoes(int $empresa, string $dataInicio = '', string $dataFim = ''): array
    {
        $where = 'WHERE empresa = :empresa';
        $params = "empresa={$empresa}";

        if (!empty($dataInicio) && !empty($dataFim)) {
            $where .= ' AND DATE(data) BETWEEN :data_inicio AND :data_fim';
            $params .= "&data_inicio={$dataInicio}&data_fim={$dataFim}";
        } elseif (!empty($dataInicio)) {
            $where .= ' AND DATE(data) >= :data_inicio';
            $params .= "&data_inicio={$dataInicio}";
        } elseif (!empty($dataFim)) {
            $where .= ' AND DATE(data) <= :data_fim';
            $params .= "&data_fim={$dataFim}";
        } else {
            $where .= ' AND YEAR(data) = YEAR(CURRENT_DATE()) AND MONTH(data) = MONTH(CURRENT_DATE())';
        }

        $this->read = new Read();
        $this->read->FullRead(
            "SELECT COUNT(*) as total 
             FROM manifestacoes_impugnacoes 
             {$where}",
            $params
        );
        $total = $this->read->getResult()[0]['total'] ?? 0;

        // Por favorável/desfavorável
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT favoravel, COUNT(*) as total 
             FROM manifestacoes_impugnacoes 
             {$where} AND favoravel IS NOT NULL
             GROUP BY favoravel",
            $params
        );
        $porFavoravel = $this->read->getResult() ?? [];

        // Por dia
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT DAY(data) as dia, COUNT(*) as total 
             FROM manifestacoes_impugnacoes 
             {$where}
             GROUP BY DAY(data)
             ORDER BY dia ASC",
            $params
        );
        $porDia = $this->read->getResult() ?? [];

        return [
            'total' => (int)$total,
            'por_favoravel' => $porFavoravel,
            'por_dia' => $porDia
        ];
    }

    /**
     * Retorna estatísticas de Pareceres
     */
    public function getEstatisticasPareceres(int $empresa, string $dataInicio = '', string $dataFim = ''): array
    {
        $where = 'WHERE empresa = :empresa';
        $params = "empresa={$empresa}";

        if (!empty($dataInicio) && !empty($dataFim)) {
            $where .= ' AND DATE(data_realizacao) BETWEEN :data_inicio AND :data_fim';
            $params .= "&data_inicio={$dataInicio}&data_fim={$dataFim}";
        } elseif (!empty($dataInicio)) {
            $where .= ' AND DATE(data_realizacao) >= :data_inicio';
            $params .= "&data_inicio={$dataInicio}";
        } elseif (!empty($dataFim)) {
            $where .= ' AND DATE(data_realizacao) <= :data_fim';
            $params .= "&data_fim={$dataFim}";
        } else {
            $where .= ' AND YEAR(data_realizacao) = YEAR(CURRENT_DATE()) AND MONTH(data_realizacao) = MONTH(CURRENT_DATE())';
        }

        $this->read = new Read();
        $this->read->FullRead(
            "SELECT COUNT(*) as total 
             FROM pareceres 
             {$where}",
            $params
        );
        $total = $this->read->getResult()[0]['total'] ?? 0;

        // Por tipo
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT tipo, COUNT(*) as total 
             FROM pareceres 
             {$where}
             GROUP BY tipo
             ORDER BY total DESC
             LIMIT 5",
            $params
        );
        $porTipo = $this->read->getResult() ?? [];

        // Por dia
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT DAY(data_realizacao) as dia, COUNT(*) as total 
             FROM pareceres 
             {$where}
             GROUP BY DAY(data_realizacao)
             ORDER BY dia ASC",
            $params
        );
        $porDia = $this->read->getResult() ?? [];

        return [
            'total' => (int)$total,
            'por_tipo' => $porTipo,
            'por_dia' => $porDia
        ];
    }

    /**
     * Retorna estatísticas de Agendamentos
     */
    public function getEstatisticasAgendamentos(int $empresa, string $dataInicio = '', string $dataFim = ''): array
    {
        $where = 'WHERE empresa = :empresa';
        $params = "empresa={$empresa}";

        if (!empty($dataInicio) && !empty($dataFim)) {
            $where .= ' AND DATE(data_agendamento) BETWEEN :data_inicio AND :data_fim';
            $params .= "&data_inicio={$dataInicio}&data_fim={$dataFim}";
        } elseif (!empty($dataInicio)) {
            $where .= ' AND DATE(data_agendamento) >= :data_inicio';
            $params .= "&data_inicio={$dataInicio}";
        } elseif (!empty($dataFim)) {
            $where .= ' AND DATE(data_agendamento) <= :data_fim';
            $params .= "&data_fim={$dataFim}";
        } else {
            $where .= ' AND YEAR(data_agendamento) = YEAR(CURRENT_DATE()) AND MONTH(data_agendamento) = MONTH(CURRENT_DATE())';
        }

        // Total por status
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT status, COUNT(*) as total 
             FROM agendamentos 
             {$where}
             GROUP BY status",
            $params
        );
        $porStatus = $this->read->getResult() ?? [];

        // Total geral
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT COUNT(*) as total 
             FROM agendamentos 
             {$where}",
            $params
        );
        $total = $this->read->getResult()[0]['total'] ?? 0;

        // Realizados
        $whereRealizados = $where . ' AND status = "Realizado"';
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT COUNT(*) as total 
             FROM agendamentos 
             {$whereRealizados}",
            $params
        );
        $realizados = $this->read->getResult()[0]['total'] ?? 0;

        return [
            'total' => (int)$total,
            'realizados' => (int)$realizados,
            'por_status' => $porStatus
        ];
    }

    /**
     * Retorna estatísticas Financeiras
     */
    public function getEstatisticasFinanceiro(int $empresa, string $dataInicio = '', string $dataFim = ''): array
    {
        $where = 'WHERE empresa = :empresa';
        $params = "empresa={$empresa}";

        if (!empty($dataInicio) && !empty($dataFim)) {
            $where .= ' AND DATE(data_emissao) BETWEEN :data_inicio AND :data_fim';
            $params .= "&data_inicio={$dataInicio}&data_fim={$dataFim}";
        } elseif (!empty($dataInicio)) {
            $where .= ' AND DATE(data_emissao) >= :data_inicio';
            $params .= "&data_inicio={$dataInicio}";
        } elseif (!empty($dataFim)) {
            $where .= ' AND DATE(data_emissao) <= :data_fim';
            $params .= "&data_fim={$dataFim}";
        } else {
            $where .= ' AND YEAR(data_emissao) = YEAR(CURRENT_DATE()) AND MONTH(data_emissao) = MONTH(CURRENT_DATE())';
        }

        // Total de contas a receber
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT 
                COUNT(*) as total,
                SUM(valor) as valor_total,
                SUM(CASE WHEN status = 'Pago' THEN valor ELSE 0 END) as valor_pago
             FROM contas_receber 
             {$where}",
            $params
        );
        $contasReceber = $this->read->getResult()[0] ?? ['total' => 0, 'valor_total' => 0, 'valor_pago' => 0];

        // Por status
        $this->read = new Read();
        $this->read->FullRead(
            "SELECT status, COUNT(*) as total, SUM(valor) as valor_total
             FROM contas_receber 
             {$where}
             GROUP BY status",
            $params
        );
        $porStatus = $this->read->getResult() ?? [];

        return [
            'total_contas' => (int)($contasReceber['total'] ?? 0),
            'valor_total' => (float)($contasReceber['valor_total'] ?? 0),
            'valor_pago' => (float)($contasReceber['valor_pago'] ?? 0),
            'por_status' => $porStatus
        ];
    }
}
