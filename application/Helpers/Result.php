<?php

namespace Agencia\Close\Helpers;

class Result
{
    private string $message;
    private bool $error;
    private array $info;

    function __construct()
    {
        $this->error = false;
        $this->info = [];
    }

    public function __get($name) {
        $method = 'get' . ucfirst($name);
        return $this->$method();
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getResultJson(){
        $result = $this->getResultArray();
        return json_encode($result, JSON_UNESCAPED_UNICODE);
    }

    public function getResultArray(): array
    {
        return [
            'error'=>$this->error,
            'message'=>$this->message,
            'info'=>$this->info,
        ];
    }

    public function getError(): bool
    {
        return $this->error;
    }


    public function setError(bool $error): void
    {
        $this->error = $error;
    }

    public function getInfo(): array
    {
        return $this->info;
    }

    public function setInfo(array $info): void
    {
        $this->info = $info;
    }
}
