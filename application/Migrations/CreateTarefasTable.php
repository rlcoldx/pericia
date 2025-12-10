<?php

namespace Agencia\Close\Migrations;

class CreateTarefasTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `tarefas` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `modulo` varchar(50) NOT NULL COMMENT 'quesito, manifestacao, parecer, agendamento',
            `registro_id` bigint(255) NOT NULL COMMENT 'ID do registro do módulo',
            `concluido` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Se a tarefa foi concluída',
            `usuario_responsavel_id` bigint(255) DEFAULT NULL COMMENT 'ID do usuário responsável pela próxima tarefa',
            `data_conclusao` date DEFAULT NULL COMMENT 'Data prevista para conclusão da próxima tarefa',
            `usuario_concluiu_id` bigint(255) DEFAULT NULL COMMENT 'ID do usuário que marcou como concluído',
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            `data_update` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_empresa` (`empresa`),
            KEY `idx_modulo_registro` (`modulo`, `registro_id`),
            KEY `idx_usuario_responsavel` (`usuario_responsavel_id`),
            KEY `idx_concluido` (`concluido`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `tarefas`";
        $this->executeQuery($sql);
    }
}
