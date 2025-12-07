<?php

namespace Agencia\Close\Migrations;

class CreateManifestacoesImpugnacoesTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `manifestacoes_impugnacoes` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `data` date NOT NULL,
            `tipo` varchar(255) NOT NULL,
            `numero` varchar(255) DEFAULT NULL,
            `reclamada_id` bigint(255) DEFAULT NULL,
            `reclamante_id` bigint(255) DEFAULT NULL,
            `favoravel` enum('FAV','DESFAV') DEFAULT NULL,
            `perito_id` bigint(255) DEFAULT NULL,
            `funcao_observacao` text DEFAULT NULL,
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_empresa` (`empresa`),
            KEY `idx_data` (`data`),
            KEY `idx_reclamada_id` (`reclamada_id`),
            KEY `idx_reclamante_id` (`reclamante_id`),
            KEY `idx_perito_id` (`perito_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `manifestacoes_impugnacoes`";
        $this->executeQuery($sql);
    }
}
