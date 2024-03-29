<?php

/**
 * ====================================================================
 * graph
 * (c) 2021 by Bálint Tóth (TBali)
 * a graph class and some related common algorithms in PHP.
 *
 * repository for latest source: https://github.com/tbali0524/graph
 * requires PHP v7.3 or higher
 * ====================================================================
 */

declare(strict_types=1);

namespace TBali\Graph;

// use Graph\Node;
// use Graph\MinPriorityQueue;
// use Graph\MyMinPriorityQueue;

const DEBUG = false;

class Graph
{
    public $desc = '';          // graph description        string
    // graph representation                                 // type
    public $v = 0;              // number of vertices:      int
    public $isDirected = true;
    public $vertexW = null;     // weights of vertices:     array of int
    public $edgeW = null;       // weights of edges:        array[idx][idx] of int or unset
    public $adjL = null;        // adjacency list:          array[idx] of array of idx
    // payload
    public $node = null;        // the actual data          array[idx] of Node
    // other input parameters
    public $startIdx = 0;       //                          int                     A*
    public $targetIdx = 0;      //                          int                     A*
    public $heuristicScore = null; //                       array[idx] of int       A*: heuristic dist: idx->targetIdx

    public const INFINITY = PHP_INT_MAX >> 2;  // less than PHP_INT_MAX to avoid addition overflow in some algorithms

    // calculated data                                      // type                 // set by this method
    public $e = null;           // number of edges:         int                     countEdges
    public $adjM = null;        // adjacency matrix:        array[idx][idx] of int  adjL2M
    public $inDegree = null;    // in degree:               array[idx] of int       countDegrees
    public $outDegree = null;   // out degree:              array[idx] of int       countDegrees
    public $hasNegativeWeight = null;   //                  bool                    checkNegativeWeight
    public $hasNegativeCycle = null;    //                  bool                    bellmanFord
    public $dist = null;        // shortest path distance:  array[idx][idx] of int  floydWarshall, dijkstra, bellmanFord
    public $pathNext = null;    // shortest path next:      array[idx][idx] of idx  floydWarshall
    public $pathPrev = null;    // shortest path prev:      array[idx][idx] of idx  dijkstra, bellmanFord
    public $parent = null;      // parent in BFS/DFS tree:  array[idx] of int       DFS, BFS
    public $componentIdx = null; // component membership:   array[idx] of int       DFS/BFS, countComponents
    public $componentCount = null; // # of components       int                     countComponents

    public const DEBUG_MAX_VERTEX_TO_SHOW = 5;

    /* methods
    public function analyzeGraph(): void
    public function reportGraph(int $maxVertex = self::DEBUG_MAX_VERTEX_TO_SHOW): string
    public function readGraph(): void
    public function writeGraph(): void
    public function adjL2M(): void
    public function mirrorEdges(): void
    public function countEdges(): void
    public function countDegrees(): void
    public function checkNegativeWeight(): bool
    public function floydWarshall(bool $createPath = TRUE): void
    public function dijkstra(int $from): void
    public function bellmanFord(int $from): bool
    public function aStar(int $from): bool
    public function getPath(int $from, int $to): array
    public function BFS(int $from, callable $func = NULL): bool
    public function dfsIterative(int $from, callable $func = NULL): bool
    public function dfs(int $from, int $compIdx = 0, callable $func = NULL): void
        static function callFunction(int $idx): bool
    public function calculateComponents(): void
    */

    // calls all graph analyzer methods
    //      input: v, isDirected, adjL (or adjM), vertexW, edgeW,
    //      result: all other class properties
    public function analyzeGraph(): void
    {
        if (!$this->isDirected) {
            $this->mirrorEdges();
        }
        $this->adjL2M();
        $this->countEdges();
        $this->countDegrees();
        $this->checkNegativeWeight();
        if ($this->hasNegativeWeight) {
            for ($i = 0; $i < $this->v; $i++) {
                $this->bellmanFord($i);
            }
            if (!isset($this->hasNegativeCycle)) {
                $this->hasNegativeCycle = false;
            }
            $this->floydWarshall();
        } else {
            for ($i = 0; $i < $this->v; $i++) {
                $this->dijkstra($i);
            }
        }
        $this->calculateComponents();
        $this->startIdx = 0;
        $this->targetIdx = $this->v - 1;
        $this->aStar($this->startIdx);
        $this->BFS($this->startIdx);
        $this->dfsIterative($this->startIdx);
    }
    // function analyzeGraph

    // returns graph report in multi-line string
    public function reportGraph(int $maxVertex = self::DEBUG_MAX_VERTEX_TO_SHOW): string
    {
        $maxVertex = min($this->v, $maxVertex);
        $vw = ($maxVertex < 100 ? 2 : 3);       // format width for vertex index display
        $ww = 5;                                // format width for weight display
        $s = 'GRAPH REPORT: ' . $this->desc . "\n  ";
        if ($this->isDirected) {
            $s .= 'directed ';
        }
        $s .= 'graph: ' . $this->v;
        if (!is_null($this->vertexW)) {
            $s .= ' weighted';
        }
        $s .= ' vertices';
        if (!is_null($this->e)) {
            $s .= ', ' . $this->e;
            if (!is_null($this->edgeW)) {
                $s .= ' weighted';
            }
            $s .= ' edges';
        }
        if (!is_null($this->componentCount)) {
            $s .= ', ' . $this->componentCount . ' components';
        }
        $s .= "\n";
        if ($this->v > $maxVertex) {
            $s .= '  (showing first ' . $maxVertex . " vertices) \n";
        }
        if (!is_null($this->hasNegativeWeight) and (!is_null($this->vertexW) or !is_null($this->edgeW))) {
            $s .= '  graph has ' . ($this->hasNegativeWeight ? '' : 'no ') . "negative weight.\n";
        }
        if (!is_null($this->hasNegativeCycle)) {
            $s .= '  graph has ' . ($this->hasNegativeCycle ? '' : 'no ') . "negative cycle.\n";
        }
        if (!is_null($this->adjL)) {
            $s .= "  adjacency list:\n";
            for ($i = 0; $i < $maxVertex; $i++) {
                $s .= '    vertex #' . str_pad(strval($i), $vw) . ': ';
                if (isset($this->adjL[$i])) {
                    $s .= 'edges to ';
                    foreach ($this->adjL[$i] as $j) {
                        $s .= str_pad(strval($j), $vw);
                        if (isset($this->edgeW[$i][$j])) {
                            $s .= ' (W= ' . str_pad(strval($this->edgeW[$i][$j]), $ww) . ')';
                        }
                        $s .= ', ';
                    }
                    if (count($this->adjL[$i]) == 0) {
                        $s .= '[none]';
                    }
                }
                $s .= "\n";
            }
        }
        $s .= '  vertices:     ';
        for ($i = 0; $i < $maxVertex; $i++) {
            $s .= '+#' . str_pad(strval($i), $ww - 1, '-');
        }
        $s .= "\n";
        if (!is_null($this->vertexW)) {
            $s .= '    weight:     ';
            for ($i = 0; $i < $maxVertex; $i++) {
                $s .= '|' . str_pad(strval($this->vertexW[$i] ?? '-'), $ww);
            }
            $s .= "\n";
        }
        if (!is_null($this->outDegree)) {
            $s .= '    out degree: ';
            for ($i = 0; $i < $maxVertex; $i++) {
                $s .= '|' . str_pad(strval($this->outDegree[$i]), $ww);
            }
            $s .= "\n";
        }
        if (!is_null($this->inDegree)) {
            $s .= '    in degree:  ';
            for ($i = 0; $i < $maxVertex; $i++) {
                $s .= '|' . str_pad(strval($this->inDegree[$i]), $ww);
            }
            $s .= "\n";
        }
        if (!is_null($this->heuristicScore)) {
            $s .= '       h-score: ';
            for ($i = 0; $i < $maxVertex; $i++) {
                $s .= '|' . str_pad(strval($this->heuristicScore[$i]), $ww);
            }
            $s .= "\n";
        }
        if (!is_null($this->adjM)) {
            $s .= "  adjacency matrix:\n";
            $s .= str_repeat(' ', 5 + $vw) . '+';
            for ($j = 0; $j < $maxVertex; $j++) {
                $s .= str_pad(strval($j), $vw + 1, '-');
            }
            if (!is_null($this->edgeW)) {
                $s .= '+ weights: ';
                for ($j = 0; $j < $maxVertex; $j++) {
                    $s .= '+#' . str_pad(strval($j), $ww - 1, '-');
                }
            }
            $s .= "\n";
            for ($i = 0; $i < $maxVertex; $i++) {
                $s .= '    #' . str_pad(strval($i), $vw) . '|';
                for ($j = 0; $j < $maxVertex; $j++) {
                    $s .= str_pad(strval($this->adjM[$i][$j]), $vw) . ' ';
                }
                if (!is_null($this->edgeW)) {
                    $s .= '|          ';
                    for ($j = 0; $j < $maxVertex; $j++) {
                        $s .= '|' . str_pad(strval($this->edgeW[$i][$j] ?? '-'), $ww);
                    }
                }
                $s .= "\n";
            }
        }
        if (!is_null($this->dist)) {
            $s .= "  shortest path distances:\n";
            $s .= str_repeat(' ', $vw + 5);
            for ($i = 0; $i < $maxVertex; $i++) {
                $s .= '+#' . str_pad(strval($i), $ww - 1, '-');
            }
            $s .= "\n";
            for ($i = 0; $i < $maxVertex; $i++) {
                $s .= '    #' . str_pad(strval($i), $vw);
                for ($j = 0; $j < $maxVertex; $j++) {
                    if (!isset($this->dist[$i][$j])) {
                        $s .= str_pad('|?', $ww + 1);
                    } elseif ($this->dist[$i][$j] >= self::INFINITY) {
                        $s .= str_pad('|INF', $ww + 1);
                    } else {
                        $s .= '|' . str_pad(strval($this->dist[$i][$j]), $ww);
                    }
                }
                $s .= "\n";
            }
        }
        if (!is_null($this->pathNext)) {
            $s .= "  shortest path - next vertices:\n";
            $s .= str_repeat(' ', $vw + 5);
            for ($i = 0; $i < $maxVertex; $i++) {
                $s .= '+' . str_pad(strval($i), $vw, '-');
            }
            $s .= "\n";
            for ($i = 0; $i < $maxVertex; $i++) {
                $s .= '    #' . str_pad(strval($i), $vw);
                for ($j = 0; $j < $maxVertex; $j++) {
                    $s .= '|' . str_pad(strval($this->pathNext[$i][$j] ?? 'x'), $vw);
                }
                $s .= "\n";
            }
        }
        if (!is_null($this->pathPrev)) {
            $s .= "  shortest path - previous vertices:\n";
            $s .= str_repeat(' ', $vw + 5);
            for ($i = 0; $i < $maxVertex; $i++) {
                $s .= '+' . str_pad(strval($i), $vw, '-');
            }
            $s .= "\n";
            for ($i = 0; $i < $maxVertex; $i++) {
                $s .= '    #' . str_pad(strval($i), $vw);
                for ($j = 0; $j < $maxVertex; $j++) {
                    $s .= '|' . str_pad(strval($this->pathPrev[$i][$j] ?? 'x'), $vw);
                }
                $s .= "\n";
            }
        }
        if (!is_null($this->componentIdx) and !is_null($this->componentCount) and ($this->componentCount > 1)) {
            $s .= "  components:\n";
            for ($i = 0; $i < $this->componentCount; $i++) {
                $s .= '    #' . $i . ':';
                for ($j = 0; $j < $this->v; $j++) {
                    if ($this->componentIdx[$j] == $i) {
                        $s .= ' ' . $j;
                    }
                }
                $s .= "\n";
            }
        }
        return $s;
    }
    // function reportGraph

    private function readNextLine(bool $isTabular = true): string
    {
        do {
            $s = trim(fgets(STDIN));
        } while (($s == '') or ($s[0] == '#'));
        if ($isTabular) {
            $s = str_replace(' ', '', $s);
        }
        $p = strpos($s, '#');
        if ($p === false) {
            return $s;
        } else {
            return rtrim(substr($s, 0, $p));
        }
    }

    // reads graph data from standard input. Limited error handling
    public function readGraph(): void
    {
        $s = $this->readNextLine(false);
        if ($s == '; desc') {
            $this->desc = $this->readNextLine(false);
            $s = $this->readNextLine(false);
        }
        if ($s == '; isDirected') {
            $s = strtolower($this->readNextLine(false));
            $this->isDirected = (($s == 'directed') or ($s == 'true') or ($s == '1'));
            $s = $this->readNextLine(false);
        }
        if ($s == '; vertices') {
            $s = $this->readNextLine(false);
            if (is_numeric($s) and ($s >= 0)) {
                $this->v = intval($s);
            }
            $s = $this->readNextLine(false);
        }
        if ($s == '; vertex weights') {
            $line = explode(',', $this->readNextLine());
            if (count($line) == $this->v) {
                $this->vertexW = $line;
            }
            $s = $this->readNextLine(false);
        }
        if ($s == '; adjacency list') {
            $this->adjL = [];
            for ($i = 0; $i < $this->v; $i++) {
                $line = explode(',', $this->readNextLine());
                if (strtolower($line[0]) == 'none') {
                    $this->adjL[$i] = [];
                } else {
                    for ($j = 0; $j < count($line); $j++) {
                        $line[$j] = intval($line[$j]);
                    }
                    $this->adjL[$i] = $line;
                }
            }
            $s = $this->readNextLine(false);
        }
        if ($s == '; adjacency matrix') {
            $this->adjL = null;
            $this->adjM = [];
            for ($i = 0; $i < $this->v; $i++) {
                $line = explode(',', $this->readNextLine());
                for ($j = 0; $j < count($line); $j++) {
                    $line[$j] = intval($line[$j]);
                }
                $this->adjM[$i] = $line;
            }
            $s = $this->readNextLine(false);
        }
        if ($s == '; edge weights') {
            $e = $this->readNextLine(false);
            $this->adjM = null;
            $this->adjL = [];
            $this->edgeW = [];
            for ($i = 0; $i < $this->v; $i++) {
                $this->adjL[$i] = [];
                $this->edgeW[$i] = [];
            }
            for ($i = 0; $i < $e; $i++) {
                $line = explode(',', $this->readNextLine());
                if (count($line) < 3) {
                    continue;
                }
                for ($j = 0; $j < count($line); $j++) {
                    $line[$j] = intval($line[$j]);
                }
                $this->adjL[$line[0]][] = $line[1];
                $this->edgeW[$line[0]][$line[1]] = $line[2];
                if (!$this->isDirected) {
                    $this->adjL[$line[1]][] = $line[0];
                    $this->edgeW[$line[1]][$line[0]] = $line[2];
                }
            }
            $s = $this->readNextLine(false);
        }
        if ($s == '; end') {
            return;
        }
    }
    // function readGraph

    // writes graph data to string in graph file format
    public function writeGraph(): string
    {
        $s = "# input file for Graph.PHP\n";
        if (!is_null($this->desc)) {
            $s .= "; desc\n";
            $s .= $this->desc . "\n";
        }
        $s .= "; isDirected\n";
        $s .= ($this->isDirected ? "directed\n" : "undirected\n");
        $s .= "; vertices\n";
        $s .= $this->v . "\n";
        if (!is_null($this->vertexW)) {
            $s .= "; vertex weights\n";
            $s .= implode(', ', ($this->vertexW ?? [])) . "\n";
        }
        if (!is_null($this->edgeW)) {
            $s .= "; edge weights\n";
            if (is_null($this->e)) {
                $this->countEdges();
            }
            $s .= $this->e . "\n";
            for ($i = 0; $i < $this->v; $i++) {
                for ($j = 0; $j < $this->v; $j++) {
                    if (isset($this->edgeW[$i][$j])) {
                        $s .= $i . ', ' . $j . ', ' . $this->edgeW[$i][$j] . "\n";
                    }
                }
            }
        } elseif (!is_null($this->adjL)) {
            $s .= "; adjacency list\n";
            for ($i = 0; $i < $this->v; $i++) {
                $line = implode(', ', $this->adjL[$i]);
                if ($line == '') {
                    $line = 'none';
                }
                $s .= $line . "\n";
            }
        } elseif (!is_null($this->adjM)) {
            $s .= "; adjacency matrix\n";
            for ($i = 0; $i < $this->v; $i++) {
                $s .= implode(', ', $this->adjM[$i]) . "\n";
            }
        }
        $s .= "; end\n";
        return $s;
    }
    // function writeGraph

    // creates adjacency matrix from adjacency list or vice versa
    //      input: v, adjL (or adjM)
    //      result: adjM (or adjL)
    public function adjL2M(): void
    {
        if (!is_null($this->adjL)) {
            $this->adjM = [];
            for ($i = 0; $i < $this->v; $i++) {
                for ($j = 0; $j < $this->v; $j++) {
                    $this->adjM[$i][$j] = 0;
                }
                foreach ($this->adjL[$i] as $j) {
                    $this->adjM[$i][$j]++;
                }
            }
        } elseif (!is_null($this->adjM)) {
            $this->adjL = [];
            for ($i = 0; $i < $this->v; $i++) {
                $this->adjL[$i] = [];
                for ($j = 0; $j < $this->v; $j++) {
                    for ($k = 0; $k < $this->adjM[$i][$j]; $k++) {
                        $this->adjL[$i][] = $j;
                    }
                }
            }
        }
    }
    // function adjL2M()

    // makes all edges bidirectional
    //      input: v, adjL (or adjM)
    //      result: adjM, adjL
    public function mirrorEdges(): void
    {
        if (is_null($this->adjM)) {
            $this->adjL2M();
        }
        for ($i = 0; $i < $this->v; $i++) {
            for ($j = 0; $j < $this->v; $j++) {
                $e = max($this->adjM[$i][$j], $this->adjM[$j][$i]);
                $this->adjM[$i][$j] = $e;
                $this->adjM[$j][$i] = $e;
            }
        }
        if (!is_null($this->edgeW)) {
            for ($i = 0; $i < $this->v; $i++) {
                for ($j = 0; $j < $this->v; $j++) {
                    if (isset($this->edgeW[$i][$j]) and isset($this->edgeW[$j][$i])) {
                        $e = max($this->edgeW[$i][$j], $this->edgeW[$j][$i]);
                    } elseif (isset($this->edgeW[$i][$j])) {
                        $e = $this->edgeW[$i][$j];
                    } elseif (isset($this->edgeW[$j][$i])) {
                        $e = $this->edgeW[$j][$i];
                    } else {
                        continue;
                    }
                    $this->edgeW[$i][$j] = $e;
                    $this->edgeW[$j][$i] = $e;
                }
            }
        }
        $this->adjL = null;
        $this->adjL2M();
    }
    // function mirrorEdges

    // calculate number of edges
    //      input: v, isDirected, adjL or adjM
    //      result: e
    public function countEdges(): void
    {
        $this->e = 0;
        if (!is_null($this->adjL)) {
            foreach ($this->adjL as $adj) {
                $this->e += count($adj);
            }
        } elseif (!is_null($this->adjM)) {
            foreach ($this->adjM as $adj) {
                foreach ($adj as $value) {
                    $this->e += $value;
                }
            }
        }
        if (!$this->isDirected) {
            $this->e = intdiv($this->e, 2);
        }
    }
    // function countEdges()

    // calculate degrees
    //      input: v, adjL
    //      result: inDegree, outDegree
    public function countDegrees(): void
    {
        $this->inDegree = [];
        $this->outDegree = [];
        for ($i = 0; $i < $this->v; $i++) {
            $this->outDegree[$i] = 0;
            $this->inDegree[$i] = 0;
        }
        if (!is_null($this->adjL)) {
            foreach ($this->adjL as $from => $adj) {
                foreach ($adj as $to) {
                    $this->outDegree[$from]++;
                    $this->inDegree[$to]++;
                }
            }
        }
    }
    // function countDegrees()

    // checks if graph has any negative vertex or edge weight
    //      input: vertexW or edgeW
    //      result: hasNegativeWeight
    //      time: O(e+v), memory: O(1)
    public function checkNegativeWeight(): bool
    {
        $this->hasNegativeWeight = false;
        if (isset($this->edgeW)) {
            foreach ($this->edgeW as $edg) {
                foreach ($edg as $weight) {
                    if ($weight < 0) {
                        $this->hasNegativeWeight = true;
                        return true;
                    }
                }
            }
        }
        if (isset($this->vertexW)) {
            foreach ($this->vertexW as $weight) {
                if ($weight < 0) {
                    $this->hasNegativeWeight = true;
                    return true;
                }
            }
        }
        return false;
    }
    // function checkNegativeWeight

    // Floyd-Warshall algorithm
    //  find shortest path distance (also with negative weights)
    //      https://en.wikipedia.org/wiki/Floyd%E2%80%93Warshall_algorithm
    //      input: v, adjL (or adjM), vertexW, edgeW (uses 1 if both NULL)
    //      result: dist, pathNext (if called with createPath = TRUE)
    //      time: O(v^3), memory: v^2
    public function floydWarshall(bool $createPath = true): void
    {
        if (is_null($this->adjL)) {
            $this->adjL2M();
        }
        $this->dist = [];
        for ($i = 0; $i < $this->v; $i++) {
            for ($j = 0; $j < $this->v; $j++) {
                $this->dist[$i][$j] = self::INFINITY;
            }
        }
        foreach ($this->adjL as $from => $adj) {
            foreach ($adj as $to) {
                if (isset($this->vertexW[$to]) and isset($this->edgeW[$from][$to])) {
                    $this->dist[$from][$to] = $this->vertexW[$to] + $this->edgeW[$from][$to];
                } elseif (isset($this->vertexW[$to])) {
                    $this->dist[$from][$to] = $this->vertexW[$to];
                } elseif (isset($this->edgeW[$from][$to])) {
                    $this->dist[$from][$to] = $this->edgeW[$from][$to];
                } else {
                    $this->dist[$from][$to] = 1;
                }
            }
        }
        for ($i = 0; $i < $this->v; $i++) {
            $this->dist[$i][$i] = 0;
        }
        if ($createPath) {
            $this->pathNext = [];
            foreach ($this->adjL as $from => $adj) {
                foreach ($adj as $to) {
                    $this->pathNext[$from][$to] = $to;
                }
            }
            for ($i = 0; $i < $this->v; $i++) {
                $this->pathNext[$i][$i] = $i;
            }
        }
        for ($k = 0; $k < $this->v; $k++) {
            for ($i = 0; $i < $this->v; $i++) {
                for ($j = 0; $j < $this->v; $j++) {
                    if (($this->dist[$i][$k] == self::INFINITY) or ($this->dist[$k][$j] == self::INFINITY)) {
                        continue;
                    }
                    $alt = $this->dist[$i][$k] + $this->dist[$k][$j];
                    if ($alt < $this->dist[$i][$j]) {
                        $this->dist[$i][$j] = $alt;
                        if (($createPath) and isset($this->pathNext[$i][$k])) {
                            $this->pathNext[$i][$j] = $this->pathNext[$i][$k];
                        }
                    }
                }
            }
        }
        for ($i = 0; $i < $this->v; $i++) {
            if (isset($this->vertexW[$i])) {
                for ($j = 0; $j < $this->v; $j++) {
                    if ($this->dist[$i][$j] < self::INFINITY) {
                        $this->dist[$i][$j] += $this->vertexW[$i];
                    }
                }
            }
        }
    }
    // function floydWarshall

    // Dijkstra algorithm
    //  find shortest path (only positive weights)
    //      https://en.wikipedia.org/wiki/Dijkstra%27s_algorithm
    //      input: v, adjL (or adjM), vertexW, edgeW (uses 1 if both NULL)
    //      result: dist[$from], pathPrev[$from]
    //      time: with min-priority queue: O(e + v * log v) or O(v^2) otherwise, memory: v^2
    public function dijkstra(int $from): void
    {
        if (($from < 0) or ($from >= $this->v)) {
            return;
        }
        if (is_null($this->vertexW) and (is_null($this->edgeW))) {
            $defaultW = 1;
        } else {
            $defaultW = 0;
        }
        if (is_null($this->adjL)) {
            $this->adjL2M();
        }
        if (is_null($this->hasNegativeWeight)) {
            $this->checkNegativeWeight();
        }
        if ($this->hasNegativeWeight) {
            return;
        }
        $pq = new MinPriorityQueue();
        $pq->setExtractFlags(MinPriorityQueue::EXTR_DATA);
        $this->dist[$from] = [];
        $this->pathPrev[$from] = [];
        for ($j = 0; $j < $this->v; $j++) {
            $this->dist[$from][$j] = self::INFINITY;
        }
        $this->dist[$from][$from] = 0;
        $pq->insert($from, 0);
        while (!$pq->isEmpty()) {
            $curr = $pq->extract();
            if ($this->dist[$from][$curr] == self::INFINITY) {
                return;
            }
            foreach ($this->adjL[$curr] as $w) {
                $alt = $this->dist[$from][$curr] + ($this->edgeW[$curr][$w] ?? $defaultW) + ($this->vertexW[$w] ?? 0);
                if ($alt < $this->dist[$from][$w]) {
                    $this->dist[$from][$w] = $alt;
                    $this->pathPrev[$from][$w] = $curr;
                    $pq->insert($w, $alt);
                }
            }
        }
        if (isset($this->vertexW[$from])) {
            for ($j = 0; $j < $this->v; $j++) {
                if ($this->dist[$from][$j] < self::INFINITY) {
                    $this->dist[$from][$j] += $this->vertexW[$from] ?? 0;
                }
            }
        }
    }
    // function dijkstra

    // Bellman-Ford algorithm
    //  find shortest path (also for negative weights)
    //      https://en.wikipedia.org/wiki/Bellman%E2%80%93Ford_algorithm
    //      input: v, adjL (or adjM), vertexW, edgeW (uses 1 if both NULL)
    //      result: dist[$from], pathPrev[$from], hasNegativeCycle
    //      returns FALSE if graph has negative-weight cycle or invalid source or unweighted
    //      time: O(e * v), memory: v
    public function bellmanFord(int $from): bool
    {
        if (($from < 0) or ($from >= $this->v)) {
            return false;
        }
        if (is_null($this->vertexW) and (is_null($this->edgeW))) {
            $defaultW = 1;
        } else {
            $defaultW = 0;
        }
        if (is_null($this->adjL)) {
            $this->adjL2M();
        }
        $this->dist[$from] = [];
        $this->pathPrev[$from] = [];
        for ($j = 0; $j < $this->v; $j++) {
            $this->dist[$from][$j] = self::INFINITY;
        }
        $this->dist[$from][$from] = 0;
        for ($i = 0; $i < $this->v; $i++) {
            foreach ($this->adjL as $u => $adj) {
                foreach ($adj as $w) {
                    if ($this->dist[$from][$u] == self::INFINITY) {
                        continue;
                    }
                    $alt = $this->dist[$from][$u] + ($this->edgeW[$u][$w] ?? $defaultW) + ($this->vertexW[$w] ?? 0);
                    if ($alt < $this->dist[$from][$w]) {
                        $this->dist[$from][$w] = $alt;
                        $this->pathPrev[$from][$w] = $u;
                    }
                }
            }
        }
        foreach ($this->adjL as $u => $adj) {
            foreach ($adj as $w) {
                if ($this->dist[$from][$u] == self::INFINITY) {
                    continue;
                }
                $alt = $this->dist[$from][$u] + ($this->edgeW[$u][$w] ?? $defaultW) + ($this->vertexW[$w] ?? 0);
                if ($alt < $this->dist[$from][$w]) {
                    $this->hasNegativeCycle = true;
                    return false;
                }
            }
        }
        if (isset($this->vertexW[$from])) {
            for ($j = 0; $j < $this->v; $j++) {
                if ($this->dist[$from][$j] < self::INFINITY) {
                    $this->dist[$from][$j] += $this->vertexW[$from] ?? 0;
                }
            }
        }
        return true;
    }
    // function bellmanFord

    // A* algorithm
    //  find shortest path (only positive weights) using heuristic distance estimation
    //      https://en.wikipedia.org/wiki/A*_search_algorithm
    //      input: v, adjL (or adjM), vertexW, edgeW (uses 1 if both NULL), heuristicScore
    //      result: dist[$from], pathPrev[$from]
    //      time: with min-priority queue: O(e), memory: v^2
    public function aStar(int $from): bool
    {
        if (($from < 0) or ($from >= $this->v) or ($this->targetIdx < 0) or ($this->targetIdx >= $this->v)) {
            return false;
        }
        if (is_null($this->heuristicScore)) {
            $wasHeurScores = false;
            $this->heuristicScore = [];
            for ($j = 0; $j < $this->v; $j++) {
                $this->heuristicScore[$j] = 0;
            }      // with no heuristic score, A* is same as Dijkstra
        } else {
            $wasHeurScores = true;
        }
        if (is_null($this->vertexW) and (is_null($this->edgeW))) {
            $defaultW = 1;
        } else {
            $defaultW = 0;
        }
        if (is_null($this->adjL)) {
            $this->adjL2M();
        }
        $pq = new MyMinPriorityQueue();             // OpenSet
        $inClosedSet = [];                     // ClosedSet (array[idx] of bool)
        $this->dist[$from] = [];               // gScore (distance: from -> idx
        $fScore = [];                          // fScore (gScore + heuristicDist)
        $this->pathPrev[$from] = [];           // cameFrom
        for ($j = 0; $j < $this->v; $j++) {
            $this->dist[$from][$j] = self::INFINITY;
            $fScore[$j] = self::INFINITY;
        }
        $this->dist[$from][$from] = 0;
        $fScore[$from] = $this->heuristicScore[$from];
        $pq->insert($from, $fScore[$from]);
        while (!$pq->isEmpty()) {
            $curr = $pq->extract();
            $inClosedSet[$curr] = true;
            if ($curr == $this->targetIdx) {
                if (isset($this->vertexW[$from])) {
                    for ($j = 0; $j < $this->v; $j++) {
                        if ($this->dist[$from][$j] < self::INFINITY) {
                            $this->dist[$from][$j] += $this->vertexW[$from] ?? 0;
                        }
                    }
                }
                if (!$wasHeurScores) {
                    $this->heuristicScore = null;
                }
                return true;
            }
            foreach ($this->adjL[$curr] as $w) {
                if (($inClosedSet[$w] ?? false)) {
                    continue;
                }
                if ($this->dist[$from][$curr] == self::INFINITY) {
                    $tentative_gScore = self::INFINITY;
                }
                $tentative_gScore = $this->dist[$from][$curr] + ($this->edgeW[$curr][$w] ?? $defaultW)
                    + ($this->vertexW[$w] ?? 0);
                if (!$pq->exists($w)) {
                    $pq->insert($w, $fScore[$w]);
                }
                if ($tentative_gScore < $this->dist[$from][$w]) {
                    $this->pathPrev[$from][$w] = $curr;
                    $this->dist[$from][$w] = $tentative_gScore;
                    $fScore[$w] = $this->dist[$from][$w] + $this->heuristicScore[$w];
                    $pq->changePriority($w, $fScore[$w]);
                }
            }
        }
        if (isset($this->vertexW[$from])) {
            for ($j = 0; $j < $this->v; $j++) {
                if ($this->dist[$from][$j] < self::INFINITY) {
                    $this->dist[$from][$j] += $this->vertexW[$from] ?? 0;
                }
            }
        }
        if (!$wasHeurScores) {
            $this->heuristicScore = null;
        }
        return false;
    }
    // function aStar

    // reconstruct shortest path (already calculated with floydWarshall, bellmanFord, dijkstra or aStar)
    //      input: from, to, pathNext or pathPrev
    //      result: array of idx
    public function getPath(int $from, int $to): array
    {
        if ($from == $to) {
            return [$from];
        }
        $path = [];
        if (!is_null($this->pathNext) and isset($this->pathNext[$from][$to])) {
            $path[] = $from;
            while ($from != $to) {
                $from = $this->pathNext[$from][$to];
                $path[] = $from;
            }
        } elseif (!is_null($this->pathPrev) and isset($this->pathPrev[$from][$to])) {
            $path[] = $to;
            while ($from != $to) {
                $to = $this->pathPrev[$from][$to];
                $path[] = $to;
            }
            $path = array_reverse($path);
        }
        return $path;
    }
    // function getPath

    // Breadth-first search
    //      https://en.wikipedia.org/wiki/Breadth-first_search
    //      input: v, adjL
    //      result: parent
    //      time: O(v^2) or with min-priority queue: O(e + v * log v), memory: v^2
    public function BFS(int $from, ?callable $func = null): bool
    {
        if (($from < 0) or ($from >= $this->v)) {
            return false;
        }
        $visited = [];
        $this->parent = [];
        $q = [];
        $qWriteIdx = 0;
        $qReadIdx = 0;
        $visited[$from] = true;
        $q[$qWriteIdx++] = $from;
        while ($qReadIdx < $qWriteIdx) {
            $curr = $q[$qReadIdx++];
            if (!is_null($func) and $func($curr)) {   // target found
                return true;
            }
            foreach ($this->adjL[$curr] as $w) {
                if (!isset($visited[$w])) {
                    $visited[$w] = true;
                    $this->parent[$w] = $curr;
                    $q[$qWriteIdx++] = $w;
                }
            }
        }
        return false;
    }
    // function BFS

    // Depth-first search - iterative
    //      https://en.wikipedia.org/wiki/Depth-first_search
    //      input: v, adjL
    //      time: O(v + e), memory: v
    public function dfsIterative(int $from, ?callable $func = null): bool
    {
        if (($from < 0) or ($from >= $this->v)) {
            return false;
        }
        $visited = [];
        $this->parent = [];
        $stack = [];
        $stack[] = $from;
        while (count($stack) > 0) {
            $curr = array_pop($stack);
            if (!isset($visited[$curr])) {
                if (!is_null($func) and $func($curr)) {       // target found
                    return true;
                }
                $visited[$curr] = true;
                foreach ($this->adjL[$curr] as $w) {
                    $this->parent[$w] = $curr;
                    $stack[] = $w;
                }
            }
        }
        return false;
    }
    // function dfsIterative

    // Depth-first search - recursive
    //      https://en.wikipedia.org/wiki/Depth-first_search
    //      input: v, adjL
    //      result: componentIdx (partial)
    //      time: O(v + e), memory: v
    public function dfs(int $from, int $compIdx = 0, ?callable $func = null): void
    {
        $this->componentIdx[$from] = $compIdx;
        if (!is_null($func)) {
            $func($from);
        }
        foreach ($this->adjL[$from] as $w) {
            if (!isset($this->componentIdx[$w]) or ($this->componentIdx[$w] != $compIdx)) {
                $this->parent[$w] = $from;
                $this->dfs($w, $compIdx, $func);
            }
        }
    }
    // function dfs

    // callback function for BFS, DFS, and dfsIterative
    // return TRUE to terminate search
    public static function callFunction(int $idx): bool
    {
        echo ' ' . $idx;
        return false;
    }

    // Count components and calculates component memberships for undirected graph, using DFS
    //      input: v, adjL
    //      result: componentIdx, componentCount
    public function calculateComponents(): void
    {
        if ($this->isDirected) {
            return;
        }
        $this->componentIdx = [];
        $this->componentCount = 0;
        for ($i = 0; $i < $this->v; $i++) {
            if (!isset($this->componentIdx[$i])) {
                $this->dfs($i, $this->componentCount);
                $this->componentCount++;
            }
        }
    }
    // function calculateComponents
}
