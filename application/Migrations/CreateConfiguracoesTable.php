<?php

namespace Agencia\Close\Migrations;

class CreateConfiguracoesTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `configuracoes` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `mail_host` varchar(255) DEFAULT NULL,
            `mail_email` varchar(255) DEFAULT NULL,
            `mail_user` varchar(255) DEFAULT NULL,
            `mail_password` varchar(255) DEFAULT NULL,
            `mail_cc` text DEFAULT NULL COMMENT 'Emails CC padrão separados por vírgula',
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_empresa` (`empresa`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `configuracoes`";
        $this->executeQuery($sql);
    }
}
