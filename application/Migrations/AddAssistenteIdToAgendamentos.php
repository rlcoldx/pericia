<?php

namespace Agencia\Close\Migrations;

class AddAssistenteIdToAgendamentos extends Migration
{
    public function up(): void
    {
        // Adiciona assistente_id se ainda não existir
        if (!$this->columnExists('agendamentos', 'assistente_id')) {
            $this->executeQuery("ALTER TABLE `agendamentos`
                                  ADD COLUMN `assistente_id` bigint(255) DEFAULT NULL AFTER `assistente_nome`,
                                  ADD KEY `idx_assistente_id` (`assistente_id`)");
        }
    }

    public function down(): void
    {
        if ($this->columnExists('agendamentos', 'assistente_id')) {
            $this->executeQuery("ALTER TABLE `agendamentos` DROP COLUMN `assistente_id`");
        }
    }
}
