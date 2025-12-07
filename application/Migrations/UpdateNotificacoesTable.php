<?php

namespace Agencia\Close\Migrations;

class UpdateNotificacoesTable extends Migration
{
    public function up(): void
    {
        // Adicionar novos campos à tabela notificacoes
        $sql = "ALTER TABLE `notificacoes`
                ADD COLUMN `tipo` varchar(100) DEFAULT NULL AFTER `usuario_id`,
                ADD COLUMN `modulo` varchar(100) DEFAULT NULL AFTER `tipo`,
                ADD COLUMN `acao` varchar(100) DEFAULT NULL AFTER `modulo`,
                ADD COLUMN `registro_id` bigint(255) DEFAULT NULL AFTER `acao`,
                ADD COLUMN `email_enviado` tinyint(1) NOT NULL DEFAULT 0 AFTER `lido`,
                ADD COLUMN `data_email_enviado` timestamp NULL DEFAULT NULL AFTER `email_enviado`,
                ADD KEY `idx_tipo` (`tipo`),
                ADD KEY `idx_modulo` (`modulo`),
                ADD KEY `idx_email_enviado` (`email_enviado`)";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "ALTER TABLE `notificacoes`
                DROP COLUMN `tipo`,
                DROP COLUMN `modulo`,
                DROP COLUMN `acao`,
                DROP COLUMN `registro_id`,
                DROP COLUMN `email_enviado`,
                DROP COLUMN `data_email_enviado`";

        $this->executeQuery($sql);
    }
}
