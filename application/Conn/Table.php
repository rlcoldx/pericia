<?php

namespace Agencia\Close\Conn;

use PDO;
use PDOException;

class Table extends Conn
{
    private string $name;
    private array $fields;

    private string $sqlTable;
    private $prepareQuery;

    private $Result;

    private $Conn;

    public function insertTableIfNotExist(string $name, array $fields)
    {
        $this->name = $name;
        $this->fields = $fields;
        if (!$this->verifyIfExist()) {
            $this->makeTable();
        }
    }

    private function Connect($sql)
    {
        $this->Conn = $this->getConn();
        $this->prepareQuery = $this->Conn->prepare($sql);
    }

    private function verifyIfExist(): bool
    {
        $read = new Read($this->database);
        $read->FullRead("SELECT 1 FROM " . $this->name . " LIMIT 1");
        if (isset($read->getResult()[0])) {
            return true;
        }
        return false;
    }

    private function makeTable(): bool
    {
        $this->makeQuery();
        $this->Connect($this->sqlTable);
        try {
            $this->prepareQuery->execute();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    private function makeQuery()
    {
        $this->sqlTable = "CREATE TABLE " . $this->name . " (";
        $this->sqlTable .= implode(',', $this->fields);
        $this->sqlTable .= ');';
    }
}