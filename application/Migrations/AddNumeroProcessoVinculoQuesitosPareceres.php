<?php

namespace Agencia\Close\Migrations;

/**
 * Vínculo entre módulos pelo número do processo (quesitos e pareceres).
 * Manifestações já possuem o campo `numero`; agendamentos já possuem `numero_processo`.
 */
class AddNumeroProcessoVinculoQuesitosPareceres extends Migration
{
    public function up(): void
    {
        $queries = [];

        if (!$this->columnExists('quesitos', 'numero_processo')) {
            $queries[] = "ALTER TABLE `quesitos` ADD COLUMN `numero_processo` varchar(255) DEFAULT NULL COMMENT 'Número do processo (vínculo entre módulos)' AFTER `vara`";
            $queries[] = "ALTER TABLE `quesitos` ADD KEY `idx_empresa_numero_processo` (`empresa`, `numero_processo`(191))";
        }

        if (!$this->columnExists('pareceres', 'numero_processo')) {
            $queries[] = "ALTER TABLE `pareceres` ADD COLUMN `numero_processo` varchar(255) DEFAULT NULL COMMENT 'Número do processo (vínculo entre módulos)' AFTER `empresa`";
            $queries[] = "ALTER TABLE `pareceres` ADD KEY `idx_empresa_numero_processo` (`empresa`, `numero_processo`(191))";
        }

        if (!$this->indexExists('manifestacoes_impugnacoes', 'idx_empresa_numero')) {
            $queries[] = "ALTER TABLE `manifestacoes_impugnacoes` ADD KEY `idx_empresa_numero` (`empresa`, `numero`(191))";
        }

        if (!empty($queries)) {
            foreach ($queries as $sql) {
                $this->executeQuery($sql);
            }
        }
    }

    public function down(): void
    {
        $queries = [];

        if ($this->indexExists('manifestacoes_impugnacoes', 'idx_empresa_numero')) {
            $queries[] = "ALTER TABLE `manifestacoes_impugnacoes` DROP INDEX `idx_empresa_numero`";
        }

        if ($this->columnExists('pareceres', 'numero_processo')) {
            if ($this->indexExists('pareceres', 'idx_empresa_numero_processo')) {
                $queries[] = "ALTER TABLE `pareceres` DROP INDEX `idx_empresa_numero_processo`";
            }
            $queries[] = "ALTER TABLE `pareceres` DROP COLUMN `numero_processo`";
        }

        if ($this->columnExists('quesitos', 'numero_processo')) {
            if ($this->indexExists('quesitos', 'idx_empresa_numero_processo')) {
                $queries[] = "ALTER TABLE `quesitos` DROP INDEX `idx_empresa_numero_processo`";
            }
            $queries[] = "ALTER TABLE `quesitos` DROP COLUMN `numero_processo`";
        }

        if (!empty($queries)) {
            foreach ($queries as $sql) {
                $this->executeQuery($sql);
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            $stmt = $this->conn->prepare(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1'
            );
            $stmt->execute([$table, $indexName]);
            return (bool) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            return false;
        }
    }
}
