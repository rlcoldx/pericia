<?php

namespace Agencia\Close\Conn\Database;

class MainDatabase extends  Database
{
    protected function setDatabaseInfo(): void
    {
        $this->host = HOST_MAIN;
        $this->user = USER_MAIN;
        $this->password = PASS_MAIN;
        $this->database = DBSA_MAIN;
    }
}