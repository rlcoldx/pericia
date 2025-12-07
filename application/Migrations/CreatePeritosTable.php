<?php

namespace Agencia\Close\Migrations;

class CreatePeritosTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `peritos` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `nome` varchar(255) NOT NULL,
            `email` varchar(255) DEFAULT NULL,
            `telefone` varchar(20) DEFAULT NULL,
            `tipo_documento` enum('CPF','CNPJ') DEFAULT 'CPF',
            `documento` varchar(18) DEFAULT NULL,
            `especialidade` varchar(255) DEFAULT NULL,
            `registro_profissional` varchar(50) DEFAULT NULL,
            `tipo_registro` varchar(50) DEFAULT NULL,
            `status` enum('Ativo','Inativo') DEFAULT 'Ativo',
            `observacoes` text DEFAULT NULL,
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_empresa` (`empresa`),
            KEY `idx_status` (`status`),
            KEY `idx_nome` (`nome`),
            KEY `idx_documento` (`documento`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `peritos`";
        $this->executeQuery($sql);
    }
}

