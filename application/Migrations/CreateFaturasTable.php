<?php

namespace Agencia\Close\Migrations;

class CreateFaturasTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `faturas` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `agendamento_id` bigint(255) NOT NULL COMMENT 'Perícia vinculada',
            `numero_fatura` varchar(50) NOT NULL COMMENT 'Número da fatura',
            `cliente_nome` varchar(255) NOT NULL,
            `cliente_documento` varchar(20) DEFAULT NULL,
            `cliente_endereco` text DEFAULT NULL,
            `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
            `valor_desconto` decimal(10,2) DEFAULT 0.00,
            `valor_acrescimo` decimal(10,2) DEFAULT 0.00,
            `valor_liquido` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Valor líquido após descontos/acréscimos',
            `data_emissao` date NOT NULL,
            `data_vencimento` date NOT NULL,
            `status` enum('Rascunho','Emitida','Enviada','Cancelada') DEFAULT 'Rascunho',
            `tipo_fatura` enum('Nota Fiscal','Recibo','Boleto','Outro') DEFAULT 'Nota Fiscal',
            `observacoes` text DEFAULT NULL,
            `arquivo_pdf` varchar(255) DEFAULT NULL COMMENT 'Caminho do arquivo PDF gerado',
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_numero_fatura_empresa` (`numero_fatura`, `empresa`),
            KEY `idx_empresa` (`empresa`),
            KEY `idx_agendamento_id` (`agendamento_id`),
            KEY `idx_status` (`status`),
            KEY `idx_data_emissao` (`data_emissao`),
            CONSTRAINT `fk_faturas_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `faturas`";
        $this->executeQuery($sql);
    }
}

