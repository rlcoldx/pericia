<?php

namespace Agencia\Close\Migrations;

class CreateEmailTemplatesTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `email_templates` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `tipo` varchar(100) NOT NULL COMMENT 'quesito_criar, quesito_editar, parecer_criar, perito_criar, etc.',
            `assunto` varchar(255) NOT NULL,
            `corpo` text NOT NULL,
            `ativo` tinyint(1) NOT NULL DEFAULT 1,
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_empresa` (`empresa`),
            KEY `idx_tipo` (`tipo`),
            KEY `idx_ativo` (`ativo`),
            UNIQUE KEY `idx_empresa_tipo` (`empresa`, `tipo`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `email_templates`";
        $this->executeQuery($sql);
    }
}
