<?php

namespace Agencia\Close\Migrations;

class CreateQuesitosTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `quesitos` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `data` date NOT NULL,
            `tipo` varchar(255) NOT NULL,
            `vara` varchar(255) NOT NULL,
            `reclamante` varchar(255) NOT NULL,
            `codigo_reclamante` varchar(255) DEFAULT NULL,
            `reclamada` varchar(255) NOT NULL,
            `link_pasta_drive` varchar(500) DEFAULT NULL,
            `enviar_para_cliente` tinyint(1) NOT NULL DEFAULT 0,
            `status` enum('Pendente','Finalizado','Pendente de Envio','Recusado') NOT NULL DEFAULT 'Pendente',
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_empresa` (`empresa`),
            KEY `idx_data` (`data`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `quesitos`";
        $this->executeQuery($sql);
    }
}

