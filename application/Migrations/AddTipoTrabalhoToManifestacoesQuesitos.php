<?php

namespace Agencia\Close\Migrations;

/**
 * Tipo de trabalho (opcional), Select2 com tags — mesmo padrão do campo Tipo.
 */
class AddTipoTrabalhoToManifestacoesQuesitos extends Migration
{
    public function up(): void
    {
        if ($this->tableExists('manifestacoes_impugnacoes')
            && !$this->columnExists('manifestacoes_impugnacoes', 'tipo_trabalho')) {
            $this->executeQuery(
                "ALTER TABLE `manifestacoes_impugnacoes`
                    ADD COLUMN `tipo_trabalho` varchar(255) DEFAULT NULL
                    COMMENT 'Tipo de trabalho (livre, distinto por empresa)'
                    AFTER `tipo`"
            );
        }

        if ($this->tableExists('quesitos')
            && !$this->columnExists('quesitos', 'tipo_trabalho')) {
            $this->executeQuery(
                "ALTER TABLE `quesitos`
                    ADD COLUMN `tipo_trabalho` varchar(255) DEFAULT NULL
                    COMMENT 'Tipo de trabalho (livre, distinto por empresa)'
                    AFTER `tipo`"
            );
        }
    }

    public function down(): void
    {
        if ($this->columnExists('manifestacoes_impugnacoes', 'tipo_trabalho')) {
            $this->executeQuery('ALTER TABLE `manifestacoes_impugnacoes` DROP COLUMN `tipo_trabalho`');
        }
        if ($this->columnExists('quesitos', 'tipo_trabalho')) {
            $this->executeQuery('ALTER TABLE `quesitos` DROP COLUMN `tipo_trabalho`');
        }
    }
}
