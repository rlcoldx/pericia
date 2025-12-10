<?php

namespace Agencia\Close\Migrations;

class CreateAssistentesTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `assistentes` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `nome` varchar(255) NOT NULL,
            `nome_contato` varchar(255) DEFAULT NULL,
            `email_contato` varchar(255) DEFAULT NULL,
            `telefone_contato` varchar(50) DEFAULT NULL,
            `profissao` varchar(255) DEFAULT NULL,
            `credencial` varchar(255) DEFAULT NULL,
            `numero_credencial` varchar(100) DEFAULT NULL,
            `cidade_estado` varchar(255) DEFAULT NULL,
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_empresa` (`empresa`),
            KEY `idx_nome` (`nome`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `assistentes`";
        $this->executeQuery($sql);
    }
}
