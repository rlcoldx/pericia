<?php

namespace Agencia\Close\Migrations;

class CreatePagamentosRecebimentosTable extends Migration
{
    public function up(): void
    {
        // Tabela de Recebimentos
        $sqlRecebimentos = "CREATE TABLE IF NOT EXISTS `recebimentos` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `conta_receber_id` bigint(255) DEFAULT NULL COMMENT 'Vinculado a uma conta a receber',
            `fatura_id` bigint(255) DEFAULT NULL COMMENT 'Vinculado a uma fatura',
            `agendamento_id` bigint(255) DEFAULT NULL COMMENT 'Vinculado a um agendamento',
            `descricao` varchar(255) NOT NULL,
            `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
            `data_recebimento` date NOT NULL,
            `data_credito` date DEFAULT NULL COMMENT 'Data de crédito na conta',
            `forma_pagamento` enum('Dinheiro','PIX','Transferência','Boleto','Cartão Crédito','Cartão Débito','Cheque','Outro') DEFAULT 'Transferência',
            `status` enum('Confirmado','Pendente','Cancelado') DEFAULT 'Confirmado',
            `numero_comprovante` varchar(255) DEFAULT NULL COMMENT 'Número do comprovante',
            `observacoes` text DEFAULT NULL,
            `arquivo_comprovante` varchar(255) DEFAULT NULL COMMENT 'Caminho do arquivo do comprovante',
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_empresa` (`empresa`),
            KEY `idx_conta_receber_id` (`conta_receber_id`),
            KEY `idx_fatura_id` (`fatura_id`),
            KEY `idx_agendamento_id` (`agendamento_id`),
            KEY `idx_status` (`status`),
            KEY `idx_data_recebimento` (`data_recebimento`),
            CONSTRAINT `fk_recebimentos_conta_receber` FOREIGN KEY (`conta_receber_id`) REFERENCES `contas_receber` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_recebimentos_fatura` FOREIGN KEY (`fatura_id`) REFERENCES `faturas` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_recebimentos_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sqlRecebimentos);

        // Tabela de Pagamentos (para pagamentos a fornecedores, assistentes, etc)
        $sqlPagamentos = "CREATE TABLE IF NOT EXISTS `pagamentos` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `agendamento_id` bigint(255) DEFAULT NULL COMMENT 'Vinculado a um agendamento (ex: pagamento ao assistente)',
            `descricao` varchar(255) NOT NULL,
            `beneficiario` varchar(255) NOT NULL COMMENT 'Nome do beneficiário',
            `beneficiario_documento` varchar(20) DEFAULT NULL COMMENT 'CPF/CNPJ do beneficiário',
            `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
            `data_pagamento` date NOT NULL,
            `data_vencimento` date DEFAULT NULL,
            `forma_pagamento` enum('Dinheiro','PIX','Transferência','Boleto','Cartão Crédito','Cartão Débito','Cheque','Outro') DEFAULT 'Transferência',
            `status` enum('Pendente','Pago','Cancelado') DEFAULT 'Pendente',
            `tipo` enum('Assistente','Fornecedor','Serviço','Outro') DEFAULT 'Assistente',
            `numero_comprovante` varchar(255) DEFAULT NULL,
            `observacoes` text DEFAULT NULL,
            `arquivo_comprovante` varchar(255) DEFAULT NULL,
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_empresa` (`empresa`),
            KEY `idx_agendamento_id` (`agendamento_id`),
            KEY `idx_status` (`status`),
            KEY `idx_data_pagamento` (`data_pagamento`),
            CONSTRAINT `fk_pagamentos_agendamento` FOREIGN KEY (`agendamento_id`) REFERENCES `agendamentos` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sqlPagamentos);
    }

    public function down(): void
    {
        $sql1 = "DROP TABLE IF EXISTS `pagamentos`";
        $sql2 = "DROP TABLE IF EXISTS `recebimentos`";
        
        $this->executeQuery($sql1);
        $this->executeQuery($sql2);
    }
}

