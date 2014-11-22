fileCreationProfiler
====================

A tool to diagnose file creation and deletion delays.

Usage:
`[php] ./fileCreationProfiler.php [duration] [interval] [slow limit]`

*(`php` can be omitted if `fileCreationProfiler.php` has its executable bit set
and you have PHP installed in /usr/bin)*

Where:
- `duration` is the time in second the test will run.

  If it's omitted or 0, the script will run until user
  interruption (CTRL-C).

- `interval` is the delay between each file creation (and deletion) in **micro-second**.

  If it's omitted or 0, 500000 will be used (2 files/seconds)


- `slow limit` is the maximum time a creation or deletion should be taking in **micro-second**.

  If it's omitted or 0, a value equal to twice the `interval` will be used


When `duration` is reached or if the test is interrupted and slow operations
occurred a summary will be displayed with the count of slow operations per hour
of the day (in GMT).

Example output:

```
(19:07) [john@host] tmp $ ~/work/fileCreationProfiler.php 5 1000 1
File creation/deletion profiling script v1.03

Test started on 2014-11-22 18:08'15" GMT.
Test files created/deleted in '/tmp' every 1,000us.
Test will run for 5 seconds.
Hit CTRL-C to stop.


/ Created and deleted 4449 files at 741.5 files/s (in: 0d-00h:00'06"):
 Creation: total: 0.217s min: 0.000s med: 0.000s max: 0.004s (#slow:2)
 Deletion: total: 0.116s min: 0.000s med: 0.000s max: 0.001s (#slow:1)


Time limit of 5 seconds reached.
Slow (> 1,000 micro-second) operations count per hour of the day (GMT):
Array
(
    [18h] => 3
)
```
