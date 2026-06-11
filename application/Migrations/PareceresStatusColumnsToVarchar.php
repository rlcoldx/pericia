<?php

namespace Agencia\Close\Migrations;

/**
 * Converte ENUM → VARCHAR em `pareceres.status_parecer` e `pareceres.status_revisao`.
 * Evita truncamento por valor fora do ENUM e problemas de encoding.
 */
class PareceresStatusColumnsToVarchar extends Migration
{
    public function up(): void
    {
        if (!$this->tableExists('pareceres')) {
            return;
        }

        if ($this->columnExists('pareceres', 'status_parecer')) {
            $this->executeQuery(
                "ALTER TABLE `pareceres`
                 MODIFY COLUMN `status_parecer` varchar(120) NULL DEFAULT NULL COMMENT 'Status do parecer'"
            );
        }

        if ($this->columnExists('pareceres', 'status_revisao')) {
            $this->executeQuery(
                "ALTER TABLE `pareceres`
                 MODIFY COLUMN `status_revisao` varchar(120) NULL DEFAULT NULL COMMENT 'Status de revisão do parecer'"
            );
        }
    }

    public function down(): void
    {
        // Não reverte para ENUM automaticamente.
    }
}
