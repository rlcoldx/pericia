<?php

namespace Agencia\Close\Helpers\Pagination;

class PaginationElement
{
    protected string $url = '';
    private array $before = [];
    private array $beforeMiddle = [];
    private int $actual;
    private array $lateMiddle = [];
    private array $late = [];

    public function pushBefore(int $before): void
    {
        array_push($this->before, [
            'number' => $before,
            'url' => $this->url . '/' . $before
        ]);
    }

    public function pushBeforeMiddle(int $beforeMiddle): void
    {
        array_push($this->beforeMiddle, [
            'number' => $beforeMiddle,
            'url' => $this->url . '/' . $beforeMiddle
        ]);
    }

    public function setActual(int $actual): void
    {
        $this->actual = $actual;
    }

    public function pushLateMiddle(int $lateMiddle): void
    {
        array_push($this->lateMiddle, [
            'number' => $lateMiddle,
            'url' => $this->url . '/' . $lateMiddle
        ]);
    }

    public function pushLate(int $late): void
    {
        array_push($this->late, [
            'number' => $late,
            'url' => $this->url . '/' . $late
        ]);
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }


    public function get(): array
    {
        return [
            'before' => $this->before,
            'beforeMiddle' => $this->beforeMiddle,
            'actual' => $this->actual,
            'lateMiddle' => $this->lateMiddle,
            'late' => $this->late
        ];
    }
}