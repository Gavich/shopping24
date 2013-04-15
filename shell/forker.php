<?php

define('ROOT', getcwd());

$shellProcess = ROOT . DIRECTORY_SEPARATOR . 'product_import.php';
define('WANT_PROCESSORS', 5);
define('PROCESSOR_EXECUTABLE', $shellProcess);
set_time_limit(0);
ini_set('display_errors', 1);
error_reporting(E_ALL | E_STRICT);
$cycles = 0;
$run = true;
$log = ROOT . DIRECTORY_SEPARATOR . 'import.log';
$reload = false;
declare(ticks = 30);
if (@file_exists($log)){
	unlink($log);
}

function signal_handler($signal){
	switch($signal){
	case SIGTERM :
		global $run;
		$run = false;
		break;
	case SIGHUP  :
		global $reload;
		$reload = true;
		break;
	}
}

pcntl_signal(SIGTERM, 'signal_handler');
pcntl_signal(SIGHUP, 'signal_handler');

function spawn_processor(){
	global $log;

	if (@file_exists($log)){
		$fh = file($log);
		if ($fh[count($fh) - 1] == 'Nothing to import...')
			die('finished');
	}

	$pid = pcntl_fork();
	if($pid){
		global $processors;
		$processors[] = $pid;
	}else{
		if(posix_setsid() == -1)
			die("Forked process could not detach from terminal" . PHP_EOL);
		// fclose(STDIN);
		// fclose(STDOUT);
		// fclose(STDERR);

		$path = '"/usr/bin/php ' . PROCESSOR_EXECUTABLE . ' startPopulating"';

		$args = array();
		$args[] = '-c';
		$args[] = '/usr/bin/php ' . PROCESSOR_EXECUTABLE . ' startPopulating> ' . $log . ' 2>&1';

		$path = '/bin/bash';
		sleep(1);
		pcntl_exec($path, $args);
		die('Failed to fork ' . PROCESSOR_EXECUTABLE . PHP_EOL);
	}
}

function spawn_processors(){
	global $processors;
	if($processors)
		kill_processors();
	$processors = array();
	for($ix = 0; $ix < WANT_PROCESSORS; $ix++){
		spawn_processor();
	}
}

function kill_processors(){
	global $processors;
	foreach($processors as $processor)
		posix_kill($processor, SIGTERM);
	foreach($processors as $processor)
		pcntl_waitpid($processor);
	unset($processors);
}

function check_processors(){
	global $processors;
	$valid = array();
	foreach($processors as $processor){
		pcntl_waitpid($processor, $status, WNOHANG);
		if(posix_getsid($processor))
			$valid[] = $processor;
	}
	$processors = $valid;
	if(count($processors) > WANT_PROCESSORS){
		for($ix = count($processors) - 1; $ix >= WANT_PROCESSORS; $ix--)
			posix_kill($processors[$ix], SIGTERM);
		for($ix = count($processors) - 1; $ix >= WANT_PROCESSORS; $ix--)
			pcntl_waitpid($processors[$ix]);
	} elseif(count($processors) < WANT_PROCESSORS){
		for($ix = count($processors); $ix < WANT_PROCESSORS; $ix++)
			spawn_processor();
	}
}

spawn_processors();

while($run){
	$cycles++;
	if($reload){
		$reload = false;
		kill_processors();
		spawn_processors();
	} else {
		check_processors();
	}
	sleep(1);
}
kill_processors();
pcntl_wait();
?>