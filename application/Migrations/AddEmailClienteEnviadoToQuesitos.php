<?php

namespace Agencia\Close\Migrations;

class AddEmailClienteEnviadoToQuesitos extends Migration
{
    public function up(): void
    {
        $queries = [];

        // Adiciona email_cliente_enviado se ainda não existir
        if (!$this->columnExists('quesitos', 'email_cliente_enviado')) {
            $queries[] = "ALTER TABLE `quesitos`
                          ADD COLUMN `email_cliente_enviado` tinyint(1) NOT NULL DEFAULT 0 AFTER `enviar_para_cliente`";
        }

        // Adiciona email_cliente_data_envio se ainda não existir
        if (!$this->columnExists('quesitos', 'email_cliente_data_envio')) {
            // Se já adicionou/encontrou email_cliente_enviado, coloca após ele; senão, coloca após enviar_para_cliente
            $after = $this->columnExists('quesitos', 'email_cliente_enviado')
                ? ' AFTER `email_cliente_enviado`'
                : ' AFTER `enviar_para_cliente`';

            $queries[] = "ALTER TABLE `quesitos`
                          ADD COLUMN `email_cliente_data_envio` timestamp NULL DEFAULT NULL{$after}";
        }

        // Executa apenas se houver algo para criar
        if (!empty($queries)) {
            $this->executeQueries($queries);
        }
    }

    public function down(): void
    {
        $queries = [];

        if ($this->columnExists('quesitos', 'email_cliente_data_envio')) {
            $queries[] = "ALTER TABLE `quesitos` DROP COLUMN `email_cliente_data_envio`";
        }

        if ($this->columnExists('quesitos', 'email_cliente_enviado')) {
            $queries[] = "ALTER TABLE `quesitos` DROP COLUMN `email_cliente_enviado`";
        }

        if (!empty($queries)) {
            $this->executeQueries($queries);
        }
    }
}
