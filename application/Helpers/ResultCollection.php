<?php

namespace Agencia\Close\Controllers\Helpers;

class ResultCollection
{
    private $resultCollection = [];
    public $resultsSuccess = [];
    public $resultsFailed = [];
    private $error;

    public function __construct()
    {
        $this->error = false;
    }

    public function push(Result $result)
    {
        array_push($this->resultCollection, $result);
    }

    public function get(): array
    {
        return $this->resultCollection;
    }

    public function getError(): bool
    {
        foreach ($this->resultCollection as $result){
            if ($result->getErro()){
                $this->error = true;
            }
        }
        return $this->error;
    }

    public function getResultsSuccess(): array
    {
        $this->setResults();
        return $this->resultsSuccess;
    }

    public function getResultsFailed(): array
    {
        $this->setResults();
        return $this->resultsFailed;
    }

    private function setResults()
    {
        foreach ($this->resultCollection as $result){
            if ($result->getErro() === true){
                array_push($this->resultsFailed, $result);
            } else {
                array_push($this->resultsSuccess, $result);
            }
        }
    }
}
