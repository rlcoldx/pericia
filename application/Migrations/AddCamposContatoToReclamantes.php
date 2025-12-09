<?php

namespace Agencia\Close\Migrations;

class AddCamposContatoToReclamantes extends Migration
{
    public function up(): void
    {
        $queries = [];

        // Adiciona nome_contato se ainda não existir
        if (!$this->columnExists('reclamantes', 'nome_contato')) {
            $queries[] = "ALTER TABLE `reclamantes`
                          ADD COLUMN `nome_contato` varchar(255) DEFAULT NULL AFTER `nome`";
        }

        // Adiciona email_contato se ainda não existir
        if (!$this->columnExists('reclamantes', 'email_contato')) {
            $queries[] = "ALTER TABLE `reclamantes`
                          ADD COLUMN `email_contato` varchar(255) DEFAULT NULL AFTER `nome_contato`";
        }

        // Adiciona telefone_contato se ainda não existir
        if (!$this->columnExists('reclamantes', 'telefone_contato')) {
            $queries[] = "ALTER TABLE `reclamantes`
                          ADD COLUMN `telefone_contato` varchar(50) DEFAULT NULL AFTER `email_contato`";
        }

        // Executa apenas se houver algo para criar
        if (!empty($queries)) {
            $this->executeQueries($queries);
        }
    }

    public function down(): void
    {
        $queries = [];

        if ($this->columnExists('reclamantes', 'telefone_contato')) {
            $queries[] = "ALTER TABLE `reclamantes` DROP COLUMN `telefone_contato`";
        }

        if ($this->columnExists('reclamantes', 'email_contato')) {
            $queries[] = "ALTER TABLE `reclamantes` DROP COLUMN `email_contato`";
        }

        if ($this->columnExists('reclamantes', 'nome_contato')) {
            $queries[] = "ALTER TABLE `reclamantes` DROP COLUMN `nome_contato`";
        }

        if (!empty($queries)) {
            $this->executeQueries($queries);
        }
    }
}
