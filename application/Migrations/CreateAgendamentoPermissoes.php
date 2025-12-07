<?php

namespace Agencia\Close\Migrations;

class CreateAgendamentoPermissoes extends Migration
{
    public function up(): void
    {
        // Insere as permissões de agendamento na tabela permissoes
        $permissoes = [
            [
                'nome' => 'agendamento_ver',
                'titulo' => 'Ver Agendamentos',
                'descricao' => 'Permite visualizar a lista de agendamentos',
                'grupo' => 'Agendamentos'
            ],
            [
                'nome' => 'agendamento_criar',
                'titulo' => 'Criar Agendamentos',
                'descricao' => 'Permite criar novos agendamentos',
                'grupo' => 'Agendamentos'
            ],
            [
                'nome' => 'agendamento_editar',
                'titulo' => 'Editar Agendamentos',
                'descricao' => 'Permite editar agendamentos existentes',
                'grupo' => 'Agendamentos'
            ],
            [
                'nome' => 'agendamento_deletar',
                'titulo' => 'Deletar Agendamentos',
                'descricao' => 'Permite deletar agendamentos',
                'grupo' => 'Agendamentos'
            ]
        ];

        foreach ($permissoes as $permissao) {
            // Verifica se a permissão já existe
            $checkSql = "SELECT id FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->execute(['nome' => $permissao['nome']]);
            
            if ($stmt->rowCount() == 0) {
                $insertSql = "INSERT INTO permissoes (nome, titulo, descricao, grupo) 
                             VALUES (:nome, :titulo, :descricao, :grupo)";
                $stmt = $this->conn->prepare($insertSql);
                $stmt->execute([
                    'nome' => $permissao['nome'],
                    'titulo' => $permissao['titulo'],
                    'descricao' => $permissao['descricao'],
                    'grupo' => $permissao['grupo']
                ]);
            }
        }
    }

    public function down(): void
    {
        // Remove as permissões de agendamento
        $permissoes = ['agendamento_ver', 'agendamento_criar', 'agendamento_editar', 'agendamento_deletar'];
        
        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute(['nome' => $permissao]);
        }
    }
}
