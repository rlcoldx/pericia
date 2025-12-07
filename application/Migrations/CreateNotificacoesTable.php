<?php

namespace Agencia\Close\Migrations;

class CreateNotificacoesTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `notificacoes` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `usuario_id` bigint(255) NOT NULL,
            `titulo` varchar(255) NOT NULL,
            `mensagem` text NOT NULL,
            `url` varchar(500) DEFAULT NULL,
            `lido` tinyint(1) NOT NULL DEFAULT 0,
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_usuario_id` (`usuario_id`),
            KEY `idx_lido` (`lido`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `notificacoes`";
        $this->executeQuery($sql);
    }
}

