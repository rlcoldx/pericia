<?php

namespace Agencia\Close\Migrations;

class CreateEquipePermissoes extends Migration
{
    public function up(): void
    {
        $permissoes = [
            [
                'nome' => 'equipe_ver',
                'titulo' => 'Ver Equipe',
                'descricao' => 'Permite visualizar a lista de membros da equipe',
                'grupo' => 'Gestão'
            ],
            [
                'nome' => 'equipe_criar',
                'titulo' => 'Criar Membro da Equipe',
                'descricao' => 'Permite criar novos membros da equipe',
                'grupo' => 'Gestão'
            ],
            [
                'nome' => 'equipe_editar',
                'titulo' => 'Editar Membro da Equipe',
                'descricao' => 'Permite editar membros da equipe existentes',
                'grupo' => 'Gestão'
            ],
            [
                'nome' => 'equipe_deletar',
                'titulo' => 'Deletar Membro da Equipe',
                'descricao' => 'Permite deletar membros da equipe',
                'grupo' => 'Gestão'
            ],
        ];

        foreach ($permissoes as $permissao) {
            $checkSql = "SELECT id FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->execute([':nome' => $permissao['nome']]);

            if ($stmt->rowCount() === 0) {
                $insertSql = "INSERT INTO permissoes (nome, titulo, descricao, grupo)
                              VALUES (:nome, :titulo, :descricao, :grupo)";
                $insertStmt = $this->conn->prepare($insertSql);
                $insertStmt->execute([
                    ':nome' => $permissao['nome'],
                    ':titulo' => $permissao['titulo'],
                    ':descricao' => $permissao['descricao'],
                    ':grupo' => $permissao['grupo'],
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissoes = [
            'equipe_ver', 'equipe_criar', 'equipe_editar', 'equipe_deletar'
        ];

        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nome' => $permissao]);
        }
    }
}
