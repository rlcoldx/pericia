<?php

namespace Agencia\Close\Migrations;

/**
 * Garante compatibilidade do ENUM `agendamentos.tipo_pericia` entre ambientes:
 * alguns bancos foram migrados com 'MÉDICA' (acentuado) e outros com 'MEDICA' (sem acento).
 *
 * Mantém ambos no ENUM e normaliza registros existentes para 'MEDICA'.
 */
class FixAgendamentosTipoPericiaEnumMedica extends Migration
{
    public function up(): void
    {
        if (!$this->tableExists('agendamentos') || !$this->columnExists('agendamentos', 'tipo_pericia')) {
            return;
        }

        try {
            // 1) Permitir ambos valores no ENUM (se já for ENUM, este MODIFY é idempotente)
            $this->executeQuery(
                "ALTER TABLE `agendamentos` 
                 MODIFY COLUMN `tipo_pericia` enum('MÉDICA','MEDICA','TECNICA','ERGONO','CINESIO','VISTORIA','X1','X2') 
                 DEFAULT NULL COMMENT 'Tipo de perícia'"
            );
        } catch (\Exception $e) {
            // Se falhar (ex.: ainda varchar), tenta converter direto para ENUM compatível
            try {
                $this->executeQuery(
                    "ALTER TABLE `agendamentos` 
                     MODIFY COLUMN `tipo_pericia` enum('MÉDICA','MEDICA','TECNICA','ERGONO','CINESIO','VISTORIA','X1','X2') 
                     DEFAULT NULL COMMENT 'Tipo de perícia'"
                );
            } catch (\Exception $e2) {
                // ignora
            }
        }

        // 2) Normaliza dados existentes
        try {
            $this->executeQuery("UPDATE `agendamentos` SET `tipo_pericia` = 'MEDICA' WHERE `tipo_pericia` = 'MÉDICA'");
        } catch (\Exception $e) {
            // ignora
        }

        // 3) Limpa valores inválidos (se houver) para NULL
        try {
            $this->executeQuery(
                "UPDATE `agendamentos`
                 SET `tipo_pericia` = NULL
                 WHERE `tipo_pericia` IS NOT NULL
                   AND `tipo_pericia` NOT IN ('MÉDICA','MEDICA','TECNICA','ERGONO','CINESIO','VISTORIA','X1','X2')"
            );
        } catch (\Exception $e) {
            // ignora
        }
    }

    public function down(): void
    {
        // Não faz rollback automático: remover valores do ENUM pode quebrar registros.
    }
}

