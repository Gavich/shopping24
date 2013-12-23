<?php
ini_set('display_errors', 1);
#error_reporting(E_ALL | E_STRICT);

xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);

for ($i = 0; $i <= 1000; $i++) {
    $a = $i * $i;
}

$xhprof_data = xhprof_disable();

$XHPROF_ROOT = "/usr/share/php";
include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";

$xhprof_runs = new XHProfRuns_Default();
$run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_testing");

echo '<a href="http://' . $_SERVER['HTTP_HOST'] . "/xhprof/xhprof_html/index.php?run={$run_id}&source=xhprof_testing" . '">report</a>';
