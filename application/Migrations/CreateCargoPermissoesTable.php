<?php

namespace Agencia\Close\Migrations;

class CreateCargoPermissoesTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `cargo_permissoes` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `cargo_id` bigint(255) NOT NULL,
            `permissao_id` int(11) NOT NULL,
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_cargo_id` (`cargo_id`),
            KEY `idx_permissao_id` (`permissao_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `cargo_permissoes`";
        $this->executeQuery($sql);
    }
}

