<?php

namespace Agencia\Close\Migrations;

class AddAgendamentoIdToPareceres extends Migration
{
    public function up(): void
    {
        if (!$this->columnExists('pareceres', 'agendamento_id')) {
            $this->executeQuery("
                ALTER TABLE `pareceres`
                ADD COLUMN `agendamento_id` bigint(255) DEFAULT NULL AFTER `empresa`,
                ADD KEY `idx_agendamento_id` (`agendamento_id`)
            ");
        }
    }

    public function down(): void
    {
        if ($this->columnExists('pareceres', 'agendamento_id')) {
            $this->executeQuery("
                ALTER TABLE `pareceres`
                DROP COLUMN `agendamento_id`,
                DROP KEY `idx_agendamento_id`
            ");
        }
    }
}
