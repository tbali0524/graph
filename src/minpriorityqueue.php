<?php

declare(strict_types=1);

namespace TBali\Graph;

// --------------------------------------------------------------------
// used by Dijkstra algorithm
class MinPriorityQueue extends \SPLPriorityQueue
{
    public function compare($a, $b): int
    {
        return parent::compare($b, $a);     //inverse the order
    }
}
