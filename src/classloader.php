<?php

// checks PHP minimum version requirement and loads all classes

declare(strict_types=1);

const MIN_PHP_VERSION = "7.3.0";
if (version_compare(phpversion(), MIN_PHP_VERSION, "<")) {
    echo "ERROR: Minimum required PHP version is " . MIN_PHP_VERSION . "; you are on " . phpversion() . PHP_EOL;
    exit();
}

require_once "node.php";
require_once "minpriorityqueue.php";
require_once "myminpriorityqueue.php";
require_once "graph.php";
require_once "graphwrapper.php";
