<?php

namespace Agencia\Close\Conn;

use PDO;
use PDOException;
use PDOStatement;

class Create extends Conn {

    private string $table;
    private array $data;
    private $Result;

    private $Create;

    private $Conn;

    public function ExeCreate(string $table, array $data) {
        $this->table = (string) $table;
        $this->data = $data;

        $this->getSyntax();
        $this->Execute();
    }

    public function getResult() {
        return $this->Result;
    }

    private function Connect() {
        $this->Conn = $this->getConn();
        $this->Create = $this->Conn->prepare($this->Create);
    }

    //Cria a sintaxe da query para Prepared Statements
    private function getSyntax() {
        $Fields = '`' . implode('`, `', array_keys($this->data)) . '`';
        $Places = ':' . implode(', :', array_keys($this->data));
        $this->Create = "INSERT INTO {$this->table} ({$Fields}) VALUES ({$Places})";
    }

    //Obtém a Conexão e a Syntax, executa a query!
    private function Execute() {
        $this->Connect();
        try {
            $this->Create->execute($this->data);
            $this->Result = $this->Conn->lastInsertId();
        } catch (PDOException $e) {
            $this->Result = null;
            // Só usa EchoMsg se não for uma requisição AJAX/JSON
            if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !isset($_GET['ajax'])) {
                EchoMsg("<b>Erro ao cadastrar:</b> {$e->getMessage()}", $e->getCode());
            }
        }
    }
    
    /**
     * Retorna informações de erro do PDO
     */
    public function getErrorInfo(): ?array
    {
        if ($this->Create && method_exists($this->Create, 'errorInfo')) {
            return $this->Create->errorInfo();
        }
        return null;
    }

}
