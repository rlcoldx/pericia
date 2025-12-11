<?php

namespace Agencia\Close\Migrations;

class CreateEmailTemplatePermissoes extends Migration
{
    public function up(): void
    {
        $permissoes = [
            [
                'nome' => 'email_template_ver',
                'titulo' => 'Ver Templates de E-mail',
                'descricao' => 'Permite visualizar a lista de templates de e-mail',
                'grupo' => 'Configurações'
            ],
            [
                'nome' => 'email_template_criar',
                'titulo' => 'Criar Template de E-mail',
                'descricao' => 'Permite criar novos templates de e-mail',
                'grupo' => 'Configurações'
            ],
            [
                'nome' => 'email_template_editar',
                'titulo' => 'Editar Template de E-mail',
                'descricao' => 'Permite editar templates de e-mail existentes',
                'grupo' => 'Configurações'
            ],
            [
                'nome' => 'email_template_deletar',
                'titulo' => 'Deletar Template de E-mail',
                'descricao' => 'Permite deletar templates de e-mail',
                'grupo' => 'Configurações'
            ],
            [
                'nome' => 'email_template_gerenciar',
                'titulo' => 'Gerenciar Templates de E-mail',
                'descricao' => 'Permite ativar/desativar templates e gerenciar configurações',
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
            'email_template_ver', 'email_template_criar', 'email_template_editar', 
            'email_template_deletar', 'email_template_gerenciar'
        ];

        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nome' => $permissao]);
        }
    }
}
