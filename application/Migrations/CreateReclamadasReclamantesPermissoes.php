<?php

namespace Agencia\Close\Migrations;

class CreateReclamadasReclamantesPermissoes extends Migration
{
    public function up(): void
    {
        $permissoes = [
            [
                'nome' => 'reclamadas_ver',
                'titulo' => 'Ver Reclamadas',
                'descricao' => 'Permite visualizar a lista de reclamadas',
                'grupo' => 'Cadastros Gerais'
            ],
            [
                'nome' => 'reclamadas_criar',
                'titulo' => 'Criar Reclamadas',
                'descricao' => 'Permite criar novas reclamadas',
                'grupo' => 'Cadastros Gerais'
            ],
            [
                'nome' => 'reclamadas_editar',
                'titulo' => 'Editar Reclamadas',
                'descricao' => 'Permite editar reclamadas existentes',
                'grupo' => 'Cadastros Gerais'
            ],
            [
                'nome' => 'reclamadas_deletar',
                'titulo' => 'Deletar Reclamadas',
                'descricao' => 'Permite deletar reclamadas',
                'grupo' => 'Cadastros Gerais'
            ],
            [
                'nome' => 'reclamantes_ver',
                'titulo' => 'Ver Reclamantes',
                'descricao' => 'Permite visualizar a lista de reclamantes',
                'grupo' => 'Cadastros Gerais'
            ],
            [
                'nome' => 'reclamantes_criar',
                'titulo' => 'Criar Reclamantes',
                'descricao' => 'Permite criar novos reclamantes',
                'grupo' => 'Cadastros Gerais'
            ],
            [
                'nome' => 'reclamantes_editar',
                'titulo' => 'Editar Reclamantes',
                'descricao' => 'Permite editar reclamantes existentes',
                'grupo' => 'Cadastros Gerais'
            ],
            [
                'nome' => 'reclamantes_deletar',
                'titulo' => 'Deletar Reclamantes',
                'descricao' => 'Permite deletar reclamantes',
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
            'reclamadas_ver', 'reclamadas_criar', 'reclamadas_editar', 'reclamadas_deletar',
            'reclamantes_ver', 'reclamantes_criar', 'reclamantes_editar', 'reclamantes_deletar'
        ];

        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nome' => $permissao]);
        }
    }
}
