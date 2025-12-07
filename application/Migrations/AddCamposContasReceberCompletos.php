<?php

namespace Agencia\Close\Migrations;

class AddCamposContasReceberCompletos extends Migration
{
    public function up(): void
    {
        $queries = [];
        
        // Adiciona campo ETAPA (sempre "PERICIA" na planilha)
        if (!$this->columnExists('contas_receber', 'etapa')) {
            $queries[] = "ALTER TABLE `contas_receber` ADD COLUMN `etapa` varchar(50) DEFAULT 'PERICIA' COMMENT 'Etapa do processo' AFTER `tipo`";
        }
        
        // Adiciona campo SITUAÇÃO (status do parecer/situação)
        if (!$this->columnExists('contas_receber', 'situacao')) {
            $queries[] = "ALTER TABLE `contas_receber` ADD COLUMN `situacao` varchar(255) DEFAULT NULL COMMENT 'Situação (QUESITOS, IMPUGNAÇÃO, Parecer enviado, etc)' AFTER `status`";
        }
        
        // Adiciona campo DATA_SITUACAO (data da situação)
        if (!$this->columnExists('contas_receber', 'data_situacao')) {
            $queries[] = "ALTER TABLE `contas_receber` ADD COLUMN `data_situacao` date DEFAULT NULL COMMENT 'Data da situação (ex: data que parecer foi enviado)' AFTER `situacao`";
        }
        
        // Adiciona campo para armazenar dados do agendamento (cache para performance)
        if (!$this->columnExists('contas_receber', 'local_pericia')) {
            $queries[] = "ALTER TABLE `contas_receber` ADD COLUMN `local_pericia` varchar(255) DEFAULT NULL COMMENT 'Local da perícia (cache do agendamento)' AFTER `cliente_nome`";
        }
        
        if (!$this->columnExists('contas_receber', 'reclamante_nome')) {
            $queries[] = "ALTER TABLE `contas_receber` ADD COLUMN `reclamante_nome` varchar(255) DEFAULT NULL COMMENT 'Nome do reclamante (cache do agendamento)' AFTER `local_pericia`";
        }
        
        if (!$this->columnExists('contas_receber', 'numero_processo')) {
            $queries[] = "ALTER TABLE `contas_receber` ADD COLUMN `numero_processo` varchar(255) DEFAULT NULL COMMENT 'Número do processo (cache do agendamento)' AFTER `reclamante_nome`";
        }
        
        if (!$this->columnExists('contas_receber', 'data_pericia')) {
            $queries[] = "ALTER TABLE `contas_receber` ADD COLUMN `data_pericia` date DEFAULT NULL COMMENT 'Data que foi realizada a perícia (cache do agendamento)' AFTER `data_emissao`";
        }
        
        if (!$this->columnExists('contas_receber', 'assistente_nome')) {
            $queries[] = "ALTER TABLE `contas_receber` ADD COLUMN `assistente_nome` varchar(255) DEFAULT NULL COMMENT 'Nome do assistente (cache do agendamento)' AFTER `numero_boleto`";
        }
        
        if (!$this->columnExists('contas_receber', 'valor_assistente')) {
            $queries[] = "ALTER TABLE `contas_receber` ADD COLUMN `valor_assistente` decimal(10,2) DEFAULT NULL COMMENT 'Valor pago ao assistente (cache do agendamento)' AFTER `assistente_nome`";
        }
        
        if (!$this->columnExists('contas_receber', 'data_envio')) {
            $queries[] = "ALTER TABLE `contas_receber` ADD COLUMN `data_envio` date DEFAULT NULL COMMENT 'Data de envio da NF e boleto (cache do agendamento)' AFTER `data_vencimento`";
        }
        
        // Renomeia data_vencimento para data_prazo se necessário, ou mantém ambos
        // Na verdade, vamos manter data_vencimento e adicionar data_prazo como alias
        
        // Executa todas as queries
        if (!empty($queries)) {
            foreach ($queries as $query) {
                $this->executeQuery($query);
            }
        }
    }

    public function down(): void
    {
        $columns = [
            'etapa',
            'situacao',
            'data_situacao',
            'local_pericia',
            'reclamante_nome',
            'numero_processo',
            'data_pericia',
            'assistente_nome',
            'valor_assistente',
            'data_envio'
        ];
        
        foreach ($columns as $column) {
            if ($this->columnExists('contas_receber', $column)) {
                $this->executeQuery("ALTER TABLE `contas_receber` DROP COLUMN `{$column}`");
            }
        }
    }
}

