<?php

namespace Agencia\Close\Migrations;

class CreateParecerPermissoes extends Migration
{
    public function up(): void
    {
        $permissoes = [
            [
                'nome' => 'parecer_ver',
                'titulo' => 'Ver Pareceres',
                'descricao' => 'Permite visualizar a lista de pareceres',
                'grupo' => 'Pareceres'
            ],
            [
                'nome' => 'parecer_cadastrar',
                'titulo' => 'Cadastrar Pareceres',
                'descricao' => 'Permite cadastrar novos pareceres',
                'grupo' => 'Pareceres'
            ],
            [
                'nome' => 'parecer_gerenciar',
                'titulo' => 'Gerenciar Pareceres',
                'descricao' => 'Permite editar pareceres existentes',
                'grupo' => 'Pareceres'
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
            'parecer_ver',
            'parecer_cadastrar',
            'parecer_gerenciar'
        ];

        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nome' => $permissao]);
        }
    }
}
