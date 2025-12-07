<?php

namespace Agencia\Close\Migrations;

class AddEmailClienteToQuesitos extends Migration
{
    public function up(): void
    {
        // Adiciona coluna email_cliente na tabela quesitos, se ainda não existir
        $sql = "ALTER TABLE `quesitos`
                ADD COLUMN `email_cliente` varchar(255) NULL AFTER `codigo_reclamante`";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "ALTER TABLE `quesitos`
                DROP COLUMN `email_cliente`";

        $this->executeQuery($sql);
    }
}

