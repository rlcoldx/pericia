<?php

namespace Agencia\Close\Models\Financeiro;

use Agencia\Close\Conn\Create;
use Agencia\Close\Conn\Read;
use Agencia\Close\Conn\Update;
use Agencia\Close\Conn\Delete;
use Agencia\Close\Models\Model;

class PagamentoRecebimento extends Model 
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
     * Lista todos os recebimentos da empresa
     */
    public function getRecebimentos($company, $filtros = []): Read
    {
        $this->read = new Read();
        $where = "WHERE empresa = :empresa";
        $params = "empresa={$company}";

        if (isset($filtros['status']) && !empty($filtros['status'])) {
            $where .= " AND status = :status";
            $params .= "&status={$filtros['status']}";
        }

        if (isset($filtros['data_inicio']) && !empty($filtros['data_inicio'])) {
            $where .= " AND data_recebimento >= :data_inicio";
            $params .= "&data_inicio={$filtros['data_inicio']}";
        }

        if (isset($filtros['data_fim']) && !empty($filtros['data_fim'])) {
            $where .= " AND data_recebimento <= :data_fim";
            $params .= "&data_fim={$filtros['data_fim']}";
        }

        $orderBy = "ORDER BY data_recebimento DESC, id DESC";
        
        $this->read->ExeRead("recebimentos", $where . " " . $orderBy, $params);
        return $this->read;
    }

    /**
     * Lista todos os pagamentos da empresa
     */
    public function getPagamentos($company, $filtros = []): Read
    {
        $this->read = new Read();
        $where = "WHERE empresa = :empresa";
        $params = "empresa={$company}";

        if (isset($filtros['status']) && !empty($filtros['status'])) {
            $where .= " AND status = :status";
            $params .= "&status={$filtros['status']}";
        }

        if (isset($filtros['tipo']) && !empty($filtros['tipo'])) {
            $where .= " AND tipo = :tipo";
            $params .= "&tipo={$filtros['tipo']}";
        }

        if (isset($filtros['data_inicio']) && !empty($filtros['data_inicio'])) {
            $where .= " AND data_pagamento >= :data_inicio";
            $params .= "&data_inicio={$filtros['data_inicio']}";
        }

        if (isset($filtros['data_fim']) && !empty($filtros['data_fim'])) {
            $where .= " AND data_pagamento <= :data_fim";
            $params .= "&data_fim={$filtros['data_fim']}";
        }

        $orderBy = "ORDER BY data_pagamento DESC, id DESC";
        
        $this->read->ExeRead("pagamentos", $where . " " . $orderBy, $params);
        return $this->read;
    }

    /**
     * Busca um recebimento por ID
     */
    public function getRecebimento($id, $company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("recebimentos", 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Busca um pagamento por ID
     */
    public function getPagamento($id, $company): Read
    {
        $this->read = new Read();
        $this->read->ExeRead("pagamentos", 
            "WHERE id = :id AND empresa = :empresa", 
            "id={$id}&empresa={$company}"
        );
        return $this->read;
    }

    /**
     * Cria um novo recebimento
     * @param bool $atualizarConta Se false, não altera valor_recebido da conta (já veio dela)
     */
    public function criarRecebimento($data, bool $atualizarConta = true): bool
    {
        $this->ensureRecebimentosSchema();

        // Normaliza forma_pagamento para valores sem acento problemático no ENUM legado
        if (!empty($data['forma_pagamento'])) {
            $fp = (string) $data['forma_pagamento'];
            if ($fp === 'Transferência' || $fp === 'TransferÃªncia') {
                $data['forma_pagamento'] = 'PIX';
            }
        } else {
            $data['forma_pagamento'] = 'PIX';
        }

        $this->create = new Create();
        $this->create->ExeCreate("recebimentos", $data);
        $ok = (bool) $this->create->getResult();

        if ($ok && $atualizarConta && !empty($data['conta_receber_id'])) {
            $this->atualizarContaReceberAposRecebimento(
                $data['conta_receber_id'],
                $data['valor'],
                $data['empresa']
            );
        }

        return $ok;
    }

    /**
     * Garante um recebimento na tabela `recebimentos` quando a conta já tem valor_recebido.
     * (Contas marcadas como Recebido no formulário de NF não criavam linha em Recebimentos.)
     */
    public function sincronizarDaContaReceber(int $contaId, int $empresa, array $conta): bool
    {
        if ($contaId <= 0 || $empresa <= 0) {
            return false;
        }

        $valorRecebido = (float) ($conta['valor_recebido'] ?? 0);
        if ($valorRecebido <= 0) {
            return false;
        }

        $this->read = new Read();
        $this->read->ExeRead(
            'recebimentos',
            'WHERE conta_receber_id = :cid AND empresa = :empresa AND status <> :cancelado LIMIT 1',
            "cid={$contaId}&empresa={$empresa}&cancelado=Cancelado"
        );
        $existente = $this->read->getResult()[0] ?? null;

        $dataRecebimento = $conta['data_envio']
            ?? $conta['data_emissao']
            ?? $conta['data_vencimento']
            ?? date('Y-m-d');
        if (empty($dataRecebimento) || $dataRecebimento === false) {
            $dataRecebimento = date('Y-m-d');
        }

        $descricao = trim((string) ($conta['descricao'] ?? ''));
        if ($descricao === '') {
            $descricao = 'Recebimento da conta #' . $contaId;
        }

        if ($existente) {
            // Mantém o registro visível na listagem; ajusta valor se divergir
            if ((float) ($existente['valor'] ?? 0) !== $valorRecebido) {
                $this->update = new Update();
                $this->update->ExeUpdate(
                    'recebimentos',
                    [
                        'valor' => $valorRecebido,
                        'descricao' => $descricao,
                        'data_recebimento' => $dataRecebimento,
                    ],
                    'WHERE id = :id AND empresa = :empresa',
                    'id=' . (int) $existente['id'] . '&empresa=' . $empresa
                );
            }

            return true;
        }

        return $this->criarRecebimento([
            'empresa' => $empresa,
            'conta_receber_id' => $contaId,
            'agendamento_id' => !empty($conta['agendamento_id']) ? (int) $conta['agendamento_id'] : null,
            'descricao' => $descricao,
            'valor' => $valorRecebido,
            'data_recebimento' => $dataRecebimento,
            // PIX evita ENUM legado com acento corrompido (Transferência)
            'forma_pagamento' => 'PIX',
            'status' => 'Confirmado',
            'numero_comprovante' => $conta['numero_nota_fiscal'] ?? null,
            'observacoes' => 'Gerado automaticamente a partir da Conta a Receber',
        ], false);
    }

    /**
     * Cria recebimentos faltantes para contas já marcadas como recebidas/parciais.
     */
    public function backfillRecebimentosFromContas(int $empresa): int
    {
        if ($empresa <= 0) {
            return 0;
        }

        $this->ensureRecebimentosSchema();

        $this->read = new Read();
        $this->read->FullRead(
            "SELECT cr.*
             FROM contas_receber cr
             WHERE cr.empresa = :empresa
               AND (
                    cr.valor_recebido > 0
                    OR cr.status IN ('Recebido', 'Parcial')
               )
               AND NOT EXISTS (
                   SELECT 1 FROM recebimentos r
                   WHERE r.conta_receber_id = cr.id
                     AND r.empresa = cr.empresa
                     AND (r.status IS NULL OR r.status <> 'Cancelado')
               )",
            "empresa={$empresa}"
        );
        $contas = $this->read->getResult() ?? [];
        $criados = 0;
        foreach ($contas as $conta) {
            // Se status Recebido mas valor_recebido zerado, usa valor_total
            if ((float) ($conta['valor_recebido'] ?? 0) <= 0 && ($conta['status'] ?? '') === 'Recebido') {
                $conta['valor_recebido'] = (float) ($conta['valor_total'] ?? 0);
            }
            if ($this->sincronizarDaContaReceber((int) $conta['id'], $empresa, $conta)) {
                $criados++;
            }
        }

        return $criados;
    }

    /**
     * ENUM legado com mojibake em forma_pagamento bloqueava INSERT (Data truncated).
     */
    private function ensureRecebimentosSchema(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $pdo = (new class extends \Agencia\Close\Conn\Conn {
                public function pdo()
                {
                    return $this->getConn();
                }
            })->pdo();

            $row = $pdo->query("SHOW COLUMNS FROM `recebimentos` LIKE 'forma_pagamento'")->fetch(\PDO::FETCH_ASSOC);
            $type = strtolower((string) ($row['Type'] ?? ''));
            if ($row && str_starts_with($type, 'enum')) {
                $pdo->exec("ALTER TABLE `recebimentos` MODIFY COLUMN `forma_pagamento` varchar(80) NULL DEFAULT 'PIX'");
            }

            $row = $pdo->query("SHOW COLUMNS FROM `recebimentos` LIKE 'status'")->fetch(\PDO::FETCH_ASSOC);
            $type = strtolower((string) ($row['Type'] ?? ''));
            if ($row && str_starts_with($type, 'enum')) {
                $pdo->exec("ALTER TABLE `recebimentos` MODIFY COLUMN `status` varchar(40) NULL DEFAULT 'Confirmado'");
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * Cria um novo pagamento
     */
    public function criarPagamento($data): bool
    {
        $this->create->ExeCreate("pagamentos", $data);
        return $this->create->getResult();
    }

    /**
     * Atualiza um recebimento
     */
    public function atualizarRecebimento($id, $company, $data): bool
    {
        // Busca recebimento antigo para recalcular conta
        $recebimentoAntigo = $this->getRecebimento($id, $company);
        $recebimentoData = $recebimentoAntigo->getResult()[0] ?? [];
        
        $this->update->ExeUpdate("recebimentos", $data, "WHERE id = :id AND empresa = :empresa", "id={$id}&empresa={$company}");
        
        // Atualiza conta a receber se necessário
        if (!empty($recebimentoData['conta_receber_id'])) {
            $valorAntigo = $recebimentoData['valor'] ?? 0;
            $valorNovo = $data['valor'] ?? $valorAntigo;
            $diferenca = $valorNovo - $valorAntigo;
            
            if ($diferenca != 0) {
                $this->atualizarContaReceberAposRecebimento($recebimentoData['conta_receber_id'], $diferenca, $company);
            }
        }
        
        return $this->update->getResult();
    }

    /**
     * Atualiza um pagamento
     */
    public function atualizarPagamento($id, $company, $data): bool
    {
        $this->update->ExeUpdate("pagamentos", $data, "WHERE id = :id AND empresa = :empresa", "id={$id}&empresa={$company}");
        return $this->update->getResult();
    }

    /**
     * Remove um recebimento
     */
    public function removerRecebimento($id, $company): bool
    {
        // Busca recebimento antes de remover
        $recebimento = $this->getRecebimento($id, $company);
        $recebimentoData = $recebimento->getResult()[0] ?? [];
        
        $this->delete->ExeDelete("recebimentos", "WHERE id = :id AND empresa = :empresa", "id={$id}&empresa={$company}");
        
        // Atualiza conta a receber removendo o valor
        if (!empty($recebimentoData['conta_receber_id'])) {
            $valorRemovido = -($recebimentoData['valor'] ?? 0);
            $this->atualizarContaReceberAposRecebimento($recebimentoData['conta_receber_id'], $valorRemovido, $company);
        }
        
        return $this->delete->getResult();
    }

    /**
     * Remove um pagamento
     */
    public function removerPagamento($id, $company): bool
    {
        $this->delete->ExeDelete("pagamentos", "WHERE id = :id AND empresa = :empresa", "id={$id}&empresa={$company}");
        return $this->delete->getResult();
    }

    /**
     * Atualiza conta a receber após recebimento
     */
    private function atualizarContaReceberAposRecebimento($contaReceberId, $valorAdicional, $company): void
    {
        if (empty($contaReceberId)) {
            return;
        }
        
        $contaReceberModel = new \Agencia\Close\Models\Financeiro\ContaReceber();
        $conta = $contaReceberModel->getContaReceber($contaReceberId, $company);
        $contaData = $conta->getResult()[0] ?? [];
        
        if (!empty($contaData)) {
            $novoValorRecebido = ($contaData['valor_recebido'] ?? 0) + $valorAdicional;
            $contaReceberModel->atualizar($contaReceberId, $company, [
                'valor_recebido' => max(0, $novoValorRecebido)
            ]);
        }
    }

    /**
     * Busca recebimentos para DataTable (AJAX)
     */
    public function getRecebimentosDataTable(int $company, array $filtros = []): array
    {
        $where = "WHERE r.empresa = :empresa";
        $params = "empresa={$company}";

        if (!empty($filtros['status'])) {
            $where .= " AND r.status = :status";
            $params .= "&status={$filtros['status']}";
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND r.data_recebimento >= :data_inicio";
            $params .= "&data_inicio={$filtros['data_inicio']}";
        }
        if (!empty($filtros['data_fim'])) {
            $where .= " AND r.data_recebimento <= :data_fim";
            $params .= "&data_fim={$filtros['data_fim']}";
        }

        $query = "SELECT r.*, 
                         cr.descricao as conta_descricao,
                         f.numero_fatura,
                         a.cliente_nome as agendamento_cliente
                  FROM recebimentos r
                  LEFT JOIN contas_receber cr ON r.conta_receber_id = cr.id AND r.empresa = cr.empresa
                  LEFT JOIN faturas f ON r.fatura_id = f.id AND r.empresa = f.empresa
                  LEFT JOIN agendamentos a ON r.agendamento_id = a.id AND r.empresa = a.empresa
                  {$where}
                  ORDER BY r.data_recebimento DESC, r.id DESC";

        $this->read->FullRead($query, $params);
        return $this->read->getResult() ?? [];
    }

    /**
     * Busca pagamentos para DataTable (AJAX)
     */
    public function getPagamentosDataTable(int $company, array $filtros = []): array
    {
        $where = "WHERE p.empresa = :empresa";
        $params = "empresa={$company}";

        if (!empty($filtros['status'])) {
            $where .= " AND p.status = :status";
            $params .= "&status={$filtros['status']}";
        }
        if (!empty($filtros['tipo'])) {
            $where .= " AND p.tipo = :tipo";
            $params .= "&tipo={$filtros['tipo']}";
        }
        if (!empty($filtros['data_inicio'])) {
            $where .= " AND p.data_pagamento >= :data_inicio";
            $params .= "&data_inicio={$filtros['data_inicio']}";
        }
        if (!empty($filtros['data_fim'])) {
            $where .= " AND p.data_pagamento <= :data_fim";
            $params .= "&data_fim={$filtros['data_fim']}";
        }

        $query = "SELECT p.*, 
                         a.cliente_nome as agendamento_cliente,
                         a.tipo_pericia
                  FROM pagamentos p
                  LEFT JOIN agendamentos a ON p.agendamento_id = a.id AND p.empresa = a.empresa
                  {$where}
                  ORDER BY p.data_pagamento DESC, p.id DESC";

        $this->read->FullRead($query, $params);
        return $this->read->getResult() ?? [];
    }

    /**
     * Calcula totais de recebimentos e pagamentos
     */
    public function getTotaisFinanceiros($company, $dataInicio = null, $dataFim = null): array
    {
        $whereRecebimentos = "WHERE empresa = :empresa";
        $wherePagamentos = "WHERE empresa = :empresa";
        $params = "empresa={$company}";

        if ($dataInicio) {
            $whereRecebimentos .= " AND data_recebimento >= :data_inicio";
            $wherePagamentos .= " AND data_pagamento >= :data_inicio";
            $params .= "&data_inicio={$dataInicio}";
        }
        if ($dataFim) {
            $whereRecebimentos .= " AND data_recebimento <= :data_fim";
            $wherePagamentos .= " AND data_pagamento <= :data_fim";
            $params .= "&data_fim={$dataFim}";
        }

        // Total recebimentos
        $this->read = new Read();
        $queryRecebimentos = "SELECT SUM(valor) as total FROM recebimentos {$whereRecebimentos} AND status = 'Confirmado'";
        $this->read->FullRead($queryRecebimentos, $params);
        $recebimentos = $this->read->getResult()[0] ?? ['total' => 0];

        // Total pagamentos
        $this->read = new Read();
        $queryPagamentos = "SELECT SUM(valor) as total FROM pagamentos {$wherePagamentos} AND status = 'Pago'";
        $this->read->FullRead($queryPagamentos, $params);
        $pagamentos = $this->read->getResult()[0] ?? ['total' => 0];

        return [
            'total_recebimentos' => $recebimentos['total'] ?? 0,
            'total_pagamentos' => $pagamentos['total'] ?? 0,
            'saldo' => ($recebimentos['total'] ?? 0) - ($pagamentos['total'] ?? 0)
        ];
    }
}

