<?php

namespace Agencia\Close\Migrations;

/**
 * Mesmo conjunto de status dos quesitos.
 */
class AddStatusToManifestacoesImpugnacoes extends Migration
{
    public function up(): void
    {
        if (!$this->tableExists('manifestacoes_impugnacoes')) {
            return;
        }

        if ($this->columnExists('manifestacoes_impugnacoes', 'status')) {
            return;
        }

        $this->executeQuery(
            "ALTER TABLE `manifestacoes_impugnacoes`
                ADD COLUMN `status` ENUM('Pendente','Finalizado','Finalizado e Enviado','Pendente de Envio','Recusado')
                NOT NULL DEFAULT 'Pendente'
                AFTER `funcao_observacao`"
        );
    }

    public function down(): void
    {
        if ($this->columnExists('manifestacoes_impugnacoes', 'status')) {
            $this->executeQuery('ALTER TABLE `manifestacoes_impugnacoes` DROP COLUMN `status`');
        }
    }
}
