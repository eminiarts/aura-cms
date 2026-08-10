<?php

use Illuminate\Foundation\Application as LaravelApplication;
use Symfony\Component\Console\Input\ArgvInput;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require __DIR__.'/GlobalSearchWorkerBootstrap.php';

/** @var LaravelApplication $app */
$status = $app->handleCommand(new ArgvInput);

exit($status);
