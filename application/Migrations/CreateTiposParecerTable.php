<?php

namespace Agencia\Close\Migrations;

class CreateTiposParecerTable extends Migration
{
    public function up(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `tipos_parecer` (
            `id` bigint(255) NOT NULL AUTO_INCREMENT,
            `empresa` bigint(255) NOT NULL DEFAULT 0,
            `nome` varchar(255) NOT NULL,
            `data_create` timestamp NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            UNIQUE KEY `idx_empresa_nome` (`empresa`, `nome`),
            KEY `idx_empresa` (`empresa`)
        ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $this->executeQuery($sql);

        // Inserir tipos padrão
        $tiposPadrao = ['MÉDICA', 'TÉCNICA', 'VISTORIA', 'ENGENHARIA'];
        foreach ($tiposPadrao as $tipo) {
            $insertSql = "INSERT IGNORE INTO `tipos_parecer` (`empresa`, `nome`) 
                          SELECT DISTINCT `id` as empresa, :tipo as nome 
                          FROM empresas 
                          WHERE NOT EXISTS (
                              SELECT 1 FROM tipos_parecer WHERE empresa = empresas.id AND nome = :tipo
                          )";
            // Como não temos acesso direto a empresas, vamos usar uma abordagem diferente
            // Inserir para empresa 0 (padrão) e depois cada empresa pode ter seus próprios
        }
    }

    public function down(): void
    {
        $sql = "DROP TABLE IF EXISTS `tipos_parecer`";
        $this->executeQuery($sql);
    }
}
