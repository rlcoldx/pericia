<?php

namespace Agencia\Close\Models\Financeiro;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class ContaReceber extends Model 
{
    protected Create $create;
    protected Read $read;
    protected Update $update;
    protected Delete $delete;
    private ?string $lastCreateError = null;

    public function __construct()
    {
        $this->create = new Create();
        $this->read = new Read();
        $this->update = new Update();
        $this->delete = new Delete();
    }

    /**
     * Lista todas as contas a receber da empresa
     */
    public function getContasReceber($company, $filtros = []): Read
    {
        $this->read = new Read();
        $where = "WHERE empresa = :empresa";
        $params = "empresa={$company}";

        if (isset($filtros['status']) && !empty($filtros['status'])) {
            $where .= " AND status = :status";
            $params .= "&status={$filtros['status']}";
        }

        if (isset($filtros['data_inicio']) && !empty($filtros['data_inicio'])) {
            $where .= " AND data_vencimento >= :data_inicio";
            $params .= "&data_inicio={$filtros['data_inicio']}";
        }

        if (isset($filtros['data_fim']) && !empty($filtros['data_fim'])) {
            $where .= " AND data_vencimento <= :data_fim";
            $params .= "&data_fim={$filtros['data_fim']}";
        }

        if (isset($filtros['cliente']) && !empty($filtros['cliente'])) {
            $where .= " AND (cliente_nome LIKE :cliente OR cliente_documento LIKE :cliente)";
            $params .= "&cliente=%{$filtros['cliente']}%";
        }

        $orderBy = "ORDER BY data_vencimento ASC, id DESC";
        
        $this->read->ExeRead("contas_receber", $where . " " . $orderBy, $params);
        return $this->read;
    }

    /**
     * Busca uma conta a receber por ID
     */
    public function getContaReceber($id, $company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("contas_receber", 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Cria uma nova conta a receber
     *
     * @return bool|Create true/id on success path via getResult; bool for compatibility
     */
    public function criar($data): bool
    {
        try {
            $this->ensureContasReceberSchema();

            // Se tiver agendamento_id, busca dados do agendamento para preencher campos
            if (!empty($data['agendamento_id'])) {
                $agendamento = $this->getAgendamentoData($data['agendamento_id'], $data['empresa']);
                if ($agendamento) {
                    if (empty($data['local_pericia']) && !empty($agendamento['local_pericia'])) {
                        $data['local_pericia'] = $agendamento['local_pericia'];
                    }
                    if (empty($data['reclamante_nome']) && !empty($agendamento['reclamante_nome'])) {
                        $data['reclamante_nome'] = $agendamento['reclamante_nome'];
                    }
                    if (empty($data['numero_processo']) && !empty($agendamento['numero_processo'])) {
                        $data['numero_processo'] = $agendamento['numero_processo'];
                    }
                    if (empty($data['data_pericia']) && !empty($agendamento['data_realizada'])) {
                        $data['data_pericia'] = $agendamento['data_realizada'];
                    }
                    if (empty($data['assistente_nome']) && !empty($agendamento['assistente_nome'])) {
                        $data['assistente_nome'] = $agendamento['assistente_nome'];
                    }
                    if (empty($data['valor_assistente']) && !empty($agendamento['valor_pago_assistente'])) {
                        $data['valor_assistente'] = $agendamento['valor_pago_assistente'];
                    }
                    if (empty($data['numero_nota_fiscal']) && !empty($agendamento['numero_nota_fiscal'])) {
                        $data['numero_nota_fiscal'] = $agendamento['numero_nota_fiscal'];
                    }
                    if (empty($data['numero_boleto']) && !empty($agendamento['numero_boleto'])) {
                        $data['numero_boleto'] = $agendamento['numero_boleto'];
                    }
                    if (empty($data['numero_pedido_cliente']) && !empty($agendamento['numero_pedido_cliente'])) {
                        $data['numero_pedido_cliente'] = $agendamento['numero_pedido_cliente'];
                    }
                    if (empty($data['data_envio']) && !empty($agendamento['data_envio_financeiro'])) {
                        $data['data_envio'] = $agendamento['data_envio_financeiro'];
                    }
                    if (empty($data['data_vencimento']) && !empty($agendamento['data_vencimento_financeiro'])) {
                        $data['data_vencimento'] = $agendamento['data_vencimento_financeiro'];
                    }
                    if (empty($data['cliente_nome']) && !empty($agendamento['cliente_nome'])) {
                        $data['cliente_nome'] = $agendamento['cliente_nome'];
                    }
                    if (empty($data['cliente_documento']) && !empty($agendamento['cliente_cpf'])) {
                        $data['cliente_documento'] = $agendamento['cliente_cpf'];
                    }
                    if (empty($data['valor_total']) && !empty($agendamento['valor_pericia_cobrado'])) {
                        $data['valor_total'] = $agendamento['valor_pericia_cobrado'];
                    }
                    if (empty($data['tipo_pericia']) && !empty($agendamento['tipo_pericia'])) {
                        $codigo = mb_strtoupper(trim((string) $agendamento['tipo_pericia']), 'UTF-8');
                        $mapa = [
                            'MEDICA' => 'Perícia Médica',
                            'MÉDICA' => 'Perícia Médica',
                            'MEDIACA' => 'Perícia Médica',
                            'TECNICA' => 'Técnica',
                            'TÉCNICA' => 'Técnica',
                            'ERGONO' => 'Ergonomia',
                            'ERGONOMIA' => 'Ergonomia',
                            'CINESIO' => 'Fisiologia',
                            'FISIOLOGIA' => 'Fisiologia',
                            'QUESITOS' => 'Quesitos',
                            'QUESITO' => 'Quesitos',
                        ];
                        if (isset($mapa[$codigo])) {
                            $data['tipo_pericia'] = $mapa[$codigo];
                        }
                    }
                } else {
                    // Agendamento inválido/inexistente: não quebra o cadastro — remove o vínculo
                    $data['agendamento_id'] = null;
                }
            }

            if (empty($data['etapa'])) {
                $data['etapa'] = 'PERICIA';
            }

            foreach (['data_vencimento', 'data_emissao', 'data_pericia', 'data_envio', 'data_situacao'] as $campoData) {
                if (array_key_exists($campoData, $data) && ($data[$campoData] === '' || $data[$campoData] === false)) {
                    $data[$campoData] = null;
                }
            }

            if (empty($data['data_vencimento'])) {
                $this->lastCreateError = 'Data de vencimento é obrigatória.';
                return false;
            }

            $valorPendente = (float) $data['valor_total'] - (float) ($data['valor_recebido'] ?? 0);
            $data['valor_pendente'] = $valorPendente;

            if (!isset($data['status']) || $data['status'] === null || $data['status'] === '') {
                if ($valorPendente <= 0) {
                    $data['status'] = 'Recebido';
                } elseif ((float) ($data['valor_recebido'] ?? 0) > 0) {
                    $data['status'] = 'Parcial';
                } else {
                    $data['status'] = 'Pendente';
                }
            }

            // Só envia colunas que existem na tabela (evita Unknown column)
            $data = $this->filtrarColunasExistentes('contas_receber', $data);
            if ($data === []) {
                $this->lastCreateError = 'Tabela contas_receber sem colunas utilizáveis.';
                return false;
            }

            $this->create = new Create();
            $this->create->ExeCreate('contas_receber', $data);
            $ok = (bool) $this->create->getResult();
            if (!$ok) {
                $this->lastCreateError = $this->create->getLastError()
                    ?? (($this->create->getErrorInfo()[2] ?? null) ?: 'Falha ao inserir no banco.');
            } else {
                $this->lastCreateError = null;
                $novoId = (int) $this->create->getResult();
                if ($novoId > 0 && (float) ($data['valor_recebido'] ?? 0) > 0) {
                    try {
                        (new PagamentoRecebimento())->sincronizarDaContaReceber($novoId, (int) ($data['empresa'] ?? 0), $data);
                    } catch (\Throwable $e) {
                        // não bloqueia o cadastro da conta
                    }
                }
            }

            return $ok;
        } catch (\Throwable $e) {
            $this->lastCreateError = $e->getMessage();
            return false;
        }
    }

    public function getLastCreateError(): ?string
    {
        return $this->lastCreateError;
    }

    private function ensureContasReceberSchema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $conn = (new class extends \Agencia\Close\Conn\Conn {
                public function pdo()
                {
                    return $this->getConn();
                }
            })->pdo();

            $needed = [
                'etapa' => "ALTER TABLE `contas_receber` ADD COLUMN `etapa` varchar(50) DEFAULT 'PERICIA'",
                'situacao' => "ALTER TABLE `contas_receber` ADD COLUMN `situacao` varchar(255) DEFAULT NULL",
                'data_situacao' => "ALTER TABLE `contas_receber` ADD COLUMN `data_situacao` date DEFAULT NULL",
                'local_pericia' => "ALTER TABLE `contas_receber` ADD COLUMN `local_pericia` varchar(500) DEFAULT NULL",
                'reclamante_nome' => "ALTER TABLE `contas_receber` ADD COLUMN `reclamante_nome` varchar(255) DEFAULT NULL",
                'numero_processo' => "ALTER TABLE `contas_receber` ADD COLUMN `numero_processo` varchar(255) DEFAULT NULL",
                'data_pericia' => "ALTER TABLE `contas_receber` ADD COLUMN `data_pericia` date DEFAULT NULL",
                'assistente_nome' => "ALTER TABLE `contas_receber` ADD COLUMN `assistente_nome` varchar(255) DEFAULT NULL",
                'valor_assistente' => "ALTER TABLE `contas_receber` ADD COLUMN `valor_assistente` decimal(10,2) DEFAULT NULL",
                'data_envio' => "ALTER TABLE `contas_receber` ADD COLUMN `data_envio` date DEFAULT NULL",
                'numero_pedido_cliente' => "ALTER TABLE `contas_receber` ADD COLUMN `numero_pedido_cliente` varchar(255) DEFAULT NULL",
                'tipo_pericia' => "ALTER TABLE `contas_receber` ADD COLUMN `tipo_pericia` varchar(80) DEFAULT NULL",
            ];

            // Só ADD COLUMN (rápido). Nunca MODIFY no request — trava/timeout → "Failed to fetch"
            foreach ($needed as $col => $sql) {
                try {
                    $check = $conn->query("SHOW COLUMNS FROM `contas_receber` LIKE " . $conn->quote($col));
                    if ($check && !$check->fetch()) {
                        $conn->exec($sql);
                    }
                } catch (\Throwable $e) {
                    // coluna já existe ou sem permissão
                }
            }

            // ENUM tipo/status → VARCHAR (só se ainda for ENUM; evita "Data truncated" em Perícia/Serviço)
            foreach ([
                'tipo' => "ALTER TABLE `contas_receber` MODIFY COLUMN `tipo` varchar(80) NULL DEFAULT 'Perícia'",
                'status' => "ALTER TABLE `contas_receber` MODIFY COLUMN `status` varchar(40) NULL DEFAULT 'Pendente'",
            ] as $col => $sql) {
                try {
                    $check = $conn->query("SHOW COLUMNS FROM `contas_receber` LIKE " . $conn->quote($col));
                    $row = $check ? $check->fetch(\PDO::FETCH_ASSOC) : false;
                    $type = strtolower((string) ($row['Type'] ?? ''));
                    if ($row && str_starts_with($type, 'enum')) {
                        $conn->exec($sql);
                    }
                } catch (\Throwable $e) {
                }
            }
        } catch (\Throwable $e) {
            // sem permissão ALTER — segue; filtrarColunasExistentes evita Unknown column
        }
    }

    private function filtrarColunasExistentes(string $table, array $data): array
    {
        try {
            $conn = (new class extends \Agencia\Close\Conn\Conn {
                public function pdo()
                {
                    return $this->getConn();
                }
            })->pdo();
            $cols = [];
            $stmt = $conn->query('SHOW COLUMNS FROM `' . str_replace('`', '', $table) . '`');
            if ($stmt) {
                foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                    $cols[$row['Field']] = true;
                }
            }
            if ($cols === []) {
                return $data;
            }
            $filtered = [];
            foreach ($data as $k => $v) {
                if (isset($cols[$k])) {
                    $filtered[$k] = $v;
                }
            }
            return $filtered;
        } catch (\Throwable $e) {
            return $data;
        }
    }
    
    /**
     * Busca dados do agendamento
     */
    private function getAgendamentoData($agendamentoId, $company): ?array
    {
        if (empty($agendamentoId)) {
            return null;
        }
        
        $this->read = new Read();
        $this->read->ExeRead("agendamentos", 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$agendamentoId}&empresa={$company}"
        );
        $result = $this->read->getResult();
        return $result[0] ?? null;
    }
    
    /**
     * Sincroniza dados do agendamento com a conta a receber
     */
    public function sincronizarComAgendamento($contaId, $company): bool
    {
        $conta = $this->getContaReceber($contaId, $company);
        $contaData = $conta->getResult()[0] ?? null;
        
        if (!$contaData || empty($contaData['agendamento_id'])) {
            return false;
        }
        
        $agendamento = $this->getAgendamentoData($contaData['agendamento_id'], $company);
        if (!$agendamento) {
            return false;
        }
        
        $data = [];
        
        // Atualiza campos se estiverem vazios ou se o agendamento tiver dados mais recentes
        if (empty($contaData['local_pericia']) && !empty($agendamento['local_pericia'])) {
            $data['local_pericia'] = $agendamento['local_pericia'];
        }
        if (empty($contaData['reclamante_nome']) && !empty($agendamento['reclamante_nome'])) {
            $data['reclamante_nome'] = $agendamento['reclamante_nome'];
        }
        if (empty($contaData['numero_processo']) && !empty($agendamento['numero_processo'])) {
            $data['numero_processo'] = $agendamento['numero_processo'];
        }
        if (empty($contaData['data_pericia']) && !empty($agendamento['data_realizada'])) {
            $data['data_pericia'] = $agendamento['data_realizada'];
        }
        if (empty($contaData['assistente_nome']) && !empty($agendamento['assistente_nome'])) {
            $data['assistente_nome'] = $agendamento['assistente_nome'];
        }
        if (empty($contaData['valor_assistente']) && !empty($agendamento['valor_pago_assistente'])) {
            $data['valor_assistente'] = $agendamento['valor_pago_assistente'];
        }
        if (empty($contaData['numero_nota_fiscal']) && !empty($agendamento['numero_nota_fiscal'])) {
            $data['numero_nota_fiscal'] = $agendamento['numero_nota_fiscal'];
        }
        if (empty($contaData['numero_boleto']) && !empty($agendamento['numero_boleto'])) {
            $data['numero_boleto'] = $agendamento['numero_boleto'];
        }
        if (empty($contaData['data_envio']) && !empty($agendamento['data_envio_financeiro'])) {
            $data['data_envio'] = $agendamento['data_envio_financeiro'];
        }
        if (empty($contaData['data_vencimento']) && !empty($agendamento['data_vencimento_financeiro'])) {
            $data['data_vencimento'] = $agendamento['data_vencimento_financeiro'];
        }
        
        if (!empty($data)) {
            return $this->atualizar($contaId, $company, $data);
        }
        
        return true;
    }

    /**
     * Atualiza uma conta a receber
     */
    public function atualizar($id, $company, $data): bool
    {
        foreach (['data_vencimento', 'data_emissao', 'data_pericia', 'data_envio', 'data_situacao'] as $campoData) {
            if (array_key_exists($campoData, $data) && ($data[$campoData] === '' || $data[$campoData] === false)) {
                $data[$campoData] = null;
            }
        }

        // Recalcula valor pendente
        $valorTotal = $data['valor_total'] ?? 0;
        $valorRecebido = $data['valor_recebido'] ?? 0;
        $valorPendente = $valorTotal - $valorRecebido;
        $data['valor_pendente'] = $valorPendente;

        // Atualiza status
        if ($valorPendente <= 0) {
            $data['status'] = 'Recebido';
        } elseif ($valorRecebido > 0) {
            $data['status'] = 'Parcial';
        } elseif (!empty($data['data_vencimento']) && strtotime($data['data_vencimento']) < strtotime('today')) {
            $data['status'] = 'Vencido';
        } else {
            $data['status'] = 'Pendente';
        }

        $this->update = new Update();
        $this->update->ExeUpdate("contas_receber", $data, "WHERE id = :id AND empresa = :empresa", "id={$id}&empresa={$company}");
        $ok = (bool) $this->update->getResult();

        if ($ok && (float) ($data['valor_recebido'] ?? 0) > 0) {
            try {
                $contaSync = $data;
                $contaSync['id'] = $id;
                // Garante campos usados no sync
                if (!isset($contaSync['descricao']) || !isset($contaSync['agendamento_id'])) {
                    $atual = $this->getContaReceber($id, $company)->getResult()[0] ?? [];
                    $contaSync = array_merge($atual, $contaSync);
                }
                (new PagamentoRecebimento())->sincronizarDaContaReceber((int) $id, (int) $company, $contaSync);
            } catch (\Throwable $e) {
            }
        }

        return $ok;
    }

    /**
     * Remove uma conta a receber
     */
    public function remover($id, $company): bool
    {
        $this->delete->ExeDelete("contas_receber", "WHERE id = :id AND empresa = :empresa", "id={$id}&empresa={$company}");
        return $this->delete->getResult();
    }

    /**
     * Busca contas a receber para DataTable (AJAX)
     */
    public function getContasReceberDataTable(int $company, array $filtros = []): array
    {
        $where = "WHERE cr.empresa = :empresa";
        $params = "empresa={$company}";

        // Filtros
        if (!empty($filtros['status'])) {
            $where .= " AND cr.status = :status";
            $params .= "&status={$filtros['status']}";
        }
        if (!empty($filtros['situacao'])) {
            $where .= " AND cr.situacao LIKE :situacao";
            $params .= "&situacao=%{$filtros['situacao']}%";
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND cr.data_vencimento >= :data_inicio";
            $params .= "&data_inicio={$filtros['data_inicio']}";
        }
        if (!empty($filtros['data_fim'])) {
            $where .= " AND cr.data_vencimento <= :data_fim";
            $params .= "&data_fim={$filtros['data_fim']}";
        }
        if (!empty($filtros['cliente'])) {
            $where .= " AND (cr.cliente_nome LIKE :cliente OR cr.cliente_documento LIKE :cliente OR cr.reclamante_nome LIKE :cliente)";
            $params .= "&cliente=%{$filtros['cliente']}%";
        }
        if (!empty($filtros['numero_processo'])) {
            $where .= " AND cr.numero_processo LIKE :numero_processo";
            $params .= "&numero_processo=%{$filtros['numero_processo']}%";
        }
        if (!empty($filtros['local'])) {
            $where .= " AND cr.local_pericia LIKE :local";
            $params .= "&local=%{$filtros['local']}%";
        }

        // Busca com JOIN para pegar dados do agendamento (usando campos cache ou do agendamento)
        $query = "SELECT cr.*, 
                         COALESCE(cr.tipo, a.tipo_pericia) as tipo_pericia,
                         COALESCE(cr.numero_processo, a.numero_processo) as numero_processo_completo,
                         COALESCE(cr.local_pericia, a.local_pericia) as local_pericia_completo,
                         COALESCE(cr.reclamante_nome, a.reclamante_nome) as reclamante_nome_completo,
                         COALESCE(cr.data_pericia, a.data_realizada) as data_pericia_completo,
                         COALESCE(cr.assistente_nome, a.assistente_nome) as assistente_nome_completo,
                         COALESCE(cr.valor_assistente, a.valor_pago_assistente) as valor_assistente_completo,
                         COALESCE(NULLIF(cr.numero_pedido_cliente, ''), a.numero_pedido_cliente) as numero_pedido_export,
                         a.data_envio_financeiro,
                         a.data_vencimento_financeiro,
                         a.status_pagamento as status_pagamento_agendamento,
                         a.tipo_pericia as agendamento_tipo_pericia
                  FROM contas_receber cr
                  LEFT JOIN agendamentos a ON cr.agendamento_id = a.id AND cr.empresa = a.empresa
                  {$where}
                  ORDER BY cr.data_vencimento ASC, cr.id DESC";

        $this->read->FullRead($query, $params);
        return $this->read->getResult() ?? [];
    }

    /**
     * Conta contas por status
     */
    public function contarPorStatus($company): Read
    {
        $this->read = new Read();
        $query = "SELECT status, COUNT(*) as total 
                  FROM contas_receber 
                  WHERE empresa = :empresa 
                  GROUP BY status";
        $this->read->FullRead($query, "empresa={$company}");
        return $this->read;
    }

    /**
     * Calcula totais financeiros
     */
    public function getTotaisFinanceiros($company): array
    {
        $this->read = new Read();
        $query = "SELECT 
                    SUM(valor_total) as total_a_receber,
                    SUM(valor_recebido) as total_recebido,
                    SUM(valor_pendente) as total_pendente
                  FROM contas_receber 
                  WHERE empresa = :empresa AND status != 'Cancelado'";
        $this->read->FullRead($query, "empresa={$company}");
        $result = $this->read->getResult();
        return $result[0] ?? ['total_a_receber' => 0, 'total_recebido' => 0, 'total_pendente' => 0];
    }
    
    /**
     * Busca dados para fluxo de caixa
     */
    public function getFluxoCaixa($company, $dataInicio = null, $dataFim = null): array
    {
        $where = "WHERE cr.empresa = :empresa";
        $params = "empresa={$company}";
        
        if ($dataInicio) {
            $where .= " AND cr.data_vencimento >= :data_inicio";
            $params .= "&data_inicio={$dataInicio}";
        }
        if ($dataFim) {
            $where .= " AND cr.data_vencimento <= :data_fim";
            $params .= "&data_fim={$dataFim}";
        }
        
        // Entradas (recebimentos confirmados - da tabela contas_receber)
        $this->read = new Read();
        $queryEntradas = "SELECT 
                            SUM(cr.valor_recebido) as total_entradas,
                            COUNT(*) as qtd_recebimentos
                          FROM contas_receber cr
                          {$where} 
                          AND cr.status IN ('Recebido', 'Parcial')
                          AND cr.valor_recebido > 0";
        $this->read->FullRead($queryEntradas, $params);
        $entradasContas = $this->read->getResult()[0] ?? ['total_entradas' => 0, 'qtd_recebimentos' => 0];
        
        // Entradas (recebimentos da tabela recebimentos)
        $whereRecebimentos = "WHERE r.empresa = :empresa";
        $paramsRecebimentos = "empresa={$company}";
        if ($dataInicio) {
            $whereRecebimentos .= " AND r.data_recebimento >= :data_inicio";
            $paramsRecebimentos .= "&data_inicio={$dataInicio}";
        }
        if ($dataFim) {
            $whereRecebimentos .= " AND r.data_recebimento <= :data_fim";
            $paramsRecebimentos .= "&data_fim={$dataFim}";
        }
        
        $this->read = new Read();
        $queryRecebimentos = "SELECT 
                                SUM(r.valor) as total_recebimentos,
                                COUNT(*) as qtd_recebimentos_tabela
                              FROM recebimentos r
                              {$whereRecebimentos}
                              AND r.status = 'Confirmado'";
        $this->read->FullRead($queryRecebimentos, $paramsRecebimentos);
        $recebimentosTabela = $this->read->getResult()[0] ?? ['total_recebimentos' => 0, 'qtd_recebimentos_tabela' => 0];
        
        $totalEntradas = ($entradasContas['total_entradas'] ?? 0) + ($recebimentosTabela['total_recebimentos'] ?? 0);
        $qtdEntradas = ($entradasContas['qtd_recebimentos'] ?? 0) + ($recebimentosTabela['qtd_recebimentos_tabela'] ?? 0);
        
        // Saídas (pagamentos de assistentes - da tabela contas_receber)
        $this->read = new Read();
        $querySaidas = "SELECT 
                          SUM(cr.valor_assistente) as total_saidas,
                          COUNT(*) as qtd_pagamentos
                        FROM contas_receber cr
                        {$where}
                        AND cr.valor_assistente > 0";
        $this->read->FullRead($querySaidas, $params);
        $saidasContas = $this->read->getResult()[0] ?? ['total_saidas' => 0, 'qtd_pagamentos' => 0];
        
        // Saídas (pagamentos da tabela pagamentos)
        $wherePagamentos = "WHERE p.empresa = :empresa";
        $paramsPagamentos = "empresa={$company}";
        if ($dataInicio) {
            $wherePagamentos .= " AND p.data_pagamento >= :data_inicio";
            $paramsPagamentos .= "&data_inicio={$dataInicio}";
        }
        if ($dataFim) {
            $wherePagamentos .= " AND p.data_pagamento <= :data_fim";
            $paramsPagamentos .= "&data_fim={$dataFim}";
        }
        
        $this->read = new Read();
        $queryPagamentos = "SELECT 
                              SUM(p.valor) as total_pagamentos,
                              COUNT(*) as qtd_pagamentos_tabela
                            FROM pagamentos p
                            {$wherePagamentos}
                            AND p.status = 'Pago'";
        $this->read->FullRead($queryPagamentos, $paramsPagamentos);
        $pagamentosTabela = $this->read->getResult()[0] ?? ['total_pagamentos' => 0, 'qtd_pagamentos_tabela' => 0];
        
        $totalSaidas = ($saidasContas['total_saidas'] ?? 0) + ($pagamentosTabela['total_pagamentos'] ?? 0);
        $qtdSaidas = ($saidasContas['qtd_pagamentos'] ?? 0) + ($pagamentosTabela['qtd_pagamentos_tabela'] ?? 0);
        
        // Previsão de entradas (contas a receber)
        $this->read = new Read();
        $queryPrevisao = "SELECT 
                            SUM(cr.valor_pendente) as total_previsao,
                            COUNT(*) as qtd_contas
                          FROM contas_receber cr
                          {$where}
                          AND cr.status IN ('Pendente', 'Parcial')
                          AND cr.valor_pendente > 0";
        $this->read->FullRead($queryPrevisao, $params);
        $previsao = $this->read->getResult()[0] ?? ['total_previsao' => 0, 'qtd_contas' => 0];
        
        $saldo = $totalEntradas - $totalSaidas;
        
        return [
            'entradas' => [
                'total' => $totalEntradas,
                'quantidade' => $qtdEntradas
            ],
            'saidas' => [
                'total' => $totalSaidas,
                'quantidade' => $qtdSaidas
            ],
            'previsao_entradas' => [
                'total' => $previsao['total_previsao'] ?? 0,
                'quantidade' => $previsao['qtd_contas'] ?? 0
            ],
            'saldo' => $saldo
        ];
    }
    
    /**
     * Busca dados para controle de inadimplência
     */
    public function getInadimplencia($company): array
    {
        $hoje = date('Y-m-d');
        $proximos7Dias = date('Y-m-d', strtotime('+7 days'));
        $proximos30Dias = date('Y-m-d', strtotime('+30 days'));
        
        // Contas vencidas
        $this->read = new Read();
        $queryVencidas = "SELECT 
                           COUNT(*) as quantidade,
                           SUM(valor_pendente) as valor_total
                         FROM contas_receber
                         WHERE empresa = :empresa
                         AND status IN ('Pendente', 'Parcial', 'Vencido')
                         AND data_vencimento < :hoje
                         AND valor_pendente > 0";
        $this->read->FullRead($queryVencidas, "empresa={$company}&hoje={$hoje}");
        $vencidas = $this->read->getResult()[0] ?? ['quantidade' => 0, 'valor_total' => 0];
        
        // Contas a vencer em 7 dias
        $this->read = new Read();
        $query7Dias = "SELECT 
                         COUNT(*) as quantidade,
                         SUM(valor_pendente) as valor_total
                       FROM contas_receber
                       WHERE empresa = :empresa
                       AND status IN ('Pendente', 'Parcial')
                       AND data_vencimento BETWEEN :hoje AND :proximos7dias
                       AND valor_pendente > 0";
        $this->read->FullRead($query7Dias, "empresa={$company}&hoje={$hoje}&proximos7dias={$proximos7Dias}");
        $proximos7DiasData = $this->read->getResult()[0] ?? ['quantidade' => 0, 'valor_total' => 0];
        
        // Contas a vencer em 30 dias
        $this->read = new Read();
        $query30Dias = "SELECT 
                          COUNT(*) as quantidade,
                          SUM(valor_pendente) as valor_total
                        FROM contas_receber
                        WHERE empresa = :empresa
                        AND status IN ('Pendente', 'Parcial')
                        AND data_vencimento BETWEEN :hoje AND :proximos30dias
                        AND valor_pendente > 0";
        $this->read->FullRead($query30Dias, "empresa={$company}&hoje={$hoje}&proximos30dias={$proximos30Dias}");
        $proximos30DiasData = $this->read->getResult()[0] ?? ['quantidade' => 0, 'valor_total' => 0];
        
        // Lista de contas vencidas
        $this->read = new Read();
        $queryListaVencidas = "SELECT 
                                 cr.*,
                                 DATEDIFF(:hoje, cr.data_vencimento) as dias_vencido
                               FROM contas_receber cr
                               WHERE cr.empresa = :empresa
                               AND cr.status IN ('Pendente', 'Parcial', 'Vencido')
                               AND cr.data_vencimento < :hoje
                               AND cr.valor_pendente > 0
                               ORDER BY cr.data_vencimento ASC
                               LIMIT 10";
        $this->read->FullRead($queryListaVencidas, "empresa={$company}&hoje={$hoje}");
        $listaVencidas = $this->read->getResult() ?? [];
        
        return [
            'vencidas' => [
                'quantidade' => $vencidas['quantidade'] ?? 0,
                'valor_total' => $vencidas['valor_total'] ?? 0
            ],
            'vencer_7_dias' => [
                'quantidade' => $proximos7DiasData['quantidade'] ?? 0,
                'valor_total' => $proximos7DiasData['valor_total'] ?? 0
            ],
            'vencer_30_dias' => [
                'quantidade' => $proximos30DiasData['quantidade'] ?? 0,
                'valor_total' => $proximos30DiasData['valor_total'] ?? 0
            ],
            'lista_vencidas' => $listaVencidas
        ];
    }
}

