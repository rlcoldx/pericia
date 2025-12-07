<?php

namespace Agencia\Close\Conn;

use Agencia\Close\Conn\Database\Database;
use Agencia\Close\Conn\Database\MainDatabase;
use PDO;
use PDOException;

abstract class Conn {

    private string $Host;
    private string $User;
    private string $Pass;
    protected string $Dbsa;

    protected Database $database;

    private $Connect = null;

    public function __construct(Database $dataBase = null)
    {
        if(!$dataBase){
            $dataBase = new MainDatabase();
        }
        $this->setDatabase($dataBase);
    }

    private function setDatabase($dataBase)
    {
        $this->database = $dataBase;
        $this->Host = $dataBase->getHost();
        $this->User = $dataBase->getUser();
        $this->Pass = $dataBase->getPassword();
        $this->Dbsa = $dataBase->getDatabase();
    }

    private function Conectar(): PDO
    {
        try {
            if ($this->Connect == null):
                $dsn = 'mysql:host=' . $this->Host . ';dbname=' . $this->Dbsa;
                $options = array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES LATIN1');
                $this->Connect = new PDO($dsn, $this->User, $this->Pass, $options);
            endif;
        } catch (PDOException $e) {
            PHPErro($e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
            die;
        }

        $this->Connect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this->Connect;
    }

    protected function getConn(): PDO
    {
        return $this->Conectar();
    }

}
