<?php

namespace Agencia\Close\Conn\Database;

abstract class Database
{
    protected string $host;
    protected string $user;
    protected string $password;
    protected string $database;

    public function __construct()
    {
        $this->setDatabaseInfo();
    }

    abstract protected function setDatabaseInfo(): void;

    public function getHost(): string
    {
        return $this->host;
    }

    public function getUser(): string
    {
        return $this->user;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getDatabase(): string
    {
        return $this->database;
    }

    public function getArray(): array
    {
        return [
            'host' => $this->host,
            'user' => $this->user,
            'password' => $this->user,
            'database' => $this->database,
        ];
    }
}