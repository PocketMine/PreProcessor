<?php

include("CCPP.class.php");

$processor = new CCPP();

file_put_contents("example_result.php", $processor->parseFilename("example.php"));