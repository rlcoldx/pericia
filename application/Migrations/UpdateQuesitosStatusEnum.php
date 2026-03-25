<?php

namespace Agencia\Close\Migrations;

class UpdateQuesitosStatusEnum extends Migration
{
    public function up(): void
    {
        // Garante que a coluna status da tabela quesitos aceita todos os valores usados no código
        $this->executeQuery("
            ALTER TABLE `quesitos`
            MODIFY COLUMN `status` enum(
                'Pendente',
                'Finalizado',
                'Finalizado e Enviado',
                'Pendente de Envio',
                'Recusado'
            ) NOT NULL DEFAULT 'Pendente'
        ");
    }

    public function down(): void
    {
        // Reverte para o enum original mínimo (sem os novos status),
        // ajuste aqui se o seu enum antigo era diferente.
        $this->executeQuery("
            ALTER TABLE `quesitos`
            MODIFY COLUMN `status` enum(
                'Pendente',
                'Finalizado',
                'Recusado'
            ) NOT NULL DEFAULT 'Pendente'
        ");
    }
}

