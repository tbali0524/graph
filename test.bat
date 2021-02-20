@echo off
php src\graph.php <test\test-graph_01.txt >test\output_01.txt 2>&1
php src\graph.php <test\test-graph_02.txt >test\output_02.txt 2>&1
php src\graph.php <test\test-graph_03.txt >test\output_03.txt 2>&1
php src\graph.php <test\test-graph_04.txt >test\output_04.txt 2>&1
php src\graph.php <test\test-graph_b2t03.txt >test\output_b2t03.txt 2>&1
php src\graph.php <test\test-graph_b2t04.txt >test\output_b2t04.txt 2>&1
