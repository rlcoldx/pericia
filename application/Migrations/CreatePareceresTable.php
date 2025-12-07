<?php

namespace Agencia\Close\Migrations;

class CreatePareceresTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `pareceres` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `data_realizacao` date NOT NULL,
            `data_fatal` date DEFAULT NULL,
            `tipo` varchar(255) NOT NULL,
            `assistente` varchar(255) DEFAULT NULL,
            `reclamada_id` bigint(255) DEFAULT NULL,
            `reclamante_id` bigint(255) DEFAULT NULL,
            `funcoes` text DEFAULT NULL,
            `observacoes` text DEFAULT NULL,
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_empresa` (`empresa`),
            KEY `idx_data_realizacao` (`data_realizacao`),
            KEY `idx_reclamada_id` (`reclamada_id`),
            KEY `idx_reclamante_id` (`reclamante_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `pareceres`";
        $this->executeQuery($sql);
    }
}
