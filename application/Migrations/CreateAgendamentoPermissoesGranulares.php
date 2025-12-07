<?php

namespace Agencia\Close\Migrations;

class CreateAgendamentoPermissoesGranulares extends Migration
{
    public function up(): void
    {
        // Permissões granulares por campo para PRIMEIRO PASSO
        $permissoesPrimeiroPasso = [
            [
                'nome' => 'agendamento_primeiro_passo_data_entrada',
                'titulo' => 'Cadastrar Data de Entrada',
                'descricao' => 'Permite cadastrar a data que o cliente solicitou o serviço',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_data_pericia',
                'titulo' => 'Cadastrar Data da Perícia',
                'descricao' => 'Permite cadastrar a data da perícia',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_hora',
                'titulo' => 'Cadastrar Hora',
                'descricao' => 'Permite cadastrar a hora da perícia',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_numero_processo',
                'titulo' => 'Cadastrar Número do Processo',
                'descricao' => 'Permite cadastrar informações do processo',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_vara',
                'titulo' => 'Cadastrar Vara',
                'descricao' => 'Permite cadastrar a vara/órgão',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_local',
                'titulo' => 'Cadastrar Local',
                'descricao' => 'Permite cadastrar o local da perícia',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_reclamante',
                'titulo' => 'Cadastrar Reclamante',
                'descricao' => 'Permite cadastrar o nome do reclamante',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_perito',
                'titulo' => 'Cadastrar Perito',
                'descricao' => 'Permite cadastrar o perito responsável',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_cliente',
                'titulo' => 'Cadastrar Cliente (Reclamada)',
                'descricao' => 'Permite cadastrar o cliente/reclamada',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_valor_cobrado',
                'titulo' => 'Cadastrar Valor Cobrado',
                'descricao' => 'Permite cadastrar o valor cobrado pela perícia',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_tipo',
                'titulo' => 'Cadastrar Tipo de Perícia',
                'descricao' => 'Permite cadastrar o tipo de perícia',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_numero_tipo',
                'titulo' => 'Cadastrar Número do Tipo',
                'descricao' => 'Permite cadastrar o número do tipo de perícia',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_assistente',
                'titulo' => 'Cadastrar Assistente',
                'descricao' => 'Permite cadastrar o assistente/prestador de serviço',
                'grupo' => 'Agendamentos Primeiro Passo'
            ],
            [
                'nome' => 'agendamento_primeiro_passo_valor_pago_assistente',
                'titulo' => 'Cadastrar Valor Pago ao Assistente',
                'descricao' => 'Permite cadastrar o valor a ser pago ao assistente',
                'grupo' => 'Agendamentos Primeiro Passo'
            ]
        ];
        
        // Permissões granulares por campo para SEGUNDO PASSO
        $permissoesSegundoPasso = [
            [
                'nome' => 'agendamento_segundo_passo_data_realizada',
                'titulo' => 'Cadastrar Data Realizada',
                'descricao' => 'Permite cadastrar a data que foi realizada a perícia',
                'grupo' => 'Agendamentos Segundo Passo'
            ],
            [
                'nome' => 'agendamento_segundo_passo_data_fatal',
                'titulo' => 'Cadastrar Data Fatal',
                'descricao' => 'Permite cadastrar a data fatal',
                'grupo' => 'Agendamentos Segundo Passo'
            ],
            [
                'nome' => 'agendamento_segundo_passo_data_entrega_parecer',
                'titulo' => 'Cadastrar Data de Entrega do Parecer',
                'descricao' => 'Permite cadastrar a data de entrega do parecer',
                'grupo' => 'Agendamentos Segundo Passo'
            ],
            [
                'nome' => 'agendamento_segundo_passo_status_parecer',
                'titulo' => 'Cadastrar Status do Parecer',
                'descricao' => 'Permite cadastrar o status do parecer',
                'grupo' => 'Agendamentos Segundo Passo'
            ],
            [
                'nome' => 'agendamento_segundo_passo_obs_parecer',
                'titulo' => 'Cadastrar Observações do Parecer',
                'descricao' => 'Permite cadastrar observações referentes ao status do parecer',
                'grupo' => 'Agendamentos Segundo Passo'
            ]
        ];
        
        // Permissões granulares por campo para TERCEIRO PASSO
        $permissoesTerceiroPasso = [
            [
                'nome' => 'agendamento_terceiro_passo_data_pagamento_assistente',
                'titulo' => 'Cadastrar Data de Pagamento do Assistente',
                'descricao' => 'Permite cadastrar a data do pagamento do assistente',
                'grupo' => 'Agendamentos Terceiro Passo'
            ],
            [
                'nome' => 'agendamento_terceiro_passo_numero_pedido',
                'titulo' => 'Cadastrar Número do Pedido',
                'descricao' => 'Permite cadastrar o número do pedido do cliente',
                'grupo' => 'Agendamentos Terceiro Passo'
            ],
            [
                'nome' => 'agendamento_terceiro_passo_numero_nota_fiscal',
                'titulo' => 'Cadastrar Número da Nota Fiscal',
                'descricao' => 'Permite cadastrar o número da nota fiscal',
                'grupo' => 'Agendamentos Terceiro Passo'
            ],
            [
                'nome' => 'agendamento_terceiro_passo_numero_boleto',
                'titulo' => 'Cadastrar Número do Boleto',
                'descricao' => 'Permite cadastrar o número do boleto',
                'grupo' => 'Agendamentos Terceiro Passo'
            ],
            [
                'nome' => 'agendamento_terceiro_passo_data_envio',
                'titulo' => 'Cadastrar Data de Envio',
                'descricao' => 'Permite cadastrar a data de envio da NF e boleto',
                'grupo' => 'Agendamentos Terceiro Passo'
            ],
            [
                'nome' => 'agendamento_terceiro_passo_data_vencimento',
                'titulo' => 'Cadastrar Data de Vencimento',
                'descricao' => 'Permite cadastrar a data de vencimento acordada com o cliente',
                'grupo' => 'Agendamentos Terceiro Passo'
            ],
            [
                'nome' => 'agendamento_terceiro_passo_status_pagamento',
                'titulo' => 'Cadastrar Status de Pagamento',
                'descricao' => 'Permite cadastrar o status do pagamento',
                'grupo' => 'Agendamentos Terceiro Passo'
            ]
        ];
        
        $todasPermissoes = array_merge($permissoesPrimeiroPasso, $permissoesSegundoPasso, $permissoesTerceiroPasso);
        
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
            // PRIMEIRO PASSO
            'agendamento_primeiro_passo_data_entrada',
            'agendamento_primeiro_passo_data_pericia',
            'agendamento_primeiro_passo_hora',
            'agendamento_primeiro_passo_numero_processo',
            'agendamento_primeiro_passo_vara',
            'agendamento_primeiro_passo_local',
            'agendamento_primeiro_passo_reclamante',
            'agendamento_primeiro_passo_perito',
            'agendamento_primeiro_passo_cliente',
            'agendamento_primeiro_passo_valor_cobrado',
            'agendamento_primeiro_passo_tipo',
            'agendamento_primeiro_passo_numero_tipo',
            'agendamento_primeiro_passo_assistente',
            'agendamento_primeiro_passo_valor_pago_assistente',
            // SEGUNDO PASSO
            'agendamento_segundo_passo_data_realizada',
            'agendamento_segundo_passo_data_fatal',
            'agendamento_segundo_passo_data_entrega_parecer',
            'agendamento_segundo_passo_status_parecer',
            'agendamento_segundo_passo_obs_parecer',
            // TERCEIRO PASSO
            'agendamento_terceiro_passo_data_pagamento_assistente',
            'agendamento_terceiro_passo_numero_pedido',
            'agendamento_terceiro_passo_numero_nota_fiscal',
            'agendamento_terceiro_passo_numero_boleto',
            'agendamento_terceiro_passo_data_envio',
            'agendamento_terceiro_passo_data_vencimento',
            'agendamento_terceiro_passo_status_pagamento'
        ];
        
        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nome' => $permissao]);
        }
    }
}

