<?php

namespace Agencia\Close\Migrations;

class AddTarefaTextoToTarefas extends Migration
{
    public function up(): void
    {
        $queries = [];

        // Adiciona tarefa_texto se ainda não existir
        if (!$this->columnExists('tarefas', 'tarefa_texto')) {
            $queries[] = "ALTER TABLE `tarefas`
                          ADD COLUMN `tarefa_texto` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_unicode_ci' AFTER `data_conclusao`";
        }

        // Executa apenas se houver algo para criar
        if (!empty($queries)) {
            $this->executeQueries($queries);
        }
    }

    public function down(): void
    {
        $queries = [];

        if ($this->columnExists('tarefas', 'tarefa_texto')) {
            $queries[] = "ALTER TABLE `tarefas` DROP COLUMN `tarefa_texto`";
        }

        if (!empty($queries)) {
            $this->executeQueries($queries);
        }
    }
}
