<?php

namespace Agencia\Close\Migrations;

/**
 * Link da Pasta do Drive em manifestações, pareceres e agendamentos.
 * Status de fluxo do parecer.
 */
class AddLinkPastaDriveModulosAndParecerStatus extends Migration
{
    public function up(): void
    {
        if ($this->tableExists('manifestacoes_impugnacoes') && !$this->columnExists('manifestacoes_impugnacoes', 'link_pasta_drive')) {
            $this->executeQuery(
                "ALTER TABLE `manifestacoes_impugnacoes`
                 ADD COLUMN `link_pasta_drive` varchar(500) DEFAULT NULL
                 COMMENT 'Link da pasta do Google Drive' AFTER `funcao_observacao`"
            );
        }

        if ($this->tableExists('pareceres') && !$this->columnExists('pareceres', 'link_pasta_drive')) {
            $this->executeQuery(
                "ALTER TABLE `pareceres`
                 ADD COLUMN `link_pasta_drive` varchar(500) DEFAULT NULL
                 COMMENT 'Link da pasta do Google Drive' AFTER `observacoes`"
            );
        }

        if ($this->tableExists('pareceres') && !$this->columnExists('pareceres', 'status')) {
            $this->executeQuery(
                "ALTER TABLE `pareceres`
                 ADD COLUMN `status` varchar(80) NOT NULL DEFAULT 'Aguardando Parecer'
                 COMMENT 'Status do fluxo do parecer' AFTER `link_pasta_drive`"
            );
        }

        if ($this->tableExists('agendamentos') && !$this->columnExists('agendamentos', 'link_pasta_drive')) {
            $this->executeQuery(
                "ALTER TABLE `agendamentos`
                 ADD COLUMN `link_pasta_drive` varchar(500) DEFAULT NULL
                 COMMENT 'Link da pasta do Google Drive' AFTER `observacoes`"
            );
        }
    }

    public function down(): void
    {
        if ($this->columnExists('pareceres', 'status')) {
            $this->executeQuery('ALTER TABLE `pareceres` DROP COLUMN `status`');
        }

        foreach (['manifestacoes_impugnacoes', 'pareceres', 'agendamentos'] as $table) {
            if ($this->columnExists($table, 'link_pasta_drive')) {
                $this->executeQuery("ALTER TABLE `{$table}` DROP COLUMN `link_pasta_drive`");
            }
        }
    }
}
