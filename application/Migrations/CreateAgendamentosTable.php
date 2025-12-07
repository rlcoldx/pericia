<?php

namespace Agencia\Close\Migrations;

class CreateAgendamentosTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `agendamentos` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `cliente_nome` varchar(255) NOT NULL,
            `cliente_email` varchar(255) DEFAULT NULL,
            `cliente_telefone` varchar(20) DEFAULT NULL,
            `cliente_cpf` varchar(14) DEFAULT NULL,
            `tipo_pericia` varchar(255) DEFAULT NULL,
            `data_agendamento` date NOT NULL,
            `hora_agendamento` time NOT NULL,
            `perito_id` bigint(255) DEFAULT NULL,
            `status` enum('Pendente','Agendado','Realizado','Cancelado','Aprovado','Rejeitado') DEFAULT 'Pendente',
            `observacoes` text DEFAULT NULL,
            `local_pericia` varchar(255) DEFAULT NULL,
            `aprovado_por` bigint(255) DEFAULT NULL,
            `data_aprovacao` datetime DEFAULT NULL,
            `motivo_rejeicao` text DEFAULT NULL,
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_empresa` (`empresa`),
            KEY `idx_perito_id` (`perito_id`),
            KEY `idx_status` (`status`),
            KEY `idx_data_agendamento` (`data_agendamento`),
            KEY `idx_aprovado_por` (`aprovado_por`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `agendamentos`";
        $this->executeQuery($sql);
    }
}

