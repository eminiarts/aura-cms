<?php

namespace Aura\Base\GlobalSearch;

final class FreshProcessGlobalSearchSupervisor
{
    private const REQUIRED_FUNCTIONS = [
        'pcntl_async_signals',
        'pcntl_exec',
        'pcntl_fork',
        'pcntl_get_last_error',
        'pcntl_signal',
        'pcntl_sigprocmask',
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

        $terminationSignals = [SIGTERM, SIGINT, SIGHUP];
        $previousSignalMask = [];

        if (! pcntl_sigprocmask(SIG_BLOCK, $terminationSignals, $previousSignalMask)) {
            return 126;
        }

        pcntl_async_signals(true);
        $terminationSignal = null;
        $watcherProcessId = 0;
        $captureSignal = function (int $signal) use (&$terminationSignal, &$watcherProcessId): void {
            $terminationSignal = $signal;

            if ($watcherProcessId > 1) {
                @posix_kill($watcherProcessId, $signal);
            }
        };

        if (! self::installSignalHandlers($terminationSignals, $captureSignal)) {
            self::restoreSignalMask($previousSignalMask);

            return 126;
        }

        $watcherProcessId = pcntl_fork();

        if ($watcherProcessId === -1) {
            self::restoreSignalMask($previousSignalMask);

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
                $previousSignalMask,
            );
        }

        if (! self::restoreSignalMask($previousSignalMask)) {
            @posix_kill($watcherProcessId, SIGTERM);
            self::reap($watcherProcessId);

            return 126;
        }

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

    /**
     * @param  array<int, int>  $signals
     * @param  (callable(int): void)|int  $handler
     */
    private static function installSignalHandlers(array $signals, callable|int $handler): bool
    {
        foreach ($signals as $signal) {
            if (! pcntl_signal($signal, $handler)) {
                return false;
            }
        }

        return true;
    }

    private static function killAndReap(int $processId): void
    {
        @posix_kill($processId, SIGKILL);
        self::reap($processId);
    }

    private static function reap(int $processId): void
    {
        while (true) {
            $status = 0;
            $waitedProcessId = pcntl_waitpid($processId, $status);

            if ($waitedProcessId === $processId
                || ($waitedProcessId === -1 && pcntl_get_last_error() !== PCNTL_EINTR)) {
                return;
            }
        }
    }

    /** @param array<int, int> $signalMask */
    private static function restoreSignalMask(array $signalMask): bool
    {
        return pcntl_sigprocmask(SIG_SETMASK, $signalMask);
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
            && defined('SIG_BLOCK')
            && defined('SIG_SETMASK')
            && defined('PCNTL_EINTR')
            && defined('WNOHANG');
    }

    private static function watch(
        int $parentProcessId,
        int $supervisorProcessId,
        int $deadlineNanoseconds,
        string $phpBinary,
        string $artisanPath,
        string $disabledFunctions,
        array $previousSignalMask,
    ): int {
        $terminationSignal = null;
        $terminationSignals = [SIGTERM, SIGINT, SIGHUP];
        $captureSignal = function (int $signal) use (&$terminationSignal): void {
            $terminationSignal = $signal;
        };

        if (! self::installSignalHandlers($terminationSignals, $captureSignal)) {
            return 126;
        }

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
            if (! self::installSignalHandlers($terminationSignals, SIG_DFL)
                || ! self::restoreSignalMask($previousSignalMask)) {
                fwrite(STDERR, "Unable to prepare the global search worker process.\n");

                return 127;
            }

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

        if (! self::restoreSignalMask($previousSignalMask)) {
            self::killAndReap($workerProcessId);

            return 126;
        }

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
                self::killAndReap($workerProcessId);

                if ($deadlineExpired) {
                    return 124;
                }

                return is_int($terminationSignal) ? 128 + $terminationSignal : 125;
            }

            usleep(10_000);
        }
    }
}
