<?php

namespace Aura\Base\GlobalSearch;

final class FreshProcessGlobalSearchSupervisor
{
    private const REQUIRED_FUNCTIONS = [
        'pcntl_async_signals',
        'pcntl_exec',
        'pcntl_fork',
        'pcntl_signal',
        'pcntl_waitpid',
        'pcntl_wexitstatus',
        'pcntl_wifexited',
        'pcntl_wifsignaled',
        'pcntl_wtermsig',
        'posix_getppid',
        'posix_kill',
    ];

    /** @param array<int, string> $arguments */
    public static function run(array $arguments): int
    {
        if (! self::runtimeIsSupported() || count($arguments) !== 5) {
            return 126;
        }

        [$parentProcessId, $deadlineNanoseconds, $phpBinary, $artisanPath, $disabledFunctions] = $arguments;

        if (! ctype_digit($parentProcessId)
            || ! ctype_digit($deadlineNanoseconds)
            || (int) $parentProcessId < 2
            || (int) $deadlineNanoseconds < 1
            || $phpBinary === ''
            || $artisanPath === ''
            || $disabledFunctions === '') {
            return 126;
        }

        $supervisorProcessId = getmypid();

        if (! is_int($supervisorProcessId) || $supervisorProcessId < 2) {
            return 126;
        }

        $watcherProcessId = pcntl_fork();

        if ($watcherProcessId === -1) {
            return 126;
        }

        if ($watcherProcessId === 0) {
            return self::watch(
                (int) $parentProcessId,
                $supervisorProcessId,
                (int) $deadlineNanoseconds,
                $phpBinary,
                $artisanPath,
                $disabledFunctions,
            );
        }

        pcntl_async_signals(true);
        $terminationSignal = null;
        $captureSignal = function (int $signal) use (&$terminationSignal, $watcherProcessId): void {
            $terminationSignal = $signal;
            @posix_kill($watcherProcessId, $signal);
        };
        pcntl_signal(SIGTERM, $captureSignal);
        pcntl_signal(SIGINT, $captureSignal);
        pcntl_signal(SIGHUP, $captureSignal);

        while (true) {
            $status = 0;
            $waitedProcessId = pcntl_waitpid($watcherProcessId, $status, WNOHANG);

            if ($waitedProcessId === $watcherProcessId) {
                return self::exitCode($status);
            }

            if ($waitedProcessId === -1) {
                return 126;
            }

            $requestParentDied = posix_getppid() !== (int) $parentProcessId
                || ! @posix_kill((int) $parentProcessId, 0);

            if (is_int($terminationSignal) || $requestParentDied) {
                @posix_kill($watcherProcessId, SIGTERM);
            }

            usleep(10_000);
        }
    }

    private static function exitCode(int $status): int
    {
        if (pcntl_wifexited($status)) {
            return pcntl_wexitstatus($status);
        }

        if (pcntl_wifsignaled($status)) {
            return 128 + pcntl_wtermsig($status);
        }

        return 126;
    }

    private static function runtimeIsSupported(): bool
    {
        foreach (self::REQUIRED_FUNCTIONS as $function) {
            if (! function_exists($function)) {
                return false;
            }
        }

        return defined('SIGTERM')
            && defined('SIGINT')
            && defined('SIGHUP')
            && defined('SIGKILL')
            && defined('WNOHANG');
    }

    private static function watch(
        int $parentProcessId,
        int $supervisorProcessId,
        int $deadlineNanoseconds,
        string $phpBinary,
        string $artisanPath,
        string $disabledFunctions,
    ): int {
        $terminationSignal = null;

        if (posix_getppid() !== $supervisorProcessId
            || ! @posix_kill($parentProcessId, 0)
            || hrtime(true) >= $deadlineNanoseconds) {
            return 125;
        }

        $workerProcessId = pcntl_fork();

        if ($workerProcessId === -1) {
            return 126;
        }

        if ($workerProcessId === 0) {
            pcntl_exec($phpBinary, [
                '-d',
                'ffi.enable=0',
                '-d',
                'disable_functions='.$disabledFunctions,
                $artisanPath,
                'aura:global-search-worker',
                '--no-interaction',
            ]);

            fwrite(STDERR, "Unable to execute the global search worker.\n");

            return 127;
        }

        pcntl_async_signals(true);
        $captureSignal = function (int $signal) use (&$terminationSignal): void {
            $terminationSignal = $signal;
        };
        pcntl_signal(SIGTERM, $captureSignal);
        pcntl_signal(SIGINT, $captureSignal);
        pcntl_signal(SIGHUP, $captureSignal);

        while (true) {
            $status = 0;
            $waitedProcessId = pcntl_waitpid($workerProcessId, $status, WNOHANG);

            if ($waitedProcessId === $workerProcessId) {
                return self::exitCode($status);
            }

            if ($waitedProcessId === -1) {
                return 126;
            }

            $supervisorDied = posix_getppid() !== $supervisorProcessId;
            $parentDied = ! @posix_kill($parentProcessId, 0);
            $deadlineExpired = hrtime(true) >= $deadlineNanoseconds;

            if (is_int($terminationSignal) || $supervisorDied || $parentDied || $deadlineExpired) {
                @posix_kill($workerProcessId, SIGKILL);
                pcntl_waitpid($workerProcessId, $status);

                if ($deadlineExpired) {
                    return 124;
                }

                return is_int($terminationSignal) ? 128 + $terminationSignal : 125;
            }

            usleep(10_000);
        }
    }
}
