<?php

namespace Agencia\Close\Migrations;

class CreateFinanceiroPermissoes extends Migration
{
    public function up(): void
    {
        // Permissões para Contas a Receber
        $permissoesContasReceber = [
            [
                'nome' => 'contas_receber_ver',
                'titulo' => 'Ver Contas a Receber',
                'descricao' => 'Permite visualizar a lista de contas a receber',
                'grupo' => 'Controle Financeiro'
            ],
            [
                'nome' => 'contas_receber_criar',
                'titulo' => 'Criar Contas a Receber',
                'descricao' => 'Permite criar novas contas a receber',
                'grupo' => 'Controle Financeiro'
            ],
            [
                'nome' => 'contas_receber_editar',
                'titulo' => 'Editar Contas a Receber',
                'descricao' => 'Permite editar contas a receber existentes',
                'grupo' => 'Controle Financeiro'
            ],
            [
                'nome' => 'contas_receber_deletar',
                'titulo' => 'Deletar Contas a Receber',
                'descricao' => 'Permite deletar contas a receber',
                'grupo' => 'Controle Financeiro'
            ]
        ];

        // Permissões para Faturamento
        $permissoesFaturamento = [
            [
                'nome' => 'faturamento_ver',
                'titulo' => 'Ver Faturas',
                'descricao' => 'Permite visualizar a lista de faturas',
                'grupo' => 'Controle Financeiro'
            ],
            [
                'nome' => 'faturamento_criar',
                'titulo' => 'Criar Faturas',
                'descricao' => 'Permite criar novas faturas',
                'grupo' => 'Controle Financeiro'
            ],
            [
                'nome' => 'faturamento_editar',
                'titulo' => 'Editar Faturas',
                'descricao' => 'Permite editar faturas existentes',
                'grupo' => 'Controle Financeiro'
            ],
            [
                'nome' => 'faturamento_deletar',
                'titulo' => 'Deletar Faturas',
                'descricao' => 'Permite deletar faturas',
                'grupo' => 'Controle Financeiro'
            ],
            [
                'nome' => 'faturamento_emitir',
                'titulo' => 'Emitir Faturas',
                'descricao' => 'Permite emitir faturas',
                'grupo' => 'Controle Financeiro'
            ]
        ];

        // Permissões para Relatórios Financeiros
        $permissoesRelatorios = [
            [
                'nome' => 'relatorio_financeiro_ver',
                'titulo' => 'Ver Relatórios Financeiros',
                'descricao' => 'Permite visualizar relatórios financeiros',
                'grupo' => 'Controle Financeiro'
            ],
            [
                'nome' => 'relatorio_financeiro_exportar',
                'titulo' => 'Exportar Relatórios Financeiros',
                'descricao' => 'Permite exportar relatórios financeiros',
                'grupo' => 'Controle Financeiro'
            ]
        ];

        // Permissões para Pagamentos e Recebimentos
        $permissoesPagamentos = [
            [
                'nome' => 'pagamento_recebimento_ver',
                'titulo' => 'Ver Pagamentos e Recebimentos',
                'descricao' => 'Permite visualizar pagamentos e recebimentos',
                'grupo' => 'Controle Financeiro'
            ],
            [
                'nome' => 'pagamento_recebimento_criar',
                'titulo' => 'Criar Pagamentos e Recebimentos',
                'descricao' => 'Permite criar novos pagamentos e recebimentos',
                'grupo' => 'Controle Financeiro'
            ],
            [
                'nome' => 'pagamento_recebimento_editar',
                'titulo' => 'Editar Pagamentos e Recebimentos',
                'descricao' => 'Permite editar pagamentos e recebimentos existentes',
                'grupo' => 'Controle Financeiro'
            ],
            [
                'nome' => 'pagamento_recebimento_deletar',
                'titulo' => 'Deletar Pagamentos e Recebimentos',
                'descricao' => 'Permite deletar pagamentos e recebimentos',
                'grupo' => 'Controle Financeiro'
            ]
        ];

        // Combina todas as permissões
        $todasPermissoes = array_merge(
            $permissoesContasReceber,
            $permissoesFaturamento,
            $permissoesRelatorios,
            $permissoesPagamentos
        );

        foreach ($todasPermissoes as $permissao) {
            $checkSql = "SELECT id FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->execute([':nome' => $permissao['nome']]);
            
            if ($stmt->rowCount() == 0) {
                $insertSql = "INSERT INTO permissoes (nome, titulo, descricao, grupo) 
                             VALUES (:nome, :titulo, :descricao, :grupo)";
                $insertStmt = $this->conn->prepare($insertSql);
                $insertStmt->execute([
                    ':nome' => $permissao['nome'],
                    ':titulo' => $permissao['titulo'],
                    ':descricao' => $permissao['descricao'],
                    ':grupo' => $permissao['grupo']
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissoes = [
            'contas_receber_ver', 'contas_receber_criar', 'contas_receber_editar', 'contas_receber_deletar',
            'faturamento_ver', 'faturamento_criar', 'faturamento_editar', 'faturamento_deletar', 'faturamento_emitir',
            'relatorio_financeiro_ver', 'relatorio_financeiro_exportar',
            'pagamento_recebimento_ver', 'pagamento_recebimento_criar', 'pagamento_recebimento_editar', 'pagamento_recebimento_deletar'
        ];
        
        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nome' => $permissao]);
        }
    }
}

