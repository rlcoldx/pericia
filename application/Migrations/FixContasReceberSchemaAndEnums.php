<?php

namespace Agencia\Close\Migrations;

/**
 * Garante colunas extras de contas_receber e converte ENUM problemáticos para VARCHAR.
 * Evita "Data truncated" em tipo/status e "Unknown column" quando migrations antigas não rodaram.
 */
class FixContasReceberSchemaAndEnums extends Migration
{
    public function up(): void
    {
        if (!$this->tableExists('contas_receber')) {
            return;
        }

        $adds = [
            'etapa' => "ALTER TABLE `contas_receber` ADD COLUMN `etapa` varchar(50) DEFAULT 'PERICIA' COMMENT 'Etapa do processo' AFTER `tipo`",
            'situacao' => "ALTER TABLE `contas_receber` ADD COLUMN `situacao` varchar(255) DEFAULT NULL COMMENT 'Situação' AFTER `status`",
            'data_situacao' => "ALTER TABLE `contas_receber` ADD COLUMN `data_situacao` date DEFAULT NULL COMMENT 'Data da situação' AFTER `situacao`",
            'local_pericia' => "ALTER TABLE `contas_receber` ADD COLUMN `local_pericia` varchar(500) DEFAULT NULL COMMENT 'Local da perícia' AFTER `cliente_nome`",
            'reclamante_nome' => "ALTER TABLE `contas_receber` ADD COLUMN `reclamante_nome` varchar(255) DEFAULT NULL COMMENT 'Reclamante' AFTER `local_pericia`",
            'numero_processo' => "ALTER TABLE `contas_receber` ADD COLUMN `numero_processo` varchar(255) DEFAULT NULL COMMENT 'Número do processo' AFTER `reclamante_nome`",
            'data_pericia' => "ALTER TABLE `contas_receber` ADD COLUMN `data_pericia` date DEFAULT NULL COMMENT 'Data da perícia' AFTER `data_emissao`",
            'assistente_nome' => "ALTER TABLE `contas_receber` ADD COLUMN `assistente_nome` varchar(255) DEFAULT NULL COMMENT 'Assistente' AFTER `numero_boleto`",
            'valor_assistente' => "ALTER TABLE `contas_receber` ADD COLUMN `valor_assistente` decimal(10,2) DEFAULT NULL COMMENT 'Valor assistente' AFTER `assistente_nome`",
            'data_envio' => "ALTER TABLE `contas_receber` ADD COLUMN `data_envio` date DEFAULT NULL COMMENT 'Data envio NF/boleto' AFTER `data_vencimento`",
            'numero_pedido_cliente' => "ALTER TABLE `contas_receber` ADD COLUMN `numero_pedido_cliente` varchar(255) DEFAULT NULL COMMENT 'Nº pedido' AFTER `numero_nota_fiscal`",
        ];

        foreach ($adds as $column => $sql) {
            if (!$this->columnExists('contas_receber', $column)) {
                $this->executeQuery($sql);
            }
        }

        if ($this->columnExists('contas_receber', 'tipo')) {
            $this->executeQuery(
                "ALTER TABLE `contas_receber`
                 MODIFY COLUMN `tipo` varchar(80) NULL DEFAULT 'Perícia' COMMENT 'Tipo da conta'"
            );
        }

        if ($this->columnExists('contas_receber', 'status')) {
            $this->executeQuery(
                "ALTER TABLE `contas_receber`
                 MODIFY COLUMN `status` varchar(40) NULL DEFAULT 'Pendente' COMMENT 'Status financeiro'"
            );
        }

        if ($this->columnExists('contas_receber', 'local_pericia')) {
            $this->executeQuery(
                "ALTER TABLE `contas_receber`
                 MODIFY COLUMN `local_pericia` varchar(500) NULL DEFAULT NULL COMMENT 'Local da perícia'"
            );
        }
    }

    public function down(): void
    {
        // Não reverte ENUM automaticamente.
    }
}
