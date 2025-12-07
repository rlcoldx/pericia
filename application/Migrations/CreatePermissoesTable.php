<?php

namespace Agencia\Close\Migrations;

class CreatePermissoesTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `permissoes` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `nome` varchar(255) DEFAULT NULL,
            `titulo` varchar(255) DEFAULT NULL,
            `descricao` varchar(255) DEFAULT NULL,
            `grupo` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `permissoes`";
        $this->executeQuery($sql);
    }
}

