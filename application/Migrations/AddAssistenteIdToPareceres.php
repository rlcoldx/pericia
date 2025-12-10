<?php

namespace Agencia\Close\Migrations;

class AddAssistenteIdToPareceres extends Migration
{
    public function up(): void
    {
        // Adiciona assistente_id se ainda não existir
        if (!$this->columnExists('pareceres', 'assistente_id')) {
            $this->executeQuery("ALTER TABLE `pareceres`
                                  ADD COLUMN `assistente_id` bigint(255) DEFAULT NULL AFTER `assistente`,
                                  ADD KEY `idx_assistente_id` (`assistente_id`)");
        }
    }

    public function down(): void
    {
        if ($this->columnExists('pareceres', 'assistente_id')) {
            $this->executeQuery("ALTER TABLE `pareceres` DROP COLUMN `assistente_id`");
        }
    }
}
