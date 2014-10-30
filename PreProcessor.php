<?php

define("THIS_PATH", realpath(dirname(__FILE__)));

$opts = getopt("", ["path:"]);

if(!isset($opts["path"])){
	exit(1);
}

$path = realpath($opts["path"]);

function process($code){
	$descriptor = [
		0 => ["pipe", "r"],
		1 => ["pipe", "w"],
		2 => ["pipe", "pipe", "a"]
	];
	$process = proc_open("cpp -traditional-cpp -nostdinc -include '".THIS_PATH."/processed/rules/PocketMine.h' -I ./processed -E -C -P -D FULL - -o -", $descriptor, $pipes);
	fwrite($pipes[0], $code);
	fclose($pipes[0]);
	$out = stream_get_contents($pipes[1]);
	fclose($pipes[1]);
	$error = stream_get_contents($pipes[2]);
	if(trim($error) != ""){
		fwrite(STDERR, $error);
	}
	fclose($pipes[2]);
	proc_close($process);
	return substr($out, strpos($out, "<?php"));
}

@mkdir(THIS_PATH."/processed/rules/", 0777, true);

foreach(glob(THIS_PATH."/rules/*.h") as $file){
	if(substr($file, -2) !== ".h"){
		continue;
	}
	$code = str_replace(["::", "->", '$'], ["__STATIC_CALL__", "__METHOD_CALL__", "__VARIABLE_DOLLAR__"], file_get_contents($file));
	file_put_contents(sTHIS_PATH."/processed/$file", $code);
}

foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path)) as $path => $f){
	if(substr($path, -4) !== ".php"){
		continue;
	}
	$oldCode = file_get_contents($path);
	$code = str_replace(["__STATIC_CALL__", "__METHOD_CALL__", "__VARIABLE_DOLLAR__", "__STARTING_COMMENT_BADLINE__"], ["::", "->", '$', " * |  _ \\ ___   ___| | _____| |_|  \\/  (_)_ __   ___      |  \\/  |  _ \\"],
		process(str_replace(["::", "->", '$', " * |  _ \\ ___   ___| | _____| |_|  \\/  (_)_ __   ___      |  \\/  |  _ \\"], ["__STATIC_CALL__", "__METHOD_CALL__", "__VARIABLE_DOLLAR__", "__STARTING_COMMENT_BADLINE__"], $oldCode))
	);
	if(trim($oldCode) !== trim($code)){
		echo "Processed $path\n";
		file_put_contents($path, $code);
	}
}