<?php

namespace Agencia\Close\Migrations;

class CreateQuesitoPermissoes extends Migration
{
    public function up(): void
    {
        $permissoes = [
            [
                'nome' => 'quesito_cadastrar',
                'titulo' => 'Cadastro de Quesito',
                'descricao' => 'Permite cadastrar novos quesitos',
                'grupo' => 'Quesitos'
            ],
            [
                'nome' => 'quesito_gerenciar',
                'titulo' => 'Gerenciar Quesitos',
                'descricao' => 'Permite editar, alterar status e acompanhar quesitos',
                'grupo' => 'Quesitos'
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
        $permissoes = ['quesito_cadastrar', 'quesito_gerenciar'];

        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nome' => $permissao]);
        }
    }
}

