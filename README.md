fileCreationProfiler
====================

A tool to diagnose file creation and deletion delays

Usage:
`./fileCreationProfiler.php [duration in seconds]`

If duration is ommited or 0, the script will run until user
interruption (CTRL-C).

By default 2 files per seconds will be created and deleted, you can
change this behaviour by setting the constant FILE_OPS_INTERVAL to a
different value.
