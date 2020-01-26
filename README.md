# graph
Graph class &amp; some common algorithms in PHP

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
    
