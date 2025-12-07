<?php

namespace Agencia\Close\Migrations;

class CreateMigrationsTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `migrations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `migration` varchar(255) NOT NULL,
            `batch` int(11) NOT NULL,
            `executed_at` timestamp NULL DEFAULT current_timestamp(),
            `execution_time` decimal(10,4) DEFAULT NULL,
            `status` enum('success','failed') DEFAULT 'success',
            `error_message` text DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `migration` (`migration`),
            KEY `idx_batch` (`batch`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `migrations`";
        $this->executeQuery($sql);
    }
}

