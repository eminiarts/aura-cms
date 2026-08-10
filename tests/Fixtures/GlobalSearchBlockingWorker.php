<?php

$markerPath = getenv('AURA_GLOBAL_SEARCH_WORKER_PID_MARKER');

if (is_string($markerPath) && $markerPath !== '') {
    file_put_contents($markerPath, json_encode([
        'worker_pid' => getmypid(),
        'parent_pid' => posix_getppid(),
    ], JSON_THROW_ON_ERROR));
}

while (true) {
    usleep(100_000);
}
