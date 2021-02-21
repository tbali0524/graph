<?php

declare(strict_types=1);

require_once "classloader.php";

$gt = new Graph\GraphWrapper();
if (isset($argv[1])) {
    // 0 = Bender2, 1 = Plague Jr, 2 = A-star expercise
    $puzzle_id = intval($argv[1]);
    if ($puzzle_id == 0) {
        $gt->readInputBender2();
    } elseif ($puzzle_id == 1) {
        $gt->readInputPlagueJr();
    } elseif ($puzzle_id == 2) {
        $gt->readInputAStarExercise();
    } else {
        throw new \Exception('Unsupported puzzle id!');
    }
    echo $gt->g->writeGraph();
} else {
    $gt->testGraph();
}
