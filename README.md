# graph
Graph class and some related common algorithms in PHP\
(c) 2021 by Balint Toth (TBali)\
v1.1

Methods:
* `public function analyzeGraph(): void`
* `public function reportGraph(int $maxVertex = self::DEBUG_MAX_VERTEX_TO_SHOW): string`
* `public function readGraph(): void`
* `public function writeGraph(): void`
* `public function adjL2M(): void`
* `public function mirrorEdges(): void`
* `public function countEdges(): void`
* `public function countDegrees(): void`
* `public function checkNegativeWeight(): bool`
* `public function floydWarshall(bool $createPath = TRUE): void`
* `public function dijkstra(int $from): void`
* `public function bellmanFord(int $from): bool`
* `public function aStar(int $from): bool`
* `public function getPath(int $from, int $to): array`
* `public function BFS(int $from, callable $func = NULL): bool`
* `public function DFS_iterative(int $from, callable $func = NULL): bool`
* `public function DFS(int $from, int $compIdx = 0, callable $func = NULL): void`
   * `static function callFunction(int $idx): bool`
* `public function calculateComponents(): void`

Basic usage:
> php test_graph.php [id] <input_graph.txt >output.txt
  * if `id` is omitted, then it reads graph description from input stream and writes report to output stream.
  * if `id` is given, then it only converts graph from specific CG puzzle test case format to own internal format
    * 0 = Bender2, 1 = Plague Jr, 2 = A-star expercise

Helper scripts (Windows only):
* Create graph report for all sample graphs in `test\input*.txt`:
> test.bat

* Clear all generated files (`test\output*.txt` and `test\converted*.txt`):
> clear.bat

Convert some CG puzzle test cases to internal graph description format:
> convert.bat

Requirements: `PHP v7.3` or later.

Licensed under MIT license.
