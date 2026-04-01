<?php

namespace Agencia\Close\Migrations;

/**
 * Corrige mojibake em todas as tabelas base: colunas varchar/char/text.
 * Ignora JSON, ENUM, BLOB, views e tabelas internas. Atualiza por valor
 * distinto (WHERE col = ?) para não depender de PK.
 */
class FixAllTablesUtf8Mojibake extends Migration
{
    private const SKIP_TABLES = [
        'migrations',
    ];

    public function up(): void
    {
        $schema = $this->currentSchema();
        if ($schema === null || $schema === '') {
            return;
        }

        $columns = $this->listTextColumns($schema);
        $pattern = Utf8MojibakeFixer::likePattern();

        foreach ($columns as [$table, $column]) {
            if (!$this->isSafeIdentifier($table) || !$this->isSafeIdentifier($column)) {
                continue;
            }

            if (in_array($table, self::SKIP_TABLES, true)) {
                continue;
            }

            if (!$this->tableExists($table)) {
                continue;
            }

            $this->fixColumn($table, $column, $pattern);
        }
    }

    private function currentSchema(): ?string
    {
        $v = $this->conn->query('SELECT DATABASE()')->fetchColumn();
        return $v !== false ? (string) $v : null;
    }

    /**
     * @return list<array{0: string, 1: string}>
     */
    private function listTextColumns(string $schema): array
    {
        $sql = 'SELECT c.`TABLE_NAME`, c.`COLUMN_NAME`
            FROM `INFORMATION_SCHEMA`.`COLUMNS` c
            INNER JOIN `INFORMATION_SCHEMA`.`TABLES` t
                ON c.`TABLE_SCHEMA` = t.`TABLE_SCHEMA` AND c.`TABLE_NAME` = t.`TABLE_NAME`
            WHERE c.`TABLE_SCHEMA` = ?
                AND t.`TABLE_TYPE` = \'BASE TABLE\'
                AND c.`DATA_TYPE` IN (\'varchar\', \'char\', \'text\', \'mediumtext\', \'longtext\', \'tinytext\')
            ORDER BY c.`TABLE_NAME`, c.`ORDINAL_POSITION`';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$schema]);

        return $stmt->fetchAll(\PDO::FETCH_NUM) ?: [];
    }

    private function fixColumn(string $table, string $column, string $pattern): void
    {
        $selectSql = sprintf(
            'SELECT DISTINCT `%s` AS `_v` FROM `%s` WHERE `%s` IS NOT NULL AND `%s` LIKE ?',
            $column,
            $table,
            $column,
            $column
        );

        $sel = $this->conn->prepare($selectSql);
        $sel->execute([$pattern]);
        $values = $sel->fetchAll(\PDO::FETCH_COLUMN, 0);

        if ($values === []) {
            return;
        }

        $updateSql = sprintf(
            'UPDATE `%s` SET `%s` = ? WHERE `%s` = ?',
            $table,
            $column,
            $column
        );
        $upd = $this->conn->prepare($updateSql);

        foreach ($values as $raw) {
            if ($raw === null || $raw === '') {
                continue;
            }
            $val = (string) $raw;
            $fixed = Utf8MojibakeFixer::tryFix($val);
            if ($fixed === null) {
                continue;
            }
            $upd->execute([$fixed, $val]);
        }
    }

    private function isSafeIdentifier(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_]+$/', $name);
    }

    public function down(): void
    {
        // Reversão não é determinística sem backup.
    }
}
