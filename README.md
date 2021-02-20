# graph
Graph class &amp; some common algorithms in PHP

* public function analyzeGraph(): void
* public function reportGraph(int $maxVertex = DEBUG_MAX_VERTEX_TO_SHOW): string
* public function readGraph(): void
* public function writeGraph(): void
* public function adjL2M(): void
* public function mirrorEdges(): void
* public function countEdges(): void
* public function countDegrees(): void
* public function checkNegativeWeight(): bool
* public function floydWarshall(bool $createPath = TRUE): void
* public function dijkstra(int $from): void
* public function bellmanFord(int $from): bool
* public function aStar(int $from): bool
* public function getPath(int $from, int $to): array
* public function BFS(int $from, callable $func = NULL): bool
* public function DFS_iterative(int $from, callable $func = NULL): bool
* public function DFS(int $from, int $compIdx = 0, callable $func = NULL): void
*   static function callFunction(int $idx): bool
* public function calculateComponents(): void

Usage:
> php graph.php <input.txt >output.txt

Create graph report for all sample graphs in test/input*.txt
> test.bat

Clear all generated files (test/output*.txt)
> clear.bat

Convert some CG puzzle test cases to internal graph description format
> convert.bat
Notes:
The constant GENERATE_TEST_CASE must be set to true in source file!
The constant PUZZLE_ID can be set in the source file: 0 = Bender2, 1 = Plague Jr, 2 = A-star expercise


Licence: GNU General Public License v3
