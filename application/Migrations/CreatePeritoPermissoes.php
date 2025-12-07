<?php

namespace Agencia\Close\Migrations;

class CreatePeritoPermissoes extends Migration
{
    public function up(): void
    {
        $permissoes = [
            [
                'nome' => 'perito_ver',
                'titulo' => 'Ver Peritos',
                'descricao' => 'Permite visualizar a lista de peritos',
                'grupo' => 'Peritos'
            ],
            [
                'nome' => 'perito_criar',
                'titulo' => 'Criar Peritos',
                'descricao' => 'Permite criar novos peritos',
                'grupo' => 'Peritos'
            ],
            [
                'nome' => 'perito_editar',
                'titulo' => 'Editar Peritos',
                'descricao' => 'Permite editar peritos existentes',
                'grupo' => 'Peritos'
            ],
            [
                'nome' => 'perito_deletar',
                'titulo' => 'Deletar Peritos',
                'descricao' => 'Permite deletar peritos',
                'grupo' => 'Peritos'
            ]
        ];

        foreach ($permissoes as $permissao) {
            $checkSql = "SELECT id FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($checkSql);
            $stmt->execute([':nome' => $permissao['nome']]);
            
            if ($stmt->rowCount() == 0) {
                $insertSql = "INSERT INTO permissoes (nome, titulo, descricao, grupo) 
                             VALUES (:nome, :titulo, :descricao, :grupo)";
                $insertStmt = $this->conn->prepare($insertSql);
                $insertStmt->execute([
                    ':nome' => $permissao['nome'],
                    ':titulo' => $permissao['titulo'],
                    ':descricao' => $permissao['descricao'],
                    ':grupo' => $permissao['grupo']
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissoes = ['perito_ver', 'perito_criar', 'perito_editar', 'perito_deletar'];
        
        foreach ($permissoes as $permissao) {
            $sql = "DELETE FROM permissoes WHERE nome = :nome";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([':nome' => $permissao]);
        }
    }
}

