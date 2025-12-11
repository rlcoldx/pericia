<?php

namespace Agencia\Close\Migrations;

class CreateCargosPermissoes extends Migration
{
    public function up(): void
    {
        $permissoes = [
            [
                'nome' => 'cargos_ver',
                'titulo' => 'Ver Cargos',
                'descricao' => 'Permite visualizar a lista de cargos',
                'grupo' => 'Gestão'
            ],
            [
                'nome' => 'cargos_criar',
                'titulo' => 'Criar Cargos',
                'descricao' => 'Permite criar novos cargos',
                'grupo' => 'Gestão'
            ],
            [
                'nome' => 'cargos_editar',
                'titulo' => 'Editar Cargos',
                'descricao' => 'Permite editar cargos existentes',
                'grupo' => 'Gestão'
            ],
            [
                'nome' => 'cargos_deletar',
                'titulo' => 'Deletar Cargos',
                'descricao' => 'Permite deletar cargos',
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
            'cargos_ver', 'cargos_criar', 'cargos_editar', 'cargos_deletar'
        ];

        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nome' => $permissao]);
        }
    }
}
