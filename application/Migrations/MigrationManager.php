<?php

namespace Agencia\Close\Migrations;

use Agencia\Close\Conn\Conn;
use PDO;
use PDOException;

class MigrationManager extends Conn
{
    private PDO $conn;
    private string $migrationsTable = 'migrations';
    private string $migrationsPath;

    public function __construct()
    {
        parent::__construct();
        $this->conn = $this->getConn();
        $this->migrationsPath = __DIR__;
    }

    /**
     * Cria a tabela de migrations se não existir
     */
    public function ensureMigrationsTable(): void
    {
        if (!$this->tableExists($this->migrationsTable)) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$this->migrationsTable}` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `migration` varchar(255) NOT NULL,
                `batch` int(11) NOT NULL,
                `executed_at` timestamp NULL DEFAULT current_timestamp(),
                `execution_time` decimal(10,4) DEFAULT NULL,
                `status` enum('success','failed') DEFAULT 'success',
                `error_message` text DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `migration` (`migration`),
                KEY `idx_batch` (`batch`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $this->conn->exec($sql);
        }
    }

    /**
     * Verifica se uma tabela existe
     */
    private function tableExists(string $tableName): bool
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
     * Retorna todas as migrations executadas
     */
    public function getExecutedMigrations(): array
    {
        $this->ensureMigrationsTable();
        $stmt = $this->conn->query("SELECT * FROM `{$this->migrationsTable}` ORDER BY `batch` DESC, `id` DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Retorna os nomes das migrations executadas
     */
    public function getExecutedMigrationNames(): array
    {
        $this->ensureMigrationsTable();
        $stmt = $this->conn->query("SELECT `migration` FROM `{$this->migrationsTable}`");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Retorna o próximo batch number
     */
    public function getNextBatch(): int
    {
        $this->ensureMigrationsTable();
        $stmt = $this->conn->query("SELECT MAX(`batch`) as max_batch FROM `{$this->migrationsTable}`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['max_batch'] ?? 0) + 1;
    }

    /**
     * Registra uma migration como executada
     */
    public function recordMigration(string $migrationName, int $batch, float $executionTime = null, bool $success = true, string $errorMessage = null): void
    {
        $this->ensureMigrationsTable();
        $sql = "INSERT INTO `{$this->migrationsTable}` (`migration`, `batch`, `execution_time`, `status`, `error_message`) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    `batch` = VALUES(`batch`), 
                    `executed_at` = current_timestamp(),
                    `execution_time` = VALUES(`execution_time`),
                    `status` = VALUES(`status`),
                    `error_message` = VALUES(`error_message`)";
        
        $stmt = $this->conn->prepare($sql);
        $status = $success ? 'success' : 'failed';
        $stmt->execute([$migrationName, $batch, $executionTime, $status, $errorMessage]);
    }

    /**
     * Remove o registro de uma migration
     */
    public function removeMigration(string $migrationName): void
    {
        $this->ensureMigrationsTable();
        $stmt = $this->conn->prepare("DELETE FROM `{$this->migrationsTable}` WHERE `migration` = ?");
        $stmt->execute([$migrationName]);
    }

    /**
     * Carrega todas as classes de migration disponíveis
     */
    public function getAvailableMigrations(): array
    {
        $migrations = [];
        $files = glob($this->migrationsPath . '/*.php');
        
        foreach ($files as $file) {
            $fileName = basename($file, '.php');
            if ($fileName === 'Migration' || $fileName === 'MigrationManager') {
                continue;
            }
            
            require_once $file;
            $className = "Agencia\\Close\\Migrations\\{$fileName}";
            
            if (class_exists($className)) {
                try {
                    $migration = new $className();
                    if ($migration instanceof Migration) {
                        $migrations[] = $migration;
                    }
                } catch (\Exception $e) {
                    // Ignora migrations que não podem ser instanciadas
                    continue;
                }
            }
        }

        // Ordena migrations por nome (timestamp)
        usort($migrations, function($a, $b) {
            return strcmp($a->getName(), $b->getName());
        });

        return $migrations;
    }

    /**
     * Executa todas as migrations pendentes
     */
    public function runPendingMigrations(): array
    {
        $this->ensureMigrationsTable();
        $executedNames = $this->getExecutedMigrationNames();
        $availableMigrations = $this->getAvailableMigrations();
        $results = [];
        $batch = $this->getNextBatch();

        foreach ($availableMigrations as $migration) {
            $migrationName = $migration->getName();
            
            // Pula migrations já executadas com sucesso
            if (in_array($migrationName, $executedNames)) {
                continue;
            }

            $startTime = microtime(true);
            $success = true;
            $errorMessage = null;

            try {
                $migration->up();
                $executionTime = microtime(true) - $startTime;
                $this->recordMigration($migrationName, $batch, $executionTime, true);
                $results[] = [
                    'migration' => $migrationName,
                    'status' => 'success',
                    'message' => 'Migration executada com sucesso',
                    'execution_time' => round($executionTime, 4)
                ];
            } catch (\Exception $e) {
                $executionTime = microtime(true) - $startTime;
                $errorMessage = $e->getMessage();
                $this->recordMigration($migrationName, $batch, $executionTime, false, $errorMessage);
                $results[] = [
                    'migration' => $migrationName,
                    'status' => 'failed',
                    'message' => $errorMessage,
                    'execution_time' => round($executionTime, 4)
                ];
                $success = false;
            }
        }

        return $results;
    }

    /**
     * Reverte a última batch de migrations
     */
    public function rollbackLastBatch(): array
    {
        $this->ensureMigrationsTable();
        $stmt = $this->conn->query("SELECT MAX(`batch`) as max_batch FROM `{$this->migrationsTable}`");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $lastBatch = $result['max_batch'] ?? 0;

        if ($lastBatch === 0) {
            return [];
        }

        $stmt = $this->conn->prepare("SELECT `migration` FROM `{$this->migrationsTable}` WHERE `batch` = ? ORDER BY `id` DESC");
        $stmt->execute([$lastBatch]);
        $migrationsToRollback = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $results = [];

        foreach ($migrationsToRollback as $migrationName) {
            $className = "Agencia\\Close\\Migrations\\{$migrationName}";
            
            if (!class_exists($className)) {
                $results[] = [
                    'migration' => $migrationName,
                    'status' => 'failed',
                    'message' => 'Classe de migration não encontrada'
                ];
                continue;
            }

            $migration = new $className();
            $startTime = microtime(true);
            $success = true;
            $errorMessage = null;

            try {
                $migration->down();
                $executionTime = microtime(true) - $startTime;
                $this->removeMigration($migrationName);
                $results[] = [
                    'migration' => $migrationName,
                    'status' => 'success',
                    'message' => 'Migration revertida com sucesso',
                    'execution_time' => round($executionTime, 4)
                ];
            } catch (\Exception $e) {
                $executionTime = microtime(true) - $startTime;
                $errorMessage = $e->getMessage();
                $results[] = [
                    'migration' => $migrationName,
                    'status' => 'failed',
                    'message' => $errorMessage,
                    'execution_time' => round($executionTime, 4)
                ];
            }
        }

        return $results;
    }

    /**
     * Executa uma migration específica
     */
    public function runMigration(string $migrationName): array
    {
        $this->ensureMigrationsTable();
        $className = "Agencia\\Close\\Migrations\\{$migrationName}";
        
        if (!class_exists($className)) {
            throw new \Exception("Migration '{$migrationName}' não encontrada");
        }

        $migration = new $className();
        $batch = $this->getNextBatch();
        $startTime = microtime(true);
        $success = true;
        $errorMessage = null;

        try {
            $migration->up();
            $executionTime = microtime(true) - $startTime;
            $this->recordMigration($migrationName, $batch, $executionTime, true);
            return [
                'migration' => $migrationName,
                'status' => 'success',
                'message' => 'Migration executada com sucesso',
                'execution_time' => round($executionTime, 4)
            ];
        } catch (\Exception $e) {
            $executionTime = microtime(true) - $startTime;
            $errorMessage = $e->getMessage();
            $this->recordMigration($migrationName, $batch, $executionTime, false, $errorMessage);
            return [
                'migration' => $migrationName,
                'status' => 'failed',
                'message' => $errorMessage,
                'execution_time' => round($executionTime, 4)
            ];
        }
    }

    /**
     * Retorna o status das migrations
     */
    public function getMigrationStatus(): array
    {
        $this->ensureMigrationsTable();
        $availableMigrations = $this->getAvailableMigrations();
        $executedMigrations = $this->getExecutedMigrations();
        $executedNames = array_column($executedMigrations, 'migration');

        $status = [
            'total' => count($availableMigrations),
            'executed' => count($executedMigrations),
            'pending' => 0,
            'failed' => 0,
            'migrations' => []
        ];

        foreach ($availableMigrations as $migration) {
            $migrationName = $migration->getName();
            $executed = in_array($migrationName, $executedNames);
            
            if (!$executed) {
                $status['pending']++;
            }

            $migrationData = array_filter($executedMigrations, function($m) use ($migrationName) {
                return $m['migration'] === $migrationName;
            });

            $migrationInfo = [
                'name' => $migrationName,
                'executed' => $executed,
                'status' => null,
                'batch' => null,
                'executed_at' => null,
                'execution_time' => null,
                'error_message' => null
            ];

            if (!empty($migrationData)) {
                $migrationData = reset($migrationData);
                $migrationInfo['status'] = $migrationData['status'];
                $migrationInfo['batch'] = $migrationData['batch'];
                $migrationInfo['executed_at'] = $migrationData['executed_at'];
                $migrationInfo['execution_time'] = $migrationData['execution_time'];
                $migrationInfo['error_message'] = $migrationData['error_message'];
                
                if ($migrationData['status'] === 'failed') {
                    $status['failed']++;
                }
            }

            $status['migrations'][] = $migrationInfo;
        }

        return $status;
    }
}

