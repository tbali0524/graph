@echo off
php src\test_graph.php <test\input_graph_01.txt >test\output_01.txt 2>&1
php src\test_graph.php <test\input_graph_02.txt >test\output_02.txt 2>&1
php src\test_graph.php <test\input_graph_03.txt >test\output_03.txt 2>&1
php src\test_graph.php <test\input_graph_04.txt >test\output_04.txt 2>&1
call convert.bat
php src\test_graph.php <test\converted_graph_b2t03.txt >test\output_b2t03.txt 2>&1
php src\test_graph.php <test\converted_graph_b2t04.txt >test\output_b2t04.txt 2>&1
