<?php

namespace Agencia\Close\Migrations;

class ChangeFavoravelToVarchar extends Migration
{
    public function up(): void
    {
        // Verificar se a coluna existe e é ENUM
        if ($this->columnExists('manifestacoes_impugnacoes', 'favoravel')) {
            // Primeiro, atualizar os dados existentes (FAV -> Favorável, DESFAV -> Desfavorável)
            $this->executeQuery("UPDATE `manifestacoes_impugnacoes` SET `favoravel` = 'Favorável' WHERE `favoravel` = 'FAV'");
            $this->executeQuery("UPDATE `manifestacoes_impugnacoes` SET `favoravel` = 'Desfavorável' WHERE `favoravel` = 'DESFAV'");
            
            // Alterar a coluna de ENUM para VARCHAR
            $this->executeQuery("ALTER TABLE `manifestacoes_impugnacoes` 
                          MODIFY COLUMN `favoravel` varchar(50) DEFAULT NULL");
        }
    }

    public function down(): void
    {
        if ($this->columnExists('manifestacoes_impugnacoes', 'favoravel')) {
            // Converter de volta para códigos antes de alterar para ENUM
            $this->executeQuery("UPDATE `manifestacoes_impugnacoes` SET `favoravel` = 'FAV' WHERE `favoravel` = 'Favorável'");
            $this->executeQuery("UPDATE `manifestacoes_impugnacoes` SET `favoravel` = 'DESFAV' WHERE `favoravel` = 'Desfavorável'");
            $this->executeQuery("UPDATE `manifestacoes_impugnacoes` SET `favoravel` = NULL WHERE `favoravel` = 'Parcialmente Favorável'");
            
            // Alterar de volta para ENUM
            $this->executeQuery("ALTER TABLE `manifestacoes_impugnacoes` 
                          MODIFY COLUMN `favoravel` enum('FAV','DESFAV') DEFAULT NULL");
        }
    }
}
