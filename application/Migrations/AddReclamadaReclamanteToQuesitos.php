<?php

namespace Agencia\Close\Migrations;

class AddReclamadaReclamanteToQuesitos extends Migration
{
    public function up(): void
    {
        $sql = "ALTER TABLE `quesitos` 
                ADD COLUMN `reclamada_id` bigint(255) DEFAULT NULL AFTER `reclamada`,
                ADD COLUMN `reclamante_id` bigint(255) DEFAULT NULL AFTER `reclamante`,
                ADD KEY `idx_reclamada_id` (`reclamada_id`),
                ADD KEY `idx_reclamante_id` (`reclamante_id`)";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "ALTER TABLE `quesitos` 
                DROP COLUMN `reclamada_id`,
                DROP COLUMN `reclamante_id`";

        $this->executeQuery($sql);
    }
}
