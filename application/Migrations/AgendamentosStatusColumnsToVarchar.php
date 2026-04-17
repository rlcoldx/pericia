<?php

namespace Agencia\Close\Migrations;

/**
 * Converte ENUM → VARCHAR em `agendamentos.status` e `agendamentos.status_parecer`.
 * Evita truncamento por valor fora do ENUM e problemas de encoding em labels com acento.
 */
class AgendamentosStatusColumnsToVarchar extends Migration
{
    public function up(): void
    {
        if (!$this->tableExists('agendamentos')) {
            return;
        }

        if ($this->columnExists('agendamentos', 'status')) {
            $this->executeQuery(
                "ALTER TABLE `agendamentos`
                 MODIFY COLUMN `status` varchar(80) NULL DEFAULT 'Agendado' COMMENT 'Status do agendamento'"
            );
        }

        if ($this->columnExists('agendamentos', 'status_parecer')) {
            $this->executeQuery(
                "ALTER TABLE `agendamentos`
                 MODIFY COLUMN `status_parecer` varchar(120) NULL DEFAULT NULL COMMENT 'Status do parecer'"
            );
        }
    }

    public function down(): void
    {
        // Não reverte para ENUM automaticamente: valores fora da lista original quebrariam o ALTER.
    }
}
