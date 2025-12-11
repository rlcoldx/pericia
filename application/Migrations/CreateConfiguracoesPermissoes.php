<?php

namespace Agencia\Close\Migrations;

class CreateConfiguracoesPermissoes extends Migration
{
    public function up(): void
    {
        $permissoes = [
            [
                'nome' => 'configuracao_ver',
                'titulo' => 'Ver Configurações',
                'descricao' => 'Permite visualizar as configurações do sistema',
                'grupo' => 'Configurações'
            ],
            [
                'nome' => 'configuracao_editar',
                'titulo' => 'Editar Configurações',
                'descricao' => 'Permite editar as configurações do sistema (e-mail, etc)',
                'grupo' => 'Configurações'
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
            'configuracao_ver', 'configuracao_editar'
        ];

        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nome' => $permissao]);
        }
    }
}
