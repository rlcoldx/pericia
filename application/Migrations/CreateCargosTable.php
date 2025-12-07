<?php

namespace Agencia\Close\Migrations;

class CreateCargosTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `cargos` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `nome` varchar(255) NOT NULL,
            `descricao` text DEFAULT NULL,
            `status` enum('Ativo','Inativo') DEFAULT 'Ativo',
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_empresa` (`empresa`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `cargos`";
        $this->executeQuery($sql);
    }
}

