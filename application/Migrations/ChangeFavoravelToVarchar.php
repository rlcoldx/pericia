<?php

namespace Agencia\Close\Migrations;

class ChangeFavoravelToVarchar extends Migration
{
    public function up(): void
    {
        $queries = [];

        // Verificar se a coluna existe e é ENUM
        if ($this->columnExists('manifestacoes_impugnacoes', 'favoravel')) {
            // Primeiro, atualizar os dados existentes (FAV -> Favorável, DESFAV -> Desfavorável)
            $queries[] = "UPDATE `manifestacoes_impugnacoes` SET `favoravel` = 'Favorável' WHERE `favoravel` = 'FAV'";
            $queries[] = "UPDATE `manifestacoes_impugnacoes` SET `favoravel` = 'Desfavorável' WHERE `favoravel` = 'DESFAV'";
            
            // Alterar a coluna de ENUM para VARCHAR
            $queries[] = "ALTER TABLE `manifestacoes_impugnacoes` 
                          MODIFY COLUMN `favoravel` varchar(50) DEFAULT NULL";
        }

        // Executa apenas se houver algo para fazer
        if (!empty($queries)) {
            $this->executeQueries($queries);
        }
    }

    public function down(): void
    {
        $queries = [];

        if ($this->columnExists('manifestacoes_impugnacoes', 'favoravel')) {
            // Converter de volta para códigos antes de alterar para ENUM
            $queries[] = "UPDATE `manifestacoes_impugnacoes` SET `favoravel` = 'FAV' WHERE `favoravel` = 'Favorável'";
            $queries[] = "UPDATE `manifestacoes_impugnacoes` SET `favoravel` = 'DESFAV' WHERE `favoravel` = 'Desfavorável'";
            $queries[] = "UPDATE `manifestacoes_impugnacoes` SET `favoravel` = NULL WHERE `favoravel` = 'Parcialmente Favorável'";
            
            // Alterar de volta para ENUM
            $queries[] = "ALTER TABLE `manifestacoes_impugnacoes` 
                          MODIFY COLUMN `favoravel` enum('FAV','DESFAV') DEFAULT NULL";
        }

        if (!empty($queries)) {
            $this->executeQueries($queries);
        }
    }
}
