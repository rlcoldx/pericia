<?php

namespace Agencia\Close\Models\Home;

use Agencia\Close\Conn\Conn;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Models\Model;

class Home extends Model 
{
    public $byrestaurante;

    public function __construct()
    {
        if ($_SESSION['pericia_perfil_tipo'] == 2) {
            $this->byrestaurante = "WHERE id_restaurante = '" . addslashes($_SESSION['pericia_perfil_empresa']) . "' ";
        } else {
            $this->byrestaurante = "";
        }
    }

    private function buildFilterConditions($dataInicio = '', $dataFim = '', $restauranteId = '', $cidade = ''): string
    {
        $conditions = [];
        
        // Filtro por restaurante
        if (!empty($restauranteId)) {
            if (is_array($restauranteId) && count($restauranteId) > 0) {
                // Múltiplos restaurantes selecionados
                $restaurantes = array_map('addslashes', $restauranteId);
                $restaurantes = array_filter($restaurantes); // Remove valores vazios
                if (!empty($restaurantes)) {
                    $conditions[] = "r.id_restaurante IN ('" . implode("','", $restaurantes) . "')";
                }
            } else {
                // Um único restaurante (compatibilidade com versão anterior)
                $conditions[] = "r.id_restaurante = '" . addslashes($restauranteId) . "'";
            }
        } else {
            if ($_SESSION['pericia_perfil_tipo'] == 2) {
                $conditions[] = "r.id_restaurante = '" . addslashes($_SESSION['pericia_perfil_empresa']) . "'";
            }
        }
        
        // Filtro por cidade
        if (!empty($cidade)) {
            if (is_array($cidade) && count($cidade) > 0) {
                // Múltiplas cidades selecionadas
                $cidades = array_map(function($c) {
                    return ucwords(strtolower(trim($c)));
                }, $cidade);
                $cidades = array_filter($cidades); // Remove valores vazios
                
                if (!empty($cidades)) {
                    $cidadeConditions = [];
                    foreach ($cidades as $c) {
                        $cidadeConditions[] = "(FIND_IN_SET('" . addslashes($c) . "', u.cidade_atuacao) > 0 OR u.cidade_atuacao LIKE '%" . addslashes($c) . "%')";
                    }
                    $conditions[] = "(" . implode(" OR ", $cidadeConditions) . ")";
                }
            } else {
                // Uma única cidade (compatibilidade com versão anterior)
                $cidadeNormalizada = ucwords(strtolower(trim($cidade)));
                $conditions[] = "(FIND_IN_SET('" . addslashes($cidadeNormalizada) . "', u.cidade_atuacao) > 0 OR u.cidade_atuacao LIKE '%" . addslashes($cidadeNormalizada) . "%')";
            }
        }
        
        // Filtro por período
        if (!empty($dataInicio) && !empty($dataFim)) {
            $conditions[] = "DATE(r.date_create) BETWEEN '" . addslashes($dataInicio) . "' AND '" . addslashes($dataFim) . "'";
        } elseif (!empty($dataInicio)) {
            $conditions[] = "DATE(r.date_create) >= '" . addslashes($dataInicio) . "'";
        } elseif (!empty($dataFim)) {
            $conditions[] = "DATE(r.date_create) <= '" . addslashes($dataFim) . "'";
        }
        
        return !empty($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";
    }

    public function getTotalReservas($dataInicio = '', $dataFim = '', $restauranteId = '', $cidade = '', $regioes = []): Read
    {
        $filterConditions = $this->buildFilterConditions($dataInicio, $dataFim, $restauranteId, $cidade);
        $whereClause = !empty($filterConditions) ? $filterConditions : "";
        
        $this->read = new Read();
        $this->read->FullRead("SELECT 
        SUM(CASE WHEN p.pagamento_status = 'approved' THEN 1 ELSE 0 END) AS total_reservas_aprovadas,
        SUM(CASE WHEN p.pagamento_status != 'approved' AND p.pagamento_status != 'cancelled' AND r.status_reserva = 'Aceito' THEN 1 ELSE 0 END) AS total_reservas_nao_concluidas,
        SUM(CASE WHEN p.pagamento_status = 'cancelled' THEN 1 ELSE 0 END) AS total_reservas_recusadas
        FROM reservas r
        LEFT JOIN pagamentos p ON r.id = p.id_reserva
        LEFT JOIN usuarios u ON r.id_restaurante = u.id
        $whereClause");
        return $this->read;
    }

    public function getTotalValorMes($dataInicio = '', $dataFim = '', $restauranteId = '', $cidade = '', $regioes = []): Read
    {
        $filterConditions = $this->buildFilterConditions($dataInicio, $dataFim, $restauranteId, $cidade);
        $whereClause = !empty($filterConditions) ? $filterConditions . " AND" : "WHERE";
        $periodCondition = "";
        
        // Se não houver filtro de data específico, usar o mês atual
        if (empty($dataInicio) && empty($dataFim)) {
            $periodCondition = "AND MONTH(p.date_create) = MONTH(CURRENT_DATE()) AND YEAR(p.date_create) = YEAR(CURRENT_DATE())";
        } else {
            $periodCondition = "";
        }
        
        $this->read = new Read();
        $this->read->FullRead("SELECT 
            SUM(CAST(p.pagamento_valor AS DECIMAL(10, 2))) AS total_vendas
            FROM reservas r
            JOIN pagamentos p ON r.id = p.id_reserva
            LEFT JOIN usuarios u ON r.id_restaurante = u.id
            $whereClause p.pagamento_status = 'approved' 
            $periodCondition");
        return $this->read;
    }

    public function getMesasReservadas($dataInicio = '', $dataFim = '', $restauranteId = '', $cidade = '', $regioes = []): Read
    {
        $filterConditions = $this->buildFilterConditions($dataInicio, $dataFim, $restauranteId, $cidade);
        $whereClause = !empty($filterConditions) ? $filterConditions . " AND" : "WHERE";
        
        $this->read = new Read();
        $this->read->FullRead("SELECT 
            sub.total_reservas,
            s.nome AS nome_suite,
            MIN(sp.valor) AS menor_valor,
            img.imagem
        FROM 
            (SELECT r.id_suite, COUNT(r.id_suite) AS total_reservas
            FROM reservas r
            JOIN pagamentos p ON r.id = p.id_reserva
            LEFT JOIN usuarios u ON r.id_restaurante = u.id
            $whereClause p.pagamento_status = 'approved'
            GROUP BY r.id_suite) AS sub
        JOIN 
            suites s ON sub.id_suite = s.id
        JOIN 
            suites_precos sp ON sub.id_suite = sp.id_suite
        LEFT JOIN 
            suites_imagens img ON sub.id_suite = s.id
        GROUP BY 
            sub.id_suite, s.nome
        ORDER BY 
            sub.total_reservas DESC");
        return $this->read;
    }

    public function getTotalValor($dataInicio = '', $dataFim = '', $restauranteId = '', $cidade = '', $regioes = []): Read
    {
        $filterConditions = $this->buildFilterConditions($dataInicio, $dataFim, $restauranteId, $cidade);
        $whereClause = !empty($filterConditions) ? $filterConditions . " AND" : "WHERE";
        
        $this->read = new Read();
        $this->read->FullRead("SELECT 
            SUM(CAST(p.pagamento_valor AS DECIMAL(10, 2))) AS total_vendas
            FROM reservas r
            JOIN pagamentos p ON r.id = p.id_reserva
            LEFT JOIN usuarios u ON r.id_restaurante = u.id
            $whereClause p.pagamento_status = 'approved'");
        return $this->read;
    }

    public function getRegistrosPorDiaDaSemana($dataInicio = '', $dataFim = '', $restauranteId = '', $cidade = '', $regioes = []): array
    {
        // Array base para todos os dias da semana em português
        $diasSemana = [
            'Domingo' => 0,
            'Segunda-feira' => 0,
            'Terça-feira' => 0,
            'Quarta-feira' => 0,
            'Quinta-feira' => 0,
            'Sexta-feira' => 0,
            'Sábado' => 0
        ];

        $filterConditions = $this->buildFilterConditions($dataInicio, $dataFim, $restauranteId, $cidade);
        $whereClause = !empty($filterConditions) ? $filterConditions . " AND" : "WHERE";

        // Executa a consulta considerando apenas reservas pagas
        $this->read = new Read();
        $this->read->FullRead("
            SELECT DAYOFWEEK(r.date_create) AS dia_semana_num, COUNT(*) AS quantidade
            FROM reservas r
            JOIN pagamentos p ON r.id = p.id_reserva
            LEFT JOIN usuarios u ON r.id_restaurante = u.id
            $whereClause p.pagamento_status = 'approved'
            GROUP BY dia_semana_num
            ORDER BY dia_semana_num
        ");

        // Mapeamento de números para dias da semana em português
        $mapaDias = [
            1 => 'Domingo',
            2 => 'Segunda-feira',
            3 => 'Terça-feira',
            4 => 'Quarta-feira',
            5 => 'Quinta-feira',
            6 => 'Sexta-feira',
            7 => 'Sábado'
        ];

        // Obtém os resultados da consulta
        $result = $this->read->getResult();

        // Atualiza o array base com as quantidades retornadas pela consulta
        if ($result) {
            foreach ($result as $row) {
                $diaSemanaPort = $mapaDias[$row['dia_semana_num']];
                $diasSemana[$diaSemanaPort] = (int)$row['quantidade'];
            }
        }

        // Retorna os dados como um array de dias e quantidades
        $dados = [];
        foreach ($diasSemana as $dia => $quantidade) {
            $dados[] = [
                'dia_da_semana' => $dia,
                'quantidade' => $quantidade
            ];
        }

        return $dados;
    }

    public function getTotalReservasPorCidade($dataInicio = '', $dataFim = '', $restauranteId = '', $cidade = '', $regioes = []): Read
    {
        $filterConditions = $this->buildFilterConditions($dataInicio, $dataFim, $restauranteId, $cidade);
        $whereClause = !empty($filterConditions) ? $filterConditions . " AND" : "WHERE";
        
        $this->read = new Read();
        $this->read->FullRead("
            SELECT 
                TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(u.cidade_atuacao, ',', 1), ',', -1)) AS nome_cidade,
                COUNT(*) AS total_reservas
            FROM reservas r
            JOIN pagamentos p ON r.id = p.id_reserva
            LEFT JOIN usuarios u ON r.id_restaurante = u.id
            $whereClause p.pagamento_status = 'approved'
            AND u.cidade_atuacao IS NOT NULL 
            AND u.cidade_atuacao != ''
            GROUP BY nome_cidade
            ORDER BY total_reservas DESC
            LIMIT 10
        ");
        return $this->read;
    }

    public function getTop10Restaurantes($dataInicio = '', $dataFim = '', $restauranteId = '', $cidade = '', $regioes = []): Read
    {
        $filterConditions = $this->buildFilterConditions($dataInicio, $dataFim, $restauranteId, $cidade);
        $whereClause = !empty($filterConditions) ? $filterConditions . " AND" : "WHERE";
        
        $this->read = new Read();
        $this->read->FullRead("
            SELECT 
                u.nome AS nome_restaurante,
                TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(u.cidade_atuacao, ',', 1), ',', -1)) AS cidade,
                COUNT(*) AS total_reservas,
                SUM(CAST(p.pagamento_valor AS DECIMAL(10, 2))) AS valor_total
            FROM reservas r
            JOIN pagamentos p ON r.id = p.id_reserva
            LEFT JOIN usuarios u ON r.id_restaurante = u.id
            $whereClause p.pagamento_status = 'approved'
            GROUP BY u.id, u.nome, cidade
            ORDER BY total_reservas DESC
            LIMIT 10
        ");
        return $this->read;
    }

}