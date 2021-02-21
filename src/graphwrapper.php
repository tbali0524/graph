<?php

declare(strict_types=1);

namespace Graph;

use Graph\Graph;

class GraphWrapper
{
    public $g = null;

    public function readInputBender2(): void
    {
        $g = new Graph();
        $g->desc = 'graph from Bender 2 IDE Test ...';
        fscanf(STDIN, "%d", $N);
        $g->v = $N + 1;
        $g->vertexW = array();
        // $g->edgeW = array();
        $g->adjL = array();
        for ($i = 0; $i < $N; $i++) {
            $room = explode(' ', trim(fgets(STDIN)));
            $r2 = (($room[2] == 'E') ? $N : $room[2]);
            $r3 = (($room[3] == 'E') ? $N : $room[3]);
            $g->adjL[$room[0]] = array();
            $g->adjL[$room[0]][] = $r2;
            if ($r3 != $r2) {
                $g->adjL[$room[0]][] = $r3;
            }
            $g->vertexW[$room[0]] = $room[1];
            // $g->edgeW[$room[0]][$r2] = -1 * $room[1];
            // $g->edgeW[$room[0]][$r3] = -1 * $room[1];
        }
        $g->vertexW[$N] = 0;
        $g->adjL[$N] = array();
        $this->g = $g;
    }
    // function readInputBender2

    public function readInputPlagueJr(): void
    {
        $g = new Graph();
        $g->desc = 'graph from Plague Jr puzzle IDE Test ...';
        $g->isDirected = false;
        fscanf(STDIN, "%d", $n);
        $g->adjL = array();
        for ($i = 0; $i < $n; $i++) {
            fscanf(STDIN, "%d %d", $xi, $yi);
            $g->adjL[$xi][] = $yi;
            $g->adjL[$yi][] = $xi;
        }
        $g->v = count($g->adjL);
        $this->g = $g;
    }
    // function readInputPlagueJr

    public function readInputAStarExercise(): void
    {
        $g = new Graph();
        $g->desc = 'graph from A Star Exercise puzzle IDE Test ...';
        $g->isDirected = false;
        fscanf(STDIN, "%d %d %d %d", $N, $E, $S, $G);
        $g->v = $N;
        $g->startIdx = $S;
        $g->targetIdx = $G;
        $inputs = explode(" ", fgets(STDIN));
        $g->heuristicScore = array();
        for ($i = 0; $i < $N; $i++) {
            $g->heuristicScore[$i] = intval($inputs[$i]);
        }
        $g->adjL = array();
        $g->edgeW = array();
        for ($i = 0; $i < $E; $i++) {
            fscanf(STDIN, "%d %d %d", $x, $y, $c);
            $g->adjL[$x][] = $y;
            $g->adjL[$y][] = $x;
            $g->edgeW[$x][$y] = $c;
            $g->edgeW[$y][$x] = $c;
        }
        $this->g = $g;
    }
    // function readInputAStarExercise

    // creates a test graph with fixed data in the source php file
    public function getFixGraph(): void
    {
        $g = new Graph();
        $g->v = 10;
        $g->isDirected = false;
        $g->vertexW =   explode(',', str_replace(' ', '', '  1,  1,  1,  1,  1,  1,  1,  1,  1,  1'));
        //                                                #  0,  1,  2,  3,  4,  5,  6,  7,  8,  9
        $g->adjM[] =    explode(',', str_replace(' ', '', '  0,  1,  0,  0,  0,  0,  0,  1,  0,  0')); // 0
        $g->adjM[] =    explode(',', str_replace(' ', '', '  1,  0,  1,  0,  0,  1,  0,  0,  0,  0')); // 1
        $g->adjM[] =    explode(',', str_replace(' ', '', '  0,  1,  0,  1,  1,  0,  0,  0,  0,  0')); // 2
        $g->adjM[] =    explode(',', str_replace(' ', '', '  0,  0,  1,  0,  0,  0,  0,  0,  0,  0')); // 3
        $g->adjM[] =    explode(',', str_replace(' ', '', '  0,  0,  1,  0,  0,  0,  0,  0,  0,  0')); // 4
        $g->adjM[] =    explode(',', str_replace(' ', '', '  0,  1,  0,  0,  0,  0,  0,  0,  0,  0')); // 5
        $g->adjM[] =    explode(',', str_replace(' ', '', '  0,  0,  0,  0,  0,  0,  0,  0,  1,  0')); // 6
        $g->adjM[] =    explode(',', str_replace(' ', '', '  1,  0,  0,  0,  0,  0,  0,  0,  0,  1')); // 7
        $g->adjM[] =    explode(',', str_replace(' ', '', '  0,  0,  0,  0,  0,  0,  1,  0,  0,  0')); // 8
        $g->adjM[] =    explode(',', str_replace(' ', '', '  0,  0,  0,  0,  0,  0,  0,  1,  0,  0')); // 9
        /*
        $g->adjL[] =   explode(',', str_replace(' ', '', '  1,  7'));
        $g->adjL[] =   explode(',', str_replace(' ', '', '  0,  2,  5'));
        $g->adjL[] =   explode(',', str_replace(' ', '', '  0'));
        $g->adjL[] =   explode(',', str_replace(' ', '', '  0'));
        $g->adjL[] =   explode(',', str_replace(' ', '', '  0'));
        $g->adjL[] =   explode(',', str_replace(' ', '', '  0'));
        $g->adjL[] =   explode(',', str_replace(' ', '', '  0'));
        $g->adjL[] =   explode(',', str_replace(' ', '', '  0'));
        $g->adjL[] =   explode(',', str_replace(' ', '', '  0'));
        $g->adjL[] =   explode(',', str_replace(' ', '', '  0'));
        */
        /*
        //                                                #  0,  1,  2,  3,  4,  5,  6,  7,  8,  9
        $g->edgeW[] =   explode(',', str_replace(' ', '', '  0, 10,  0,  0,  0,  0,  0, 20,  0,  0'));
        $g->edgeW[] =   explode(',', str_replace(' ', '', ' 30,  0, 40,  0,  0, 50,  0,  0,  0,  0'));
        $g->edgeW[] =   explode(',', str_replace(' ', '', '  1,  0,  0,  0,  0,  0,  0,  0,  0,  0'));
        $g->edgeW[] =   explode(',', str_replace(' ', '', '  1,  0,  0,  0,  0,  0,  0,  0,  0,  0'));
        $g->edgeW[] =   explode(',', str_replace(' ', '', '  1,  0,  0,  0,  0,  0,  0,  0,  0,  0'));
        $g->edgeW[] =   explode(',', str_replace(' ', '', '  1,  0,  0,  0,  0,  0,  0,  0,  0,  0'));
        $g->edgeW[] =   explode(',', str_replace(' ', '', '  1,  0,  0,  0,  0,  0,  0,  0,  0,  0'));
        $g->edgeW[] =   explode(',', str_replace(' ', '', '  1,  0,  0,  0,  0,  0,  0,  0,  0,  0'));
        $g->edgeW[] =   explode(',', str_replace(' ', '', '  1,  0,  0,  0,  0,  0,  0,  0,  0,  0'));
        $g->edgeW[] =   explode(',', str_replace(' ', '', '  1,  0,  0,  0,  0,  0,  0,  0,  0,  0'));
        */
        $this->g = $g;
    }
    // function getFixGraph

    // reads graph in my format from stdin, report to stdout
    public function testGraph(): void
    {
        $g = new Graph();
        $g->readGraph();
        $g->analyzeGraph();
        echo $g->reportGraph(100);
        echo '  shortest path from 0 to ' . ($g->v - 1) . ': ' . implode('->', $g->getPath(0, $g->v - 1)) . "\n";
        echo '  BFS traverse order: ';
        $g->BFS(0, array('\Graph\Graph', 'callFunction'));
        echo "\n";
        echo '  DFS traverse order: ';
        $g->dfsIterative(0, '\Graph\Graph::callFunction');
        echo "\n";
        $this->g = $g;
    }
    // function testGraph
}
