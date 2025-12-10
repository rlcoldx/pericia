<?php

namespace Agencia\Close\Migrations;

class CreateAssistentesPermissoes extends Migration
{
    public function up(): void
    {
        $permissoes = [
            [
                'nome' => 'assistentes_ver',
                'titulo' => 'Ver Assistentes',
                'descricao' => 'Permite visualizar a lista de assistentes',
                'grupo' => 'Cadastros Gerais'
            ],
            [
                'nome' => 'assistentes_criar',
                'titulo' => 'Criar Assistentes',
                'descricao' => 'Permite criar novos assistentes',
                'grupo' => 'Cadastros Gerais'
            ],
            [
                'nome' => 'assistentes_editar',
                'titulo' => 'Editar Assistentes',
                'descricao' => 'Permite editar assistentes existentes',
                'grupo' => 'Cadastros Gerais'
            ],
            [
                'nome' => 'assistentes_deletar',
                'titulo' => 'Deletar Assistentes',
                'descricao' => 'Permite deletar assistentes',
                'grupo' => 'Cadastros Gerais'
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
            'assistentes_ver', 'assistentes_criar', 'assistentes_editar', 'assistentes_deletar'
        ];

        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nome' => $permissao]);
        }
    }
}
