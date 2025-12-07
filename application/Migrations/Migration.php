<?php

namespace Agencia\Close\Migrations;

use Agencia\Close\Conn\Conn;
use PDO;
use PDOException;

abstract class Migration extends Conn
{
    protected PDO $conn;

    public function __construct()
    {
        parent::__construct();
        $this->conn = $this->getConn();
    }

    /**
     * Executa a migration (aplicar alterações)
     */
    abstract public function up(): void;

    /**
     * Reverte a migration (desfazer alterações)
     */
    abstract public function down(): void;

    /**
     * Retorna o nome da migration
     */
    public function getName(): string
    {
        $className = get_class($this);
        $parts = explode('\\', $className);
        return end($parts);
    }

    /**
     * Executa uma query SQL
     */
    protected function executeQuery(string $sql): bool
    {
        try {
            $this->conn->exec($sql);
            return true;
        } catch (PDOException $e) {
            throw new \Exception("Erro ao executar query: " . $e->getMessage() . "\nSQL: " . $sql);
        }
    }

    /**
     * Executa múltiplas queries SQL
     */
    protected function executeQueries(array $queries): bool
    {
        try {
            $this->conn->beginTransaction();
            foreach ($queries as $sql) {
                $this->conn->exec($sql);
            }
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            throw new \Exception("Erro ao executar queries: " . $e->getMessage());
        }
    }

    /**
     * Verifica se uma tabela existe
     */
    protected function tableExists(string $tableName): bool
    {
        try {
            $stmt = $this->conn->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$tableName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Verifica se uma coluna existe em uma tabela
     */
    protected function columnExists(string $tableName, string $columnName): bool
    {
        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM `{$tableName}` LIKE ?");
            $stmt->execute([$columnName]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
}

