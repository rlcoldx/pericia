<?php

namespace Agencia\Close\Migrations;

class CreateManifestacoesImpugnacoesPermissoes extends Migration
{
    public function up(): void
    {
        $permissoes = [
            [
                'nome' => 'manifestacao_impugnacao_ver',
                'titulo' => 'Ver Manifestações e Impugnações',
                'descricao' => 'Permite visualizar a lista de manifestações e impugnações',
                'grupo' => 'Manifestações e Impugnações'
            ],
            [
                'nome' => 'manifestacao_impugnacao_criar',
                'titulo' => 'Criar Manifestações e Impugnações',
                'descricao' => 'Permite criar novas manifestações e impugnações',
                'grupo' => 'Manifestações e Impugnações'
            ],
            [
                'nome' => 'manifestacao_impugnacao_editar',
                'titulo' => 'Editar Manifestações e Impugnações',
                'descricao' => 'Permite editar manifestações e impugnações existentes',
                'grupo' => 'Manifestações e Impugnações'
            ],
            [
                'nome' => 'manifestacao_impugnacao_deletar',
                'titulo' => 'Deletar Manifestações e Impugnações',
                'descricao' => 'Permite deletar manifestações e impugnações',
                'grupo' => 'Manifestações e Impugnações'
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
            'manifestacao_impugnacao_ver',
            'manifestacao_impugnacao_criar',
            'manifestacao_impugnacao_editar',
            'manifestacao_impugnacao_deletar'
        ];

        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nome' => $permissao]);
        }
    }
}
