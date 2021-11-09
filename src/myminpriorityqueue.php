<?php

declare(strict_types=1);

namespace TBali\Graph;

// --------------------------------------------------------------------
// used by aStar algorithm
// naive implementation using array, allows to change priority of any item
class MyMinPriorityQueue
{
    private $data = array();                // array[0..] of value (int/float/string)
    private $priority = array();            // array[value] of int/float

    public const ERROR_MSG_GET_EMPTY = 'Trying to get item from empty Priority queue!';
    public const ERROR_MSG_INSERT_EXISTING = 'Trying to insert already existing item to Priority queue!';
    public const ERROR_MSG_CHANGE_NON_EXISTENT = 'Trying to update non-existing item in Priority queue!';
    public const ERROR_MSG_DELETE_NON_EXISTENT = 'Trying to delete non-existing item in Priority queue!';

    public function compare($a, $b): int
    {
        $ans = ($this->priority[$b] ?? PHP_INT_MAX) <=> ($this->priority[$a] ?? PHP_INT_MAX);
        if ($ans == 0) {
            $ans = $b <=> $a;
        }
        return $ans;
    }

    public function top()
    {
        if (count($this->data) == 0) {
            throw new \Exception(self::ERROR_MSG_GET_EMPTY);
        }
        return $this->data[count($this->data) - 1];
    }

    public function extract()
    {
        if (count($this->data) == 0) {
            throw new \Exception(self::ERROR_MSG_GET_EMPTY);
        }
        $ans = array_pop($this->data);
        unset($this->priority[$ans]);
        return $ans;
    }

    public function insert($item, $priority): void
    {
        if (isset($this->priority[$item])) {
            throw new \Exception(self::ERROR_MSG_INSERT_EXISTING);
        }
        $this->data[] = $item;
        $this->priority[$item] = $priority;
        usort($this->data, array(self::class, 'compare'));
    }

    public function changePriority($item, $priority): void
    {
        if (!isset($this->priority[$item])) {
            throw new \Exception(self::ERROR_MSG_CHANGE_NON_EXISTENT);
        }
        $this->priority[$item] = $priority;
        usort($this->data, array(self::class, 'compare'));
    }

    public function exists($item): bool
    {
        return isset($this->priority[$item]);
    }

    public function isEmpty(): bool
    {
        return count($this->data) == 0;
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function toString(): string
    {
        $s = '';
        foreach ($this->data as $item) {
            $s .= $item . ' (' . $this->priority[$item] . ') ';
        }
        return $s;
    }
}
