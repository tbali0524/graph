<?php
// --------------------------------------------------------------------
// Graph class
// (c) 2020 by Balint Toth

define('DEBUG', FALSE);
define('DEBUG_MAX_VERTEX_TO_SHOW', 5);
define('GENERATE_TEST_CASE', FALSE);
define('PUZZLE_ID', 0);         // 0 = Bender2, 1 = Plague Jr, 2 = A-star expercise

// --------------------------------------------------------------------
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

} // class Node

// --------------------------------------------------------------------
class Graph
{
    public $desc = '';          // graph description        string
    // graph representation                                 // type
    public $v = 0;              // number of vertices:      int
    public $isDirected = TRUE;
    public $vertexW = NULL;     // weights of vertices:     array of int
    public $edgeW = NULL;       // weights of edges:        array[idx][idx] of int or unset
    public $adjL = NULL;        // adjacency list:          array[idx] of array of idx    
    // payload
    public $node = NULL;        // the actual data          array[idx] of Node
    // other input parameters
    public $startIdx = 0;       //                          int                     A*
    public $targetIdx = 0;      //                          int                     A*
    public $heuristicScore = NULL; //                       array[idx] of int       A*: heuristic distance: idx -> targetIdx
 
    const Infinity = PHP_INT_MAX >> 2;  // less than PHP_INT_MAX to avoid addition overflow in some algorithms

    // calculated data                                      // type                 // set by this method
    public $e = NULL;           // number of edges:         int                     countEdges
    public $adjM = NULL;        // adjacency matrix:        array[idx][idx] of int  adjL2M
    public $inDegree = NULL;    // in degree:               array[idx] of int       countDegrees
    public $outDegree = NULL;   // out degree:              array[idx] of int       countDegrees
    public $hasNegativeWeight = NULL;   //                  bool                    checkNegativeWeight
    public $hasNegativeCycle = NULL;    //                  bool                    bellmanFord
    public $dist = NULL;        // shortest path distance:  array[idx][idx] of int  floydWarshall, dijkstra, bellmanFord
    public $pathNext = NULL;    // shortest path next:      array[idx][idx] of idx  floydWarshall
    public $pathPrev = NULL;    // shortest path prev:      array[idx][idx] of idx  dijkstra, bellmanFord
    public $parent = NULL;      // parent in BFS/DFS tree:  array[idx] of int       DFS, BFS
    public $componentIdx = NULL; // component membership:   array[idx] of int       DFS/BFS, countComponents
    public $componentCount = NULL; // # of components       int                     countComponents

     /* methods
    public function analyzeGraph(): void
    public function reportGraph(int $maxVertex = DEBUG_MAX_VERTEX_TO_SHOW): string
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
    public function DFS_iterative(int $from, callable $func = NULL): bool
    public function DFS(int $from, int $compIdx = 0, callable $func = NULL): void
        static function callFunction(int $idx): bool
    public function calculateComponents(): void
    */

    // calls all graph analyzer methods 
    //      input: v, isDirected, adjL (or adjM), vertexW, edgeW, 
    //      result: all other class properties
    public function analyzeGraph(): void
    {
        if (!$this->isDirected)
            $this->mirrorEdges();
        $this->adjL2M();
        $this->countEdges();
        $this->countDegrees();
        $this->checkNegativeWeight();
        if ($this->hasNegativeWeight)
        {
            for ($i = 0; $i < $this->v; $i++)
                $this->bellmanFord($i);
            if (!isset($this->hasNegativeCycle))
            $this->hasNegativeCycle = FALSE;
            $this->floydWarshall();
        }
        else
            for ($i = 0; $i < $this->v; $i++)
                $this->dijkstra($i);
        $this->calculateComponents();
        $this->startIdx = 0;
        $this->targetIdx = $this->v - 1;
        $this->aStar($this->startIdx);
        $this->BFS($this->startIdx);
        $this->DFS_iterative($this->startIdx);
    } // function analyzeGraph
    
    // returns graph report in multi-line string
    public function reportGraph(int $maxVertex = DEBUG_MAX_VERTEX_TO_SHOW): string
    {
        $maxVertex = min($this->v, $maxVertex);
        $vw = ($maxVertex < 100 ? 2 : 3);       // format width for vertex index display
        $ww = 5;                                // format width for weight display
        $s = 'GRAPH REPORT: ' . $this->desc .  "\n  ";
        if ($this->isDirected)
            $s .= 'directed ';
        $s .= 'graph: ' . $this->v;
        if (!is_null($this->vertexW))
            $s .= ' weighted';
        $s .= ' vertices';
        if (!is_null($this->e))
        {
            $s .= ', ' . $this->e;
            if (!is_null($this->edgeW))
                $s .= ' weighted';
            $s .= ' edges';
        }
        if (!is_null($this->componentCount))
            $s .= ', ' . $this->componentCount . ' components';
        $s .= "\n";
        if ($this->v > $maxVertex)
            $s .= '  (showing first ' . $maxVertex . " vertices) \n";
        if (!is_null($this->hasNegativeWeight) and (!is_null($this->vertexW) or !is_null($this->edgeW)))
            $s .= '  graph has ' . ($this->hasNegativeWeight ? '': 'no ') . "negative weight.\n";
        if (!is_null($this->hasNegativeCycle))
            $s .= '  graph has ' . ($this->hasNegativeCycle ? '': 'no ') . "negative cycle.\n";
        if (!is_null($this->adjL))
        {
            $s .= "  adjacency list:\n";
            for ($i = 0; $i < $maxVertex; $i++)
            {
                $s .= '    vertex #' . str_pad($i, $vw) . ': ';
                if (isset($this->adjL[$i]))
                {
                    $s .= 'edges to ';
                    foreach ($this->adjL[$i] as $j)
                    {
                        $s .= str_pad($j, $vw);
                        if (isset($this->edgeW[$i][$j]))
                            $s .= ' (W= ' . str_pad(intval($this->edgeW[$i][$j]), $ww) . ')';
                        $s .= ', ';
                    }
                    if (count($this->adjL[$i]) == 0)
                        $s .= '[none]';
                }
                $s .= "\n";
            }
        }
        $s .= '  vertices:     ';
        for ($i = 0; $i < $maxVertex; $i++)
            $s .= '+#' . str_pad($i, $ww - 1, '-');
        $s .= "\n";
        if (!is_null($this->vertexW))
        {
            $s .= '    weight:     ';
            for ($i = 0; $i < $maxVertex; $i++)
                $s .= '|' . str_pad(($this->vertexW[$i] ?? '-'), $ww);
            $s .= "\n";
        }
        if (!is_null($this->outDegree))
        {
            $s .= '    out degree: ';
            for ($i = 0; $i < $maxVertex; $i++)
                $s .= '|' . str_pad($this->outDegree[$i], $ww);
            $s .= "\n";
        }
        if (!is_null($this->inDegree))
        {
            $s .= '    in degree:  ';
            for ($i = 0; $i < $maxVertex; $i++)
                $s .= '|' . str_pad($this->inDegree[$i], $ww);
            $s .= "\n";
        }
        if (!is_null($this->heuristicScore))
        {
            $s .= '       h-score: ';
            for ($i = 0; $i < $maxVertex; $i++)
                $s .= '|' . str_pad($this->heuristicScore[$i], $ww);
            $s .= "\n";
        }
        if (!is_null($this->adjM))
        {
            $s .= "  adjacency matrix:\n";
            $s .= str_repeat(' ', 5 + $vw) . '+'; 
            for ($j = 0; $j < $maxVertex; $j++)
                $s .= str_pad($j, $vw + 1, '-');
            if (!is_null($this->edgeW))
            {
                $s .= '+ weights: ';
                for ($j = 0; $j < $maxVertex; $j++)
                    $s .= '+#' . str_pad($j, $ww - 1, '-');
            }
            $s .= "\n";
            for ($i = 0; $i < $maxVertex; $i++)
            {
                $s .= '    #' . str_pad($i, $vw) . '|';
                for ($j = 0; $j < $maxVertex; $j++)
                    $s .= str_pad($this->adjM[$i][$j], $vw) . ' ';
                if (!is_null($this->edgeW))
                {
                    $s .= '|          ';
                    for ($j = 0; $j < $maxVertex; $j++)
                        $s .= '|' . str_pad(($this->edgeW[$i][$j] ?? '-'), $ww);
                }
                $s .= "\n";
            }
        }
        if (!is_null($this->dist))
        {
            $s .= "  shortest path distances:\n";
            $s .= str_repeat(' ', $vw + 5); 
            for ($i = 0; $i < $maxVertex; $i++)
                $s .= '+#' . str_pad($i, $ww - 1, '-');
            $s .= "\n";
            for ($i = 0; $i < $maxVertex; $i++)
            {
                $s .= '    #' . str_pad($i, $vw);
                for ($j = 0; $j < $maxVertex; $j++)
                    if (!isset($this->dist[$i][$j]))
                        $s .= str_pad('|?', $ww + 1);
                    elseif ($this->dist[$i][$j] >= self::Infinity)
                        $s .= str_pad('|INF', $ww + 1);
                    else
                        $s .= '|' . str_pad($this->dist[$i][$j], $ww);
                $s .= "\n";
            }
        }
        if (!is_null($this->pathNext))
        {
            $s .= "  shortest path - next vertices:\n";
            $s .= str_repeat(' ', $vw + 5); 
            for ($i = 0; $i < $maxVertex; $i++)
                $s .= '+' . str_pad($i, $vw, '-');
            $s .= "\n";
            for ($i = 0; $i < $maxVertex; $i++)
            {
                $s .= '    #' . str_pad($i, $vw);
                for ($j = 0; $j < $maxVertex; $j++)
                    $s .= '|' . str_pad(($this->pathNext[$i][$j] ?? 'x'), $vw);
                $s .= "\n";
            }
        }
        if (!is_null($this->pathPrev))
        {
            $s .= "  shortest path - previous vertices:\n";
            $s .= str_repeat(' ', $vw + 5); 
            for ($i = 0; $i < $maxVertex; $i++)
                $s .= '+' . str_pad($i, $vw, '-');
            $s .= "\n";
            for ($i = 0; $i < $maxVertex; $i++)
            {
                $s .= '    #' . str_pad($i, $vw);
                for ($j = 0; $j < $maxVertex; $j++)
                    $s .= '|' . str_pad(($this->pathPrev[$i][$j]  ?? 'x'), $vw);
                $s .= "\n";
            }
        }
        if (!is_null($this->componentIdx) and !is_null($this->componentCount) and ($this->componentCount > 1))
        {
            $s .= "  components:\n";
            for ($i = 0; $i < $this->componentCount; $i++)
            {
                $s .= '    #' . $i . ':';
                for ($j = 0; $j < $this->v; $j++)
                    if ($this->componentIdx[$j] == $i)
                        $s .= ' ' . $j;
                $s .= "\n";
            }
        }
        return $s;
    } // function reportGraph

    private function readNextLine(bool $isTabular = TRUE): string
    {
        do {
            $s = trim(fgets(STDIN));
        } while (($s == '') or ($s[0] == '#'));
        if ($isTabular)
            $s = str_replace(' ', '', $s);
        $p = strpos($s, '#');
        if ($p === FALSE)
            return $s;
        else
            return rtrim(substr($s, 0, $p));
    }
  
    // reads graph data from standard input. Limited error handling
    public function readGraph(): void
    {
        $s = $this->readNextLine(FALSE);
        if ($s == '; desc')
        {
            $this->desc = $this->readNextLine(FALSE);
            $s = $this->readNextLine(FALSE);
        }
        if ($s == '; isDirected')
        {
            $s = strtolower($this->readNextLine(FALSE));
            $this->isDirected = (($s == 'directed') or ($s == 'true') or ($s == '1'));
            $s = $this->readNextLine(FALSE);
        }
        if ($s == '; vertices')
        {
            $s = $this->readNextLine(FALSE);
            if (is_numeric($s) and ($s >= 0))
                $this->v = intval($s);
            $s = $this->readNextLine(FALSE);
        }
        if ($s == '; vertex weights')
        {
            $line = explode(',', $this->readNextLine());
            if (count($line) == $this->v)
                $this->vertexW = $line;
            $s = $this->readNextLine(FALSE);
        }
        if ($s == '; adjacency list')
        {
            $this->adjL = array();
            for ($i = 0; $i < $this->v; $i++)
            {
                $line = explode(',', $this->readNextLine());
                if (strtolower($line[0]) == 'none')
                    $this->adjL[$i] = [];
                else
                    $this->adjL[$i] = $line;
            }
            $s = $this->readNextLine(FALSE);
        }
        if ($s == '; adjacency matrix')
        {
            $this->adjL = NULL;
            $this->adjM = array();
            for ($i = 0; $i < $this->v; $i++)
                $this->adjM[$i] = explode(',', $this->readNextLine());
            $s = $this->readNextLine(FALSE);
        }
        if ($s == '; edge weights')
        {
            $e = $this->readNextLine(FALSE);
            $this->adjM = NULL;
            $this->adjL = array();
            $this->edgeW = array();
            for ($i = 0; $i < $this->v; $i++)
            {
                $this->adjL[$i] = array(); 
                $this->edgeW[$i] = array(); 
            }
            for ($i = 0; $i < $e; $i++)
            {
                $line = explode(',', $this->readNextLine());
                if (count($line) < 3)
                    continue;
                $this->adjL[$line[0]][] = $line[1];
                $this->edgeW[$line[0]][$line[1]] = $line[2];
                if (!$this->isDirected)
                {
                    $this->adjL[$line[1]][] = $line[0];
                    $this->edgeW[$line[1]][$line[0]] = $line[2];
                }
            }
            $s = $this->readNextLine(FALSE);
        }
        if ($s == '; end')
            return;        
    } // function readGraph

    // writes graph data to string in graph file format
    public function writeGraph(): string
    {
        $s = "# input file for Graph.PHP\n";
        if (!is_null($this->desc))
        {
            $s .= "; desc\n";
            $s .= $this->desc . "\n";
        }
        $s .= "; isDirected\n";
        $s .= ($this->isDirected ? "directed\n" : "undirected\n");
        $s .= "; vertices\n";
        $s .= $this->v . "\n";
        if (!is_null($this->vertexW))
        {
            $s .= "; vertex weights\n";
            $s .= implode(', ', ($this->vertexW ?? [])) . "\n";
        }
        if (!is_null($this->edgeW))
        {
            $s .= "; edge weights\n";
            if (is_null($this->e))
                $this->countEdges();
            $s .= $this->e . "\n";
            for ($i = 0; $i < $this->v; $i++)
                for ($j = 0; $j < $this->v; $j++)
                    if (isset($this->edgeW[$i][$j]))
                        $s .= $i . ', ' . $j . ', ' . $this->edgeW[$i][$j] . "\n";  
        }
        elseif (!is_null($this->adjL))
        {
            $s .= "; adjacency list\n";
            for ($i = 0; $i < $this->v; $i++)
            {
                $line = implode(', ', $this->adjL[$i]);
                if ($line == '')
                    $line = 'none';
                $s .= $line . "\n";
            }
        }
        elseif (!is_null($this->adjM))
        {
            $s .= "; adjacency matrix\n";
            for ($i = 0; $i < $this->v; $i++)
                $s .= implode(', ', $this->adjL[$i]) . "\n";
        }
        $s .= "; end\n";
        return $s;        
    } // function writeGraph

    // creates adjacency matrix from adjacency list or vice versa
    //      input: v, adjL (or adjM)
    //      result: adjM (or adjL)
    public function adjL2M(): void
    {
        if (!is_null($this->adjL))
        {
            $this->adjM = array();
            for ($i = 0; $i < $this->v; $i++)
            {
                for ($j = 0; $j < $this->v; $j++)
                    $this->adjM[$i][$j] = 0;
                foreach ($this->adjL[$i] as $j)
                    $this->adjM[$i][$j]++;
            }
        }
        elseif (!is_null($this->adjM))
        {
            $this->adjL = array();
            for ($i = 0; $i < $this->v; $i++)
            {
                $this->adjL[$i] = array(); 
                for ($j = 0; $j < $this->v; $j++)
                    for ($k = 0; $k < $this->adjM[$i][$j]; $k++)
                        $this->adjL[$i][] = $j;
            }
        }
    } // function adjL2M()

    // makes all edges bidirectional
    //      input: v, adjL (or adjM)
    //      result: adjM, adjL
    public function mirrorEdges(): void
    {
        if (is_null($this->adjM))
            $this->adjL2M();
        for ($i = 0; $i < $this->v; $i++)
            for ($j = 0; $j < $this->v; $j++)
            {
                $e = max($this->adjM[$i][$j], $this->adjM[$j][$i]);
                $this->adjM[$i][$j] = $e;
                $this->adjM[$j][$i] = $e;
            }
        if (!is_null($this->edgeW))
            for ($i = 0; $i < $this->v; $i++)
                for ($j = 0; $j < $this->v; $j++)
                {
                    if (isset($this->edgeW[$i][$j]) and isset($this->edgeW[$j][$i]))
                        $e = max($this->edgeW[$i][$j], $this->edgeW[$j][$i]);
                    elseif (isset($this->edgeW[$i][$j]))
                        $e = $this->edgeW[$i][$j];
                    elseif (isset($this->edgeW[$j][$i]))
                        $e = $this->edgeW[$j][$i];
                    else
                        continue;
                    $this->edgeW[$i][$j] = $e;
                    $this->edgeW[$j][$i] = $e;
                }
        $this->adjL = NULL;
        $this->adjL2M();
    } // function mirrorEdges

    // calculate number of edges
    //      input: v, isDirected, adjL or adjM
    //      result: e
    public function countEdges(): void
    {
        $this->e = 0;
        if (!is_null($this->adjL))
            foreach ($this->adjL as $adj)
                $this->e += count($adj);
        else 
            foreach ($this->adjM as $adj)
                foreach ($adj as $value)
                    $this->e += $value;
        if (!$this->isDirected)
            $this->e = intdiv($this->e, 2);
    } // function countEdges()

    // calculate degrees
    //      input: v, adjL
    //      result: inDegree, outDegree
    public function countDegrees(): void
    {
        $this->inDegree = array();
        $this->outDegree = array();
        for ($i = 0; $i < $this->v; $i++)
        {
            $this->outDegree[$i] = 0;
            $this->inDegree[$i] = 0;
        }
        foreach ($this->adjL as $from => $adj)
            foreach ($adj as $to)
            {
                $this->outDegree[$from]++;
                $this->inDegree[$to]++;
            }
    } // function countDegrees()

    // checks if graph has any negative vertex or edge weight
    //      input: vertexW or edgeW
    //      result: hasNegativeWeight
    //      time: O(e+v), memory: O(1)
    public function checkNegativeWeight(): bool
    {
        $this->hasNegativeWeight = FALSE;
        if (isset($this->edgeW))
            foreach ($this->edgeW as $edg)
                foreach ($edg as $weight)
                    if ($weight < 0)
                    {
                        $this->hasNegativeWeight = TRUE;
                        return TRUE;
                    }
        if (isset($this->vertexW))
            foreach ($this->vertexW as $weight)
                if ($weight < 0)
                {
                    $this->hasNegativeWeight = TRUE;
                    return TRUE;
                }
        return FALSE;
    } // function checkNegativeWeight

    // Floyd-Warshall algorithm
    //  find shortest path distance (also with negative weights)
    //      https://en.wikipedia.org/wiki/Floyd%E2%80%93Warshall_algorithm
    //      input: v, adjL (or adjM), vertexW, edgeW (uses 1 if both NULL)
    //      result: dist, pathNext (if called with createPath = TRUE)
    //      time: O(v^3), memory: v^2
    public function floydWarshall(bool $createPath = TRUE): void
    {
        if (is_null($this->adjL))
            $this->adjL2M();
        $this->dist = array();
        for ($i = 0; $i < $this->v; $i++)
            for ($j = 0; $j < $this->v; $j++)
                $this->dist[$i][$j] = self::Infinity;
        foreach ($this->adjL as $from => $adj)
            foreach ($adj as $to)
            {
                if (isset($this->vertexW[$to]) and isset($this->edgeW[$from][$to]))
                    $this->dist[$from][$to] = $this->vertexW[$to] + $this->edgeW[$from][$to];
                elseif (isset($this->vertexW[$to]))
                    $this->dist[$from][$to] = $this->vertexW[$to];
                elseif (isset($this->edgeW[$from][$to]))
                    $this->dist[$from][$to] = $this->edgeW[$from][$to];
                else
                    $this->dist[$from][$to] = 1;
            }
        for ($i = 0; $i < $this->v; $i++)
            $this->dist[$i][$i] = 0;
        if ($createPath)
        {
            $this->pathNext = array();
            foreach ($this->adjL as $from => $adj)
                foreach ($adj as $to)
                    $this->pathNext[$from][$to] = $to;
            for ($i = 0; $i < $this->v; $i++)
                $this->pathNext[$i][$i] = $i;
        }
        for ($k = 0; $k < $this->v; $k++)
            for ($i = 0; $i < $this->v; $i++)
                for ($j = 0; $j < $this->v; $j++)
                {
                    if (($this->dist[$i][$k] == self::Infinity) or  ($this->dist[$k][$j] == self::Infinity))
                        continue;
                    $alt = $this->dist[$i][$k] + $this->dist[$k][$j];
                    if ($alt < $this->dist[$i][$j]) 
                    {
                        $this->dist[$i][$j] = $alt;
                        if (($createPath) and isset($this->pathNext[$i][$k]))
                            $this->pathNext[$i][$j] = $this->pathNext[$i][$k];
                    }
                }
        for ($i = 0; $i < $this->v; $i++)
            if (isset($this->vertexW[$i]))
                for ($j = 0; $j < $this->v; $j++)
                    if ($this->dist[$i][$j] < self::Infinity)
                        $this->dist[$i][$j] += $this->vertexW[$i];
    } // function floydWarshall

    // Dijkstra algorithm
    //  find shortest path (only positive weights)
    //      https://en.wikipedia.org/wiki/Dijkstra%27s_algorithm
    //      input: v, adjL (or adjM), vertexW, edgeW (uses 1 if both NULL)
    //      result: dist[$from], pathPrev[$from]
    //      time: with min-priority queue: O(e + v * log v) or O(v^2) otherwise, memory: v^2
    public function dijkstra(int $from): void
    {
        if (($from < 0) or ($from >= $this->v))
            return;
        if (is_null($this->vertexW) and (is_null($this->edgeW)))
            $defaultW = 1;
        else
            $defaultW = 0;
        if (is_null($this->adjL))
            $this->adjL2M();
        if (is_null($this->hasNegativeWeight))
            $this->checkNegativeWeight();
        if ($this->hasNegativeWeight)
            return;
        $pq = new MinPriorityQueue;
        $pq->setExtractFlags(MinPriorityQueue::EXTR_DATA);
        $this->dist[$from] = array();
        $this->pathPrev[$from] = array();
        for ($j = 0; $j < $this->v; $j++)
            $this->dist[$from][$j] = self::Infinity;
        $this->dist[$from][$from] = 0;
        $pq->insert($from, 0);
        while (!$pq->isEmpty())
        {
            $curr = $pq->extract();
            if ($this->dist[$from][$curr] == self::Infinity)
                return;
            foreach ($this->adjL[$curr] as $w)
            {
                $alt = $this->dist[$from][$curr] + ($this->edgeW[$curr][$w] ?? $defaultW) + ($this->vertexW[$w] ?? 0);
                if ($alt < $this->dist[$from][$w])
                {
                    $this->dist[$from][$w] = $alt;
                    $this->pathPrev[$from][$w] = $curr;
                    $pq->insert($w, $alt);
                }
            }
        }
        if (isset($this->vertexW[$from]))
            for ($j = 0; $j < $this->v; $j++)
                if ($this->dist[$from][$j] < self::Infinity)
                    $this->dist[$from][$j] += $this->vertexW[$from];
    } // function dijkstra

    // Bellman-Ford algorithm
    //  find shortest path (also for negative weights)
    //      https://en.wikipedia.org/wiki/Bellman%E2%80%93Ford_algorithm
    //      input: v, adjL (or adjM), vertexW, edgeW (uses 1 if both NULL)
    //      result: dist[$from], pathPrev[$from], hasNegativeCycle
    //      returns FALSE if graph has negative-weight cycle or invalid source or unweighted
    //      time: O(e * v), memory: v
    public function bellmanFord(int $from): bool
    {
        if (($from < 0) or ($from >= $this->v))
            return FALSE;
        if (is_null($this->vertexW) and (is_null($this->edgeW)))
            $defaultW = 1;
        else
            $defaultW = 0;
        if (is_null($this->adjL))
            $this->adjL2M();
        $this->dist[$from] = array();
        $this->pathPrev[$from] = array();
        for ($j = 0; $j < $this->v; $j++)
            $this->dist[$from][$j] = self::Infinity;
        $this->dist[$from][$from] = 0;
        for ($i = 0; $i < $this->v; $i++)
            foreach ($this->adjL as $u => $adj)
                foreach ($adj as $w)
                {
                    if ($this->dist[$from][$u] == self::Infinity)
                        continue;
                    $alt = $this->dist[$from][$u] + ($this->edgeW[$u][$w] ?? $defaultW) + ($this->vertexW[$w] ?? 0);
                    if ($alt < $this->dist[$from][$w]) 
                    {
                        $this->dist[$from][$w] = $alt;
                        $this->pathPrev[$from][$w] = $u;
                    }
                }
        foreach ($this->adjL as $u => $adj)
            foreach ($adj as $w)
            {
                if ($this->dist[$from][$u] == self::Infinity)
                    continue;
                $alt = $this->dist[$from][$u] + ($this->edgeW[$u][$w] ?? $defaultW) + ($this->vertexW[$w] ?? 0);
                if ($alt < $this->dist[$from][$w])
                {
                    $this->hasNegativeCycle = TRUE;
                    return FALSE;
                }
            }
        if (isset($this->vertexW[$from]))
            for ($j = 0; $j < $this->v; $j++)
                if ($this->dist[$from][$j] < self::Infinity)
                    $this->dist[$from][$j] += $this->vertexW[$from];
        return TRUE;
    } // function bellmanFord

    // A* algorithm
    //  find shortest path (only positive weights) using heuristic distance estimation
    //      https://en.wikipedia.org/wiki/A*_search_algorithm
    //      input: v, adjL (or adjM), vertexW, edgeW (uses 1 if both NULL), heuristicScore
    //      result: dist[$from], pathPrev[$from]
    //      time: with min-priority queue: O(e), memory: v^2
    public function aStar(int $from): bool
    {
        if (($from < 0) or ($from >= $this->v) or ($this->targetIdx < 0) or ($this->targetIdx >= $this->v))
            return FALSE;
        if (is_null($this->heuristicScore))
        {
            $wasHeurScores = FALSE;
            $this->heuristicScore = array();
            for ($j = 0; $j < $this->v; $j++)
                $this->heuristicScore[$j] = 0;      // with no heuristic score, A* is same as Dijkstra
        }
        else
            $wasHeurScores = TRUE;
        if (is_null($this->vertexW) and (is_null($this->edgeW)))
            $defaultW = 1;
        else
            $defaultW = 0;
        if (is_null($this->adjL))
            $this->adjL2M();
        $pq = new MyMinPriorityQueue;               // OpenSet
        $inClosedSet = array();                     // ClosedSet (array[idx] of bool)
        $this->dist[$from] = array();               // gScore (distance: from -> idx
        $fScore = array();                          // fScore (gScore + heuristicDist)
        $this->pathPrev[$from] = array();           // cameFrom
        for ($j = 0; $j < $this->v; $j++)
        {
            $this->dist[$from][$j] = self::Infinity;
            $fScore[$j] = self::Infinity;
        }
        $this->dist[$from][$from] = 0;
        $fScore[$from] = $this->heuristicScore[$from];
        $pq->insert($from, $fScore[$from]);
        while (!$pq->isEmpty())
        {
            $curr = $pq->extract();
            $inClosedSet[$curr] = TRUE;
            if ($curr == $this->targetIdx)
            {
                if (isset($this->vertexW[$from]))
                    for ($j = 0; $j < $this->v; $j++)
                        if ($this->dist[$from][$j] < self::Infinity)
                            $this->dist[$from][$j] += $this->vertexW[$from];
                    if (!$wasHeurScores);
                        $this->heuristicScore = NULL;
                return TRUE;
            }
            foreach ($this->adjL[$curr] as $w)
            {
                if (($inClosedSet[$w] ?? FALSE))
                    continue;
                if ($this->dist[$from][$curr] == self::Infinity)
                    $tentative_gScore = self::Infinity;
                $tentative_gScore = $this->dist[$from][$curr] + ($this->edgeW[$curr][$w] ?? $defaultW) + ($this->vertexW[$w] ?? 0);
                if (!$pq->exists($w))
                    $pq->insert($w, $fScore[$w]);
                if ($tentative_gScore < $this->dist[$from][$w])
                {
                    $this->pathPrev[$from][$w] = $curr;
                    $this->dist[$from][$w] = $tentative_gScore;
                    $fScore[$w] = $this->dist[$from][$w] + $this->heuristicScore[$w];
                    $pq->changePriority($w, $fScore[$w]);
                }
            }
        }
        if (isset($this->vertexW[$from]))
            for ($j = 0; $j < $this->v; $j++)
                if ($this->dist[$from][$j] < self::Infinity)
                    $this->dist[$from][$j] += $this->vertexW[$from];
        if (!$wasHeurScores);
            $this->heuristicScore = NULL;
        return FALSE;
    } // function aStar

    // reconstruct shortest path (already calculated with floydWarshall, bellmanFord, dijkstra or aStar)
    //      input: from, to, pathNext or pathPrev
    //      result: array of idx
    public function getPath(int $from, int $to): array
    {
        if ($from == $to)
            return [$from];
	$path = array();
        if (!is_null($this->pathNext) and isset($this->pathNext[$from][$to]))
        {
            $path[] = $from;
            while ($from != $to)
            {
                $from = $this->pathNext[$from][$to];
                $path[] = $from;
            }
        }
        elseif (!is_null($this->pathPrev) and isset($this->pathPrev[$from][$to]))
        {
            $path[] = $to;
            while ($from != $to)
            {
                $to = $this->pathPrev[$from][$to];
                $path[] = $to;
            }
            $path = array_reverse($path);
        }
        return $path;
    } // function getPath

    // Breadth-first search
    //      https://en.wikipedia.org/wiki/Breadth-first_search
    //      input: v, adjL
    //      result: parent
    //      time: O(v^2) or with min-priority queue: O(e + v * log v), memory: v^2
    public function BFS(int $from, callable $func = NULL): bool
    {
        if (($from < 0) or ($from >= $this->v))
            return FALSE;
        $visited = array();
        $this->parent = array();
        $q = array();
        $qWriteIdx = 0;
        $qReadIdx = 0;
        $visited[$from] = TRUE;
        $q[$qWriteIdx++] = $from;
        while ($qReadIdx < $qWriteIdx)
        {
            $curr = $q[$qReadIdx++];
            if (!is_null($func) and $func($curr))   // target found
                return TRUE;
            foreach ($this->adjL[$curr] as $w)
                if (!isset($visited[$w]))
                {
                    $visited[$w] = TRUE;
                    $this->parent[$w] = $curr;
                    $q[$qWriteIdx++] = $w;
                }
        }
        return FALSE;
    } // function BFS

    // Depth-first search - iterative
    //      https://en.wikipedia.org/wiki/Depth-first_search
    //      input: v, adjL
    //      time: O(v + e), memory: v
    public function DFS_iterative(int $from, callable $func = NULL): bool
    {
        if (($from < 0) or ($from >= $this->v))
            return FALSE;
        $visited = array();
        $this->parent = array();
        $stack = array();
        $stack[] = $from;
        while (count($stack) > 0)
        {
            $curr = array_pop($stack);
            if (!isset($visited[$curr]))
            {
                if (!is_null($func) and $func($curr))       // target found
                    return TRUE;
                $visited[$curr] = TRUE;
                foreach ($this->adjL[$curr] as $w)
                {
                    $this->parent[$w] = $curr;
                    $stack[] = $w;
                }
            }
        }
        return FALSE;
    } // function DFS_iterative

    // Depth-first search - recursive
    //      https://en.wikipedia.org/wiki/Depth-first_search
    //      input: v, adjL
    //      result: componentIdx (partial)
    //      time: O(v + e), memory: v
    public function DFS(int $from, int $compIdx = 0, callable $func = NULL): void
    {
        $this->componentIdx[$from] = $compIdx;
        if (!is_null($func))
            $func($from);
        foreach ($this->adjL[$from] as $w)
            if (!isset($this->componentIdx[$w]) or ($this->componentIdx[$w] != $compIdx))
            {
                $this->parent[$w] = $from;
                $this->DFS($w, $compIdx, $func);
            }
    } // function DFS

    // callback function for BFS, DFS, and DFS_iterative
    // return TRUE to terminate search
    static function callFunction(int $idx): bool
    {
        echo ' ' . $idx;
        return FALSE;
    }

    // Count components and calculates component memberships for undirected graph, using DFS
    //      input: v, adjL
    //      result: componentIdx, componentCount
    public function calculateComponents(): void
    {
        if ($this->isDirected)
            return;
        $this->componentIdx = array();
        $this->componentCount = 0;
        for ($i = 0; $i < $this->v; $i++)
            if (!isset($this->componentIdx[$i]))
            {
                $this->DFS($i, $this->componentCount);
                $this->componentCount++;
            }
    } // function calculateComponents

} // class Graph

// --------------------------------------------------------------------
// used by Dijkstra algorithm
class MinPriorityQueue extends SPLPriorityQueue
{
	public function compare($a, $b) {
		return parent::compare($b, $a);     //inverse the order
	}
} // class MinPriorityQueue

// --------------------------------------------------------------------
// used by aStar algorithm
// naive implementation using array, allows to change priority of any item
class MyMinPriorityQueue
{
    private $data = array();                // array[0..] of value (int/float/string)
    private $priority = array();            // array[value] of int/float
    
    const ErrorMsgGetEmpty = 'Trying to get item from empty Priority queue!';
    const ErrorMsgInsertExisting = 'Trying to insert already existing item to Priority queue!';
    const ErrorMsgChangeNonExistent = 'Trying to update non-existing item in Priority queue!';
    const ErrorMsgDeleteNonExistent = 'Trying to delete non-existing item in Priority queue!';
    
	public function compare($a, $b): int
	{
	    $ans = ($this->priority[$b] ?? PHP_INT_MAX) <=> ($this->priority[$a] ?? PHP_INT_MAX);
	    if ($ans == 0)
	        $ans = $b <=> $a;
        return $ans;
	}

	public function top()
	{
        if (count($this->data) == 0)
            throw new Exception(self::ErrorMsgGetEmpty);
        return $this->data[count($this->data) - 1];
	}

	public function extract()
	{
        if (count($this->data) == 0)
            throw new Exception(self::ErrorMsgGetEmpty);
        $ans = array_pop($this->data);
        unset($this->priority[$ans]);
        return $ans;
	}
	
	public function insert($item, $priority): void
	{
	    if (isset($this->priority[$item]))
            throw new Exception(self::ErrorMsgInsertExisting);
        $this->data[] = $item;
        $this->priority[$item] = $priority;
        usort($this->data, array('MyMinPriorityQueue', 'compare'));
	}

	public function changePriority($item, $priority): void
	{
	    if (!isset($this->priority[$item]))
            throw new Exception(self::ErrorMsgChangeNonExistent);
        $this->priority[$item] = $priority;
        usort($this->data, array('MyMinPriorityQueue', 'compare'));
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
	    foreach ($this->data as $item)
	        $s .= $item . ' (' . $this->priority[$item] . ') ';
        return $s;
	}
	
} // class MinPriorityQueue

// --------------------------------------------------------------------
class GraphTest
{
    public $g = NULL;

    public function readInputBender2(): void
    {
        $g = new Graph;
        $g->desc = 'graph from Bender 2 IDE Test ...';
        fscanf(STDIN, "%d",
            $N
        );
        $g->v = $N + 1;
        $g->vertexW = array();
        // $g->edgeW = array();
        $g->adjL = array();
        for ($i = 0; $i < $N; $i++)
        {
            $room = explode(' ', trim(fgets(STDIN)));
            $r2 = (($room[2] == 'E') ? $N : $room[2]);
            $r3 = (($room[3] == 'E') ? $N : $room[3]);
            $g->adjL[$room[0]] = array();
            $g->adjL[$room[0]][] = $r2;
            if ($r3 != $r2)
                $g->adjL[$room[0]][] = $r3;
            $g->vertexW[$room[0]] = $room[1];
            // $g->edgeW[$room[0]][$r2] = -1 * $room[1];
            // $g->edgeW[$room[0]][$r3] = -1 * $room[1];
        }
        $g->vertexW[$N] = 0;
        $g->adjL[$N] = array();
        $this->g = $g;
    } // function readInputBender2

    public function readInputPlagueJr(): void
    {
        $g = new Graph;
        $g->desc = 'graph from Plague Jr puzzle IDE Test ...';
        $g->isDirected = FALSE;
        fscanf(STDIN, "%d",
            $n
        );
        $g->adjL = array();
        for ($i = 0; $i < $n; $i++)
        {
            fscanf(STDIN, "%d %d",
                $xi,
                $yi
            );
            $g->adjL[$xi][] = $yi;
            $g->adjL[$yi][] = $xi;
        }
        $g->v = count($this->adjL);
        $this->g = $g;
    } // function readInputPlagueJr

    public function readInputAStarExercise(): void
    {
        $g = new Graph;
        $g->desc = 'graph from A Star Exercise puzzle IDE Test ...';
        $g->isDirected = FALSE;
        fscanf(STDIN, "%d %d %d %d",
            $N,
            $E,
            $S,
            $G
        );
        $g->v = $N;
        $g->startIdx = $S;
        $g->targetIdx = $G;
        $inputs = explode(" ", fgets(STDIN));
        $g->heuristicScore = array();
        for ($i = 0; $i < $N; $i++)
            $g->heuristicScore[$i] = intval($inputs[$i]);
        $g->adjL = array();
        $g->edgeW = array();
        for ($i = 0; $i < $E; $i++)
        {
            fscanf(STDIN, "%d %d %d",
                $x,
                $y,
                $c
            );
            $g->adjL[$x][] = $y;
            $g->adjL[$y][] = $x;
            $g->edgeW[$x][$y] = $c;
            $g->edgeW[$y][$x] = $c;
        }
        $this->g = $g;
    } // function readInputAStarExercise

    // creates a test graph with fixed data in the source php file 
    public function getFixGraph(): void
    {
        $g = new Graph;
        $g->v = 10;
        $g->isDirected = FALSE;
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
    } // function getFixGraph

    // reads graph in my format from stdin, report to stdout 
    public function testGraph(): void
    {
        $g = new Graph;
        $g->readGraph();
        $g->analyzeGraph();
        echo $g->reportGraph(100);
        echo '  shortest path from 0 to ' . ($g->v - 1) . ': ' . implode('->', $g->getPath(0, $g->v - 1)) . "\n";
        echo '  BFS traverse order: ' ;
        $g->BFS(0, array('Graph', 'callFunction'));
        echo "\n";
        echo '  DFS traverse order: ' ;
        $g->DFS_iterative(0, array('Graph', 'callFunction'));
        echo "\n";
        $this->g = $g;
    } // function testGraph
    
} // class TestGraph

// --------------------------------------------------------------------
// ---------- main program
$gt = new GraphTest;
if (GENERATE_TEST_CASE)
{
    if (PUZZLE_ID == 0)
        $gt->readInputBender2();
    elseif (PUZZLE_ID == 1)
        $gt->readInputPlagueJr();
    elseif (PUZZLE_ID == 2)
        $gt->readInputAStarExercise();
    else
        throw new Exception('Unsupported puzzle id!');
    echo $gt->g->writeGraph();
}
else
    $gt->testGraph();
// To debug (equivalent to var_dump): error_log(var_export($var, true));
?>