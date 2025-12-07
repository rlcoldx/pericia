<?php

namespace Agencia\Close\Migrations;

class AddEmailClienteEnviadoToQuesitos extends Migration
{
    public function up(): void
    {
        $sql = "ALTER TABLE `quesitos`
                ADD COLUMN `email_cliente_enviado` tinyint(1) NOT NULL DEFAULT 0 AFTER `enviar_para_cliente`,
                ADD COLUMN `email_cliente_data_envio` timestamp NULL DEFAULT NULL AFTER `email_cliente_enviado`";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "ALTER TABLE `quesitos`
                DROP COLUMN `email_cliente_enviado`,
                DROP COLUMN `email_cliente_data_envio`";

        $this->executeQuery($sql);
    }
}
