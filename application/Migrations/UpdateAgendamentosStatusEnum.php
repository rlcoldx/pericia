<?php

namespace Agencia\Close\Migrations;

class UpdateAgendamentosStatusEnum extends Migration
{
    public function up(): void
    {
        // Primeiro, atualizar os dados existentes para os novos valores
        // Mapear valores antigos para novos
        $mapeamentos = [
            'Pendente' => 'Agendado',
            'Realizado' => 'Perícia Realizada',
            'Cancelado' => 'Não Realizada',
            'Aprovado' => 'Agendado',
            'Rejeitado' => 'Não Realizada'
        ];

        foreach ($mapeamentos as $antigo => $novo) {
            $this->executeQuery("UPDATE `agendamentos` SET `status` = '{$novo}' WHERE `status` = '{$antigo}'");
        }

        // Alterar o ENUM para os novos valores
        $this->executeQuery("ALTER TABLE `agendamentos` 
                      MODIFY COLUMN `status` enum('Agendado','Perícia Realizada','Não Realizada','Parecer para Revisar') 
                      DEFAULT 'Agendado'");
    }

    public function down(): void
    {
        // Reverter os mapeamentos
        $mapeamentos = [
            'Agendado' => 'Pendente', // Pode ter vindo de Pendente ou Aprovado, usar Pendente como padrão
            'Perícia Realizada' => 'Realizado',
            'Não Realizada' => 'Cancelado', // Pode ter vindo de Cancelado ou Rejeitado, usar Cancelado como padrão
            'Parecer para Revisar' => 'Pendente'
        ];

        foreach ($mapeamentos as $novo => $antigo) {
            $this->executeQuery("UPDATE `agendamentos` SET `status` = '{$antigo}' WHERE `status` = '{$novo}'");
        }

        // Reverter o ENUM para os valores antigos
        $this->executeQuery("ALTER TABLE `agendamentos` 
                      MODIFY COLUMN `status` enum('Pendente','Agendado','Realizado','Cancelado','Aprovado','Rejeitado') 
                      DEFAULT 'Pendente'");
    }
}
