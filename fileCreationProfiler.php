#!/usr/bin/php
<?php
/*
    File creation/deletion profiling script v1.03

    Copyright © 2014 by John Wellesz

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

 */

ini_set("display_startup_errors", true);
error_reporting(E_ALL | E_STRICT);
set_time_limit(0);

const MAJOR = 1;
const MINOR = 3;

CONST USEC  = 1000000;

# Test files base path
$TEST_FILE_BASE_PATH = sprintf("%s%s%s_test-",
    getcwd() ? getcwd() : "", DIRECTORY_SEPARATOR, basename(__FILE__));

$WHEEL = '-\\|/';

########

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

if ($argc > 2 && is_numeric($argv[2]) && $argv[2])
    $FILE_OPS_INTERVAL = (int)$argv[2];
else
    $FILE_OPS_INTERVAL = 500000;

if ($argc > 3 && is_numeric($argv[3]) && $argv[3])
    $SLOW_TIME_LIMIT = (int)$argv[2];
else
    $SLOW_TIME_LIMIT = 2 * $FILE_OPS_INTERVAL;

########

printf("File creation/deletion profiling script v%d.%02d\n\n", MAJOR, MINOR);

printf("Test started on %s.\nTest files created/deleted in '%s' every %sus.\nTest will run for %s.\nHit CTRL-C to stop.\n%s\n"
    , gmdate("Y-m-d H:i's\" T")
    , dirname($TEST_FILE_BASE_PATH)
    , number_format($FILE_OPS_INTERVAL)
    , ($TIME_RUN_LIMIT ? "$TIME_RUN_LIMIT seconds" : "ever")
    , ($SIGNAL_HANDLING ? "" : "No signal handling. You will have to delete the last test file manually and no summary will be available.\n")
);


echo "\n";

$wheelPos   = 0;

$cTotal     = $cMin = $cMed = $cMax = false;
$uTotal     = $uMin = $uMed = $uMax = false;
$cSlow      = $uSlow = 0;

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

    if ($cElapsed * USEC > $SLOW_TIME_LIMIT)
        ++$cSlow;

    #### Delete the test file ####

    $start = microtime(true);
    unlink ($TEST_FILE_BASE_PATH . $i);
    $uElapsed = microtime(true) - $start;

    $uTotal += $uElapsed;

    if (false === $uMin || $uElapsed < $uMin)
        $uMin = $uElapsed;

    if (false === $uMax || $uElapsed > $uMax)
        $uMax = $uElapsed;

    $uMed = $uTotal / $i;

    if ($uElapsed * USEC > $SLOW_TIME_LIMIT)
        ++$uSlow;

    #### Count bad times per hour of the day (GMT) ####

    if (($cElapsed + $uElapsed) * USEC > $SLOW_TIME_LIMIT) {
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

        printf( " Creation: total: %0.3fs min: %.03fs med: %.03fs max: %.03fs (#slow:%d) \n"
            , $cTotal, $cMin, $cMed, $cMax, $cSlow);

        printf( " Deletion: total: %0.3fs min: %.03fs med: %.03fs max: %.03fs (#slow:%d) \n\n"
            , $uTotal, $uMin, $uMed, $uMax, $uSlow);

        $lastEcho = $time;
    }

    usleep($FILE_OPS_INTERVAL);
}

echo "\n";

if ($quit == 1)
    echo "Interrupted by user.\n";
else
    printf("Time limit of %d seconds reached.\n", $TIME_RUN_LIMIT);

if (count($worstHours)) {
    printf("Slow (> %s micro-second) operations count per hour of the day (GMT):\n",
        number_format($SLOW_TIME_LIMIT));

    print_r($worstHours);
} else {
    printf("No file creation + deletion took more than %s micro-second.\n,",
        number_format($SLOW_TIME_LIMIT));
}

echo "\n";

exit(0);


