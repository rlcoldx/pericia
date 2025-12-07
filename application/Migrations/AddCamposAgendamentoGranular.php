<?php

namespace Agencia\Close\Migrations;

class AddCamposAgendamentoGranular extends Migration
{
    public function up(): void
    {
        // Campos do CLOVIS
        $queries = [];
        
        // Verifica e adiciona cada campo se não existir
        if (!$this->columnExists('agendamentos', 'data_entrada')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `data_entrada` date DEFAULT NULL COMMENT 'Data que o cliente solicitou o serviço' AFTER `empresa`";
        }
        
        if (!$this->columnExists('agendamentos', 'numero_processo')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `numero_processo` varchar(255) DEFAULT NULL COMMENT 'Número do processo' AFTER `cliente_cpf`";
        }
        
        if (!$this->columnExists('agendamentos', 'vara')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `vara` varchar(255) DEFAULT NULL COMMENT 'Vara/Órgão' AFTER `numero_processo`";
        }
        
        if (!$this->columnExists('agendamentos', 'reclamante_nome')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `reclamante_nome` varchar(255) DEFAULT NULL COMMENT 'Nome do reclamante' AFTER `vara`";
        }
        
        if (!$this->columnExists('agendamentos', 'valor_pericia_cobrado')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `valor_pericia_cobrado` decimal(10,2) DEFAULT NULL COMMENT 'Valor cobrado pela perícia' AFTER `reclamante_nome`";
        }
        
        if (!$this->columnExists('agendamentos', 'numero_tipo_pericia')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `numero_tipo_pericia` varchar(50) DEFAULT NULL COMMENT 'Número do tipo de perícia' AFTER `tipo_pericia`";
        }
        
        if (!$this->columnExists('agendamentos', 'assistente_nome')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `assistente_nome` varchar(255) DEFAULT NULL COMMENT 'Nome do assistente/prestador de serviço' AFTER `perito_id`";
        }
        
        if (!$this->columnExists('agendamentos', 'valor_pago_assistente')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `valor_pago_assistente` decimal(10,2) DEFAULT NULL COMMENT 'Valor a ser pago ao assistente' AFTER `assistente_nome`";
        }
        
        // Campos do MARCELO
        if (!$this->columnExists('agendamentos', 'data_realizada')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `data_realizada` date DEFAULT NULL COMMENT 'Data que foi realizada a perícia' AFTER `data_agendamento`";
        }
        
        if (!$this->columnExists('agendamentos', 'data_fatal')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `data_fatal` date DEFAULT NULL COMMENT 'Data fatal' AFTER `data_realizada`";
        }
        
        if (!$this->columnExists('agendamentos', 'data_entrega_parecer')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `data_entrega_parecer` date DEFAULT NULL COMMENT 'Data de entrega do parecer' AFTER `data_fatal`";
        }
        
        if (!$this->columnExists('agendamentos', 'status_parecer')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `status_parecer` enum('OK','FAVORAVEL','DESFAVORAVEL','PARCIAL FAVORAVEL','NR (NÃO REALIZADO)') DEFAULT NULL COMMENT 'Status do parecer' AFTER `data_entrega_parecer`";
        }
        
        if (!$this->columnExists('agendamentos', 'obs_parecer')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `obs_parecer` text DEFAULT NULL COMMENT 'Observações referentes ao status do parecer' AFTER `status_parecer`";
        }
        
        // Campos do MAURO
        if (!$this->columnExists('agendamentos', 'data_pagamento_assistente')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `data_pagamento_assistente` date DEFAULT NULL COMMENT 'Data do pagamento do assistente' AFTER `valor_pago_assistente`";
        }
        
        if (!$this->columnExists('agendamentos', 'numero_pedido_cliente')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `numero_pedido_cliente` varchar(255) DEFAULT NULL COMMENT 'Número do pedido do cliente' AFTER `data_pagamento_assistente`";
        }
        
        if (!$this->columnExists('agendamentos', 'numero_nota_fiscal')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `numero_nota_fiscal` varchar(255) DEFAULT NULL COMMENT 'Número da nota fiscal' AFTER `numero_pedido_cliente`";
        }
        
        if (!$this->columnExists('agendamentos', 'numero_boleto')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `numero_boleto` varchar(255) DEFAULT NULL COMMENT 'Número do boleto' AFTER `numero_nota_fiscal`";
        }
        
        if (!$this->columnExists('agendamentos', 'data_envio_financeiro')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `data_envio_financeiro` date DEFAULT NULL COMMENT 'Data de envio da NF e boleto para o cliente' AFTER `numero_boleto`";
        }
        
        if (!$this->columnExists('agendamentos', 'data_vencimento_financeiro')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `data_vencimento_financeiro` date DEFAULT NULL COMMENT 'Prazo acordado com o cliente' AFTER `data_envio_financeiro`";
        }
        
        if (!$this->columnExists('agendamentos', 'status_pagamento')) {
            $queries[] = "ALTER TABLE `agendamentos` ADD COLUMN `status_pagamento` varchar(50) DEFAULT NULL COMMENT 'Status do pagamento' AFTER `data_vencimento_financeiro`";
        }
        
        // Executa todas as queries de ALTER TABLE primeiro
        if (!empty($queries)) {
            $this->executeQueries($queries);
        }
        
        // Atualiza tipo_pericia para ENUM com as opções corretas
        // Primeiro, atualiza valores inválidos para NULL antes de alterar para ENUM
        // Executa fora da transação pois ALTER TABLE pode causar problemas
        if ($this->columnExists('agendamentos', 'tipo_pericia')) {
            try {
                // Limpa valores inválidos
                $this->executeQuery("UPDATE `agendamentos` SET `tipo_pericia` = NULL WHERE `tipo_pericia` IS NOT NULL AND `tipo_pericia` NOT IN ('MEDIACA','TECNICA','ERGONO','CINESIO','VISTORIA','X1','X2')");
                
                // Altera para ENUM
                $this->executeQuery("ALTER TABLE `agendamentos` MODIFY COLUMN `tipo_pericia` enum('MEDIACA','TECNICA','ERGONO','CINESIO','VISTORIA','X1','X2') DEFAULT NULL COMMENT 'Tipo de perícia'");
            } catch (\Exception $e) {
                // Se falhar, tenta apenas alterar a coluna (pode já estar limpa)
                try {
                    $this->executeQuery("ALTER TABLE `agendamentos` MODIFY COLUMN `tipo_pericia` enum('MEDIACA','TECNICA','ERGONO','CINESIO','VISTORIA','X1','X2') DEFAULT NULL COMMENT 'Tipo de perícia'");
                } catch (\Exception $e2) {
                    // Ignora se já estiver no formato correto
                }
            }
        }
    }

    public function down(): void
    {
        $queries = [];
        
        // Remove campos do CLOVIS
        if ($this->columnExists('agendamentos', 'data_entrada')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `data_entrada`";
        }
        if ($this->columnExists('agendamentos', 'numero_processo')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `numero_processo`";
        }
        if ($this->columnExists('agendamentos', 'vara')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `vara`";
        }
        if ($this->columnExists('agendamentos', 'reclamante_nome')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `reclamante_nome`";
        }
        if ($this->columnExists('agendamentos', 'valor_pericia_cobrado')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `valor_pericia_cobrado`";
        }
        if ($this->columnExists('agendamentos', 'numero_tipo_pericia')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `numero_tipo_pericia`";
        }
        if ($this->columnExists('agendamentos', 'assistente_nome')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `assistente_nome`";
        }
        if ($this->columnExists('agendamentos', 'valor_pago_assistente')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `valor_pago_assistente`";
        }
        
        // Remove campos do MARCELO
        if ($this->columnExists('agendamentos', 'data_realizada')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `data_realizada`";
        }
        if ($this->columnExists('agendamentos', 'data_fatal')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `data_fatal`";
        }
        if ($this->columnExists('agendamentos', 'data_entrega_parecer')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `data_entrega_parecer`";
        }
        if ($this->columnExists('agendamentos', 'status_parecer')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `status_parecer`";
        }
        if ($this->columnExists('agendamentos', 'obs_parecer')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `obs_parecer`";
        }
        
        // Remove campos do MAURO
        if ($this->columnExists('agendamentos', 'data_pagamento_assistente')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `data_pagamento_assistente`";
        }
        if ($this->columnExists('agendamentos', 'numero_pedido_cliente')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `numero_pedido_cliente`";
        }
        if ($this->columnExists('agendamentos', 'numero_nota_fiscal')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `numero_nota_fiscal`";
        }
        if ($this->columnExists('agendamentos', 'numero_boleto')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `numero_boleto`";
        }
        if ($this->columnExists('agendamentos', 'data_envio_financeiro')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `data_envio_financeiro`";
        }
        if ($this->columnExists('agendamentos', 'data_vencimento_financeiro')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `data_vencimento_financeiro`";
        }
        if ($this->columnExists('agendamentos', 'status_pagamento')) {
            $queries[] = "ALTER TABLE `agendamentos` DROP COLUMN `status_pagamento`";
        }
        
        // Reverte tipo_pericia para varchar
        $queries[] = "ALTER TABLE `agendamentos` MODIFY COLUMN `tipo_pericia` varchar(255) DEFAULT NULL";
        
        if (!empty($queries)) {
            $this->executeQueries($queries);
        }
    }
}

