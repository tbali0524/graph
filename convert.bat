@echo off
echo The constant GENERATE_TEST_CASE must be set to true in source file!
echo The constant PUZZLE_ID can be set in the source file: 0 = Bender2, 1 = Plague Jr, 2 = A-star expercise
php src\graph.php <test\input_orig_b2t03.txt >test\test-graph_b2t03.txt
php src\graph.php <test\input_orig_b2t04.txt >test\test-graph_b2t04.txt
