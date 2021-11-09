@echo off
php src\test_graph.php < tests\input_graph_01.txt > output\output_01.txt 2>&1
php src\test_graph.php < tests\input_graph_02.txt > output\output_02.txt 2>&1
php src\test_graph.php < tests\input_graph_03.txt > output\output_03.txt 2>&1
php src\test_graph.php < tests\input_graph_04.txt > output\output_04.txt 2>&1
call convert.bat
php src\test_graph.php < output\converted_graph_b2t03.txt > output\output_b2t03.txt 2>&1
php src\test_graph.php < output\converted_graph_b2t04.txt > output\output_b2t04.txt 2>&1
