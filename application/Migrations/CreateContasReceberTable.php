<?php

namespace Agencia\Close\Migrations;

class CreateContasReceberTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `contas_receber` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `agendamento_id` bigint(255) DEFAULT NULL COMMENT 'Vinculado a um agendamento/perícia',
            `descricao` varchar(255) NOT NULL COMMENT 'Descrição da conta',
            `cliente_nome` varchar(255) NOT NULL COMMENT 'Nome do cliente',
            `cliente_documento` varchar(20) DEFAULT NULL COMMENT 'CPF/CNPJ do cliente',
            `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor total da conta',
            `valor_recebido` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor já recebido',
            `valor_pendente` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor pendente (calculado)',
            `data_vencimento` date NOT NULL COMMENT 'Data de vencimento',
            `data_emissao` date DEFAULT NULL COMMENT 'Data de emissão',
            `status` enum('Pendente','Parcial','Recebido','Cancelado','Vencido') DEFAULT 'Pendente',
            `tipo` enum('Perícia','Serviço','Outro') DEFAULT 'Perícia',
            `numero_nota_fiscal` varchar(255) DEFAULT NULL COMMENT 'Número da nota fiscal',
            `numero_boleto` varchar(255) DEFAULT NULL COMMENT 'Número do boleto',
            `observacoes` text DEFAULT NULL,
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_empresa` (`empresa`),
            KEY `idx_agendamento_id` (`agendamento_id`),
            KEY `idx_status` (`status`),
            KEY `idx_data_vencimento` (`data_vencimento`),
            KEY `idx_cliente_documento` (`cliente_documento`),
            CONSTRAINT `fk_contas_receber_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `contas_receber`";
        $this->executeQuery($sql);
    }
}

