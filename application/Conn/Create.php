<?php

namespace Agencia\Close\Conn;

use PDO;
use PDOException;
use PDOStatement;

class Create extends Conn {

    private string $table;
    private array $data;
    private $Result;
    private ?string $lastError = null;

    private $Create;

    private $Conn;

    public function ExeCreate(string $table, array $data) {
        $this->table = (string) $table;
        $this->data = $data;
        $this->lastError = null;

        $this->getSyntax();
        $this->Execute();
    }

    public function getResult() {
        return $this->Result;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function Connect() {
        $this->Conn = $this->getConn();
        $this->Create = $this->Conn->prepare($this->Create);
    }

    private function getSyntax() {
        $Fields = '`' . implode('`, `', array_keys($this->data)) . '`';
        $Places = ':' . implode(', :', array_keys($this->data));
        $this->Create = "INSERT INTO {$this->table} ({$Fields}) VALUES ({$Places})";
    }

    private function Execute() {
        $this->Connect();
        try {
            $this->Create->execute($this->data);
            $this->Result = $this->Conn->lastInsertId();
            $this->lastError = null;
        } catch (PDOException $e) {
            $this->Result = null;
            $this->lastError = $e->getMessage();
            if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !isset($_GET['ajax'])) {
                EchoMsg("<b>Erro ao cadastrar:</b> {$e->getMessage()}", $e->getCode());
            }
        }
    }
    
    public function getErrorInfo(): ?array
    {
        if ($this->lastError !== null) {
            return [
                0 => 'HY000',
                1 => null,
                2 => $this->lastError,
                'driver_message' => $this->lastError,
            ];
        }
        if ($this->Create && method_exists($this->Create, 'errorInfo')) {
            return $this->Create->errorInfo();
        }
        return null;
    }

}
