<?php

namespace Agencia\Close\Migrations;

/**
 * Converte `agendamentos.tipo_pericia` de ENUM para VARCHAR.
 *
 * Evita erro "Data truncated for column 'tipo_pericia'" quando o ENUM do ambiente
 * não coincide exatamente com os valores enviados pelo app (acentos, aliases, etc.).
 */
class AgendamentosTipoPericiaVarchar extends Migration
{
    public function up(): void
    {
        if (!$this->tableExists('agendamentos') || !$this->columnExists('agendamentos', 'tipo_pericia')) {
            return;
        }

        $this->executeQuery(
            "ALTER TABLE `agendamentos`
             MODIFY COLUMN `tipo_pericia` varchar(50) DEFAULT NULL COMMENT 'Tipo de perícia'"
        );
    }

    public function down(): void
    {
        // Não reverte automaticamente para ENUM (pode truncar dados existentes).
    }
}
