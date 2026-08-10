#!/usr/bin/env php
<?php

$watcherProcessId = posix_getppid();

posix_kill($watcherProcessId, SIGSTOP);
posix_kill(getmypid(), SIGSTOP);
posix_kill($watcherProcessId, SIGTERM);
posix_kill($watcherProcessId, SIGCONT);

usleep(5_000_000);
