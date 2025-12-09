<?php

namespace Agencia\Close\Migrations;

class AddEmailClienteCcToQuesitos extends Migration
{
    public function up(): void
    {
        $queries = [];

        // Adiciona email_cliente_cc se ainda não existir
        if (!$this->columnExists('quesitos', 'email_cliente_cc')) {
            $queries[] = "ALTER TABLE `quesitos`
                          ADD COLUMN `email_cliente_cc` text DEFAULT NULL AFTER `email_cliente`";
        }

        // Executa apenas se houver algo para criar
        if (!empty($queries)) {
            $this->executeQueries($queries);
        }
    }

    public function down(): void
    {
        $queries = [];

        if ($this->columnExists('quesitos', 'email_cliente_cc')) {
            $queries[] = "ALTER TABLE `quesitos` DROP COLUMN `email_cliente_cc`";
        }

        if (!empty($queries)) {
            $this->executeQueries($queries);
        }
    }
}
