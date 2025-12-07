<?php

namespace Agencia\Close\Helpers\Pagination;

class Pagination
{
    private PaginationElement $elements;

    private int $last = 1;
    private int $actual = 1;

    private int $beforePages = 1;
    private int $lastPages = 1;
    private int $beforeMiddlePages = 1;
    private int $lastMiddlePages = 4;

    public function __construct()
    {
        $this->elements = new PaginationElement();
    }

    public function setLastPage(int $last)
    {
        if ($last > $this->actual) {
            $this->last = $last;
        }
    }

    public function setActualPage(int $actual)
    {
        $this->actual = $actual;
        if ($this->actual > $this->last) {
            $this->last = $this->actual;
        }
    }

    private function maxElements(): int
    {
        return $this->beforePages + $this->lastPages + $this->beforeMiddlePages + $this->lastMiddlePages + 1;
    }

    private function setList(): void
    {
        if ($this->last <= $this->maxElements()) {
            $this->showAllElements();
        } else {
            $this->before();
            $this->beforeMiddle();
            $this->actual();
            $this->lateMiddle();
            $this->late();
        }
    }

    private function before()
    {
        for ($i = 1; $i <= $this->beforePages; $i++) {
            $beforePage = $this->actual - $this->beforeMiddlePages - $i;
            if ($beforePage >= 1) {
                $this->elements->pushBefore($i);
            }
        }
    }

    private function beforeMiddle()
    {
        for ($i = 1; $i <= $this->beforeMiddlePages; $i++) {
            $beforePage = $this->actual - $this->beforeMiddlePages + $i - 1;
            if ($beforePage >= 1) {
                $this->elements->pushBeforeMiddle($beforePage);
            }
        }
    }

    private function actual()
    {
        $this->elements->setActual($this->actual);
    }

    private function lateMiddle()
    {
        for ($i = 1; $i <= $this->lastMiddlePages; $i++) {
            $lastPage = $this->actual + $i;
            if ($lastPage <= $this->last) {
                $this->elements->pushLateMiddle($lastPage);
            }
        }
    }

    private function late()
    {
        for ($i = 1; $i <= $this->lastPages; $i++) {
            $lastPage = $this->actual + $i + $this->lastMiddlePages;
            if ($lastPage <= $this->last) {
                $this->elements->pushLate($this->last - $this->lastPages + $i + 1);
            }
        }
    }

    private function showAllElements(): void
    {
        for ($i = 1; $i <= $this->last; $i++) {
            if ($i < $this->actual) {
                $this->elements->pushBeforeMiddle($i);
            } elseif ($i > $this->actual) {
                $this->elements->pushLateMiddle($i);
            } else {
                $this->elements->setActual($i);
            }
        }
    }

    public function setUrl(string $url): void
    {
        $this->elements->setUrl($url);
    }

    public function getArray(): array
    {
        $this->setList();
        return $this->elements->get();
    }
}