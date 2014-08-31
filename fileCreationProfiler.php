#!/usr/bin/php
<?php
/**
 * File creation/deletion profiling script v1.02
 * Author: John Wellesz
 * License: public domain
 */

# Usage:
#  ./fileCreationProfiler.php [duration in seconds]
#
#       If duration is ommited or 0, the script will run until user
#       interruption.
#
#       By default 2 files per seconds will be created and deleted, you can
#       change this behaviour by setting the constant FILE_OPS_INTERVAL to a
#       different value (see below).
#
#       Test files are created in the directory where fileCreationProfiler.php
#       is located, you can change this by seting $TEST_FILE_BASE_PATH (see
#       below).


######## CONFIGURATION ###########

# Test file creation/deletion interval in microseconds
const FILE_OPS_INTERVAL = 500000;

# Test files base path
$TEST_FILE_BASE_PATH = __FILE__ . "_test-";

##### END OF CONFIGURATION #######

const MAJOR = 1;
const MINOR = 2;


$WHEEL = '-\\|/';

########
error_reporting(E_ALL | E_STRICT);

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

#### Test and configure signal handling ####
if (function_exists('pcntl_signal') && function_exists('pcntl_signal_dispatch') && PHP_VERSION_ID > 50300) {
    pcntl_signal(SIGINT,
        function () use (&$quit) {
            echo "\nCTRL-C, Quitting...\n";
            echo "\033[2A";
            $quit = 1;
        }
    );
    $SIGNAL_HANDLING = true;
} else {
    $SIGNAL_HANDLING = false;
}

#### Parse command line arguments ####
if ($argc > 1 && is_numeric($argv[1]))
    $TIME_RUN_LIMIT = (int)$argv[1];
else
    $TIME_RUN_LIMIT = false;

set_time_limit(0);
########

printf("File creation/deletion profiling script v%d.%02d\n\n", MAJOR, MINOR);

printf("Test started on %s.\nTest files created in '%s'.\nTest will run for %s.\nHit CTRL-C to stop.\n%s\n"
    , gmdate("Y-m-d H:i's\"")
    , dirname(__FILE__)
    , ($TIME_RUN_LIMIT ? "$TIME_RUN_LIMIT seconds" : "ever")
    , ($SIGNAL_HANDLING ? "" : "No signal handling. You will have to delete the last test file manually and no summary will be available.\n")
);


echo "\n";

$wheelPos   = 0;

$cTotal     = $cMin = $cMed = $cMax = false;
$uTotal     = $uMin = $uMed = $uMax = false;
$cOverSec   = $uOverSec = 0;

$quit       = false;

$worstHours = array();
$startTime  = time();
$lastEcho   = 0;

for ($i = 1 ; ! $quit ; $i++) {

    if ($SIGNAL_HANDLING)
        pcntl_signal_dispatch();

    $time = time();

    if ($TIME_RUN_LIMIT && $time - $startTime > $TIME_RUN_LIMIT)
        $quit = 2;

    #### create a test file ####

    $start = microtime(true);
    touch ($TEST_FILE_BASE_PATH . $i);
    $cElapsed = microtime(true) - $start;

    $cTotal += $cElapsed;

    if (false === $cMin || $cElapsed < $cMin)
        $cMin = $cElapsed;

    if (false === $cMax || $cElapsed > $cMax)
        $cMax = $cElapsed;

    $cMed = $cTotal / $i;

    if ($cElapsed > 1)
        ++$cOverSec;

    #### delete the test file ####

    $start = microtime(true);
    unlink ($TEST_FILE_BASE_PATH . $i);
    $uElapsed = microtime(true) - $start;

    $uTotal += $uElapsed;

    if (false === $uMin || $uElapsed < $uMin)
        $uMin = $uElapsed;

    if (false === $uMax || $uElapsed > $uMax)
        $uMax = $uElapsed;

    $uMed = $uTotal / $i;

    if ($uElapsed > 1)
        ++$uOverSec;

    #### count bad times per hour of the day (GMT) ####

    if ($cElapsed + $uElapsed > 1) {
        if (! isset($worstHours[(($time / 3600) % 24) . 'h']))
            $worstHours[(($time / 3600) % 24) . 'h']  = 1;
        else
            $worstHours[(($time / 3600) % 24) . 'h'] += 1;
    }

    #### Print out stats every seconds ####

    if ($time - $lastEcho > 1 || $quit) {

        $timeSpan = $time - $startTime;

        if ($lastEcho)
            echo "\033[4A";

        printf( "%s Created and deleted %d files at %.1f files/s (in: %dd-%02dh:%02d'%02d\"): \n"
            , $WHEEL{$wheelPos++ % 4}, $i, ($timeSpan > 0 ? $i / $timeSpan : 0)
            , $timeSpan / 86400, ($timeSpan % 86400) / 3600, ($timeSpan % 3600) / 60, $timeSpan % 60);

        printf( " Creation: total: %0.3fs min: %.03fs med: %.03fs max: %.03fs (#>1s:%d) \n"
            , $cTotal, $cMin, $cMed, $cMax, $cOverSec);

        printf( " Deletion: total: %0.3fs min: %.03fs med: %.03fs max: %.03fs (#>1s:%d) \n\n"
            , $uTotal, $uMin, $uMed, $uMax, $uOverSec);

        $lastEcho = $time;
    }

    usleep(FILE_OPS_INTERVAL);
}

echo "\n";

if ($quit == 1)
    echo "Interrupted by user.\n";
else
    printf("Time limit of %d seconds reached.\n", $TIME_RUN_LIMIT);

if (count($worstHours)) {
    echo "Slow (> 1s) operations count per hour of the day (GMT):\n";
    print_r($worstHours);
} else {
    echo "No file creation + deletion took more than a second.\n";
}

echo "\n";

exit(0);


