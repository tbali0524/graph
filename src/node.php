<?php

declare(strict_types=1);

namespace Graph;

// Problem specific data for each graph vertex
class Node
{
    public $idx = 0;

    public function __construct(int $_idx = 0)
    {
        $this->idx = $_idx;
    }

    public function toString(): string
    {
        return strval($this->idx);
    }
}
