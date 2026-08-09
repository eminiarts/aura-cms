<?php

namespace Aura\Base\GlobalSearch;

use Throwable;

final class FreshProcessGlobalSearchSupervisor
{
    private const MAXIMUM_PROTOCOL_BYTES = 512;

    private const REQUIRED_FUNCTIONS = [
        'fclose',
        'feof',
        'fread',
        'fwrite',
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
        'random_bytes',
        'stream_set_blocking',
        'stream_socket_pair',
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

        $parentProcessId = (int) $parentProcessId;
        $deadlineNanoseconds = (int) $deadlineNanoseconds;
        $supervisorProcessId = getmypid();

        if (! is_int($supervisorProcessId) || $supervisorProcessId < 2) {
            return 126;
        }

        $terminationSignals = [SIGTERM, SIGINT, SIGHUP];
        $previousSignalMask = [];

        if (! pcntl_sigprocmask(SIG_BLOCK, $terminationSignals, $previousSignalMask)) {
            return 126;
        }

        try {
            $publicationToken = bin2hex(random_bytes(16));
        } catch (Throwable) {
            self::restoreSignalMask($previousSignalMask);

            return 126;
        }

        $supervisorWatcherChannel = self::channelPair();
        $watcherWorkerChannel = self::channelPair();

        if ($supervisorWatcherChannel === null || $watcherWorkerChannel === null) {
            self::closeChannelPair($supervisorWatcherChannel);
            self::closeChannelPair($watcherWorkerChannel);
            self::restoreSignalMask($previousSignalMask);

            return 126;
        }

        pcntl_async_signals(true);
        $terminationSignal = null;
        $watcherProcessId = 0;
        $workerProcessId = 0;
        $captureSignal = function (int $signal) use (
            &$terminationSignal,
            &$watcherProcessId,
            &$workerProcessId,
        ): void {
            $terminationSignal = $signal;

            if ($watcherProcessId > 1) {
                @posix_kill($watcherProcessId, $signal);
            }

            if ($workerProcessId > 1) {
                @posix_kill($workerProcessId, $signal);
            }
        };

        if (! self::installSignalHandlers($terminationSignals, $captureSignal)) {
            self::closeChannelPair($supervisorWatcherChannel);
            self::closeChannelPair($watcherWorkerChannel);
            self::restoreSignalMask($previousSignalMask);

            return 126;
        }

        $watcherProcessId = pcntl_fork();

        if ($watcherProcessId === -1) {
            self::closeChannelPair($supervisorWatcherChannel);
            self::closeChannelPair($watcherWorkerChannel);
            self::restoreSignalMask($previousSignalMask);

            return 126;
        }

        if ($watcherProcessId === 0) {
            self::closeChannel($supervisorWatcherChannel[0]);
            self::closeChannel($watcherWorkerChannel[0]);

            return self::watch(
                $parentProcessId,
                $supervisorProcessId,
                $deadlineNanoseconds,
                $publicationToken,
                $supervisorWatcherChannel[1],
                $watcherWorkerChannel[1],
                $previousSignalMask,
            );
        }

        self::closeChannel($supervisorWatcherChannel[1]);
        self::closeChannel($watcherWorkerChannel[1]);
        $workerProcessId = pcntl_fork();

        if ($workerProcessId === -1) {
            self::closeChannel($supervisorWatcherChannel[0]);
            self::closeChannel($watcherWorkerChannel[0]);
            self::killAndReap($watcherProcessId);
            self::restoreSignalMask($previousSignalMask);

            return 126;
        }

        if ($workerProcessId === 0) {
            self::closeChannel($supervisorWatcherChannel[0]);

            return self::startWorker(
                $parentProcessId,
                $supervisorProcessId,
                $deadlineNanoseconds,
                $publicationToken,
                $watcherWorkerChannel[0],
                $phpBinary,
                $artisanPath,
                $disabledFunctions,
                $previousSignalMask,
            );
        }

        self::closeChannel($watcherWorkerChannel[0]);
        $publication = self::readMessage($supervisorWatcherChannel[0], $deadlineNanoseconds);
        $publicationIsValid = self::messageHasKeys($publication, [
            'type',
            'token',
            'watcher_pid',
            'worker_pid',
        ])
            && $publication['type'] === 'published'
            && is_string($publication['token'])
            && hash_equals($publicationToken, $publication['token'])
            && $publication['watcher_pid'] === $watcherProcessId
            && $publication['worker_pid'] === $workerProcessId
            && @posix_kill($workerProcessId, 0);

        if (! $publicationIsValid
            || ! self::writeMessage($supervisorWatcherChannel[0], [
                'type' => 'ack',
                'token' => $publicationToken,
                'worker_pid' => $workerProcessId,
            ], $deadlineNanoseconds)) {
            self::closeChannel($supervisorWatcherChannel[0]);
            self::killAndReap($workerProcessId);
            self::killAndReap($watcherProcessId);
            self::restoreSignalMask($previousSignalMask);

            return hrtime(true) >= $deadlineNanoseconds ? 124 : 126;
        }

        self::closeChannel($supervisorWatcherChannel[0]);

        if (! self::restoreSignalMask($previousSignalMask)) {
            self::killAndReap($workerProcessId);
            self::killAndReap($watcherProcessId);

            return 126;
        }

        return self::supervise(
            $parentProcessId,
            $deadlineNanoseconds,
            $watcherProcessId,
            $workerProcessId,
            $terminationSignal,
        );
    }

    /** @return array{0: resource, 1: resource}|null */
    private static function channelPair(): ?array
    {
        $channels = @stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        if ($channels === false
            || ! @stream_set_blocking($channels[0], false)
            || ! @stream_set_blocking($channels[1], false)) {
            self::closeChannelPair($channels === false ? null : $channels);

            return null;
        }

        return $channels;
    }

    private static function closeChannel(mixed $channel): void
    {
        if (is_resource($channel)) {
            @fclose($channel);
        }
    }

    /** @param array<int, mixed>|null $channels */
    private static function closeChannelPair(?array $channels): void
    {
        if ($channels === null) {
            return;
        }

        foreach ($channels as $channel) {
            self::closeChannel($channel);
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

    private static function messageHasKeys(?array $message, array $keys): bool
    {
        return is_array($message) && array_keys($message) === $keys;
    }

    /** @return array<string, mixed>|null */
    private static function readMessage(mixed $channel, int $deadlineNanoseconds): ?array
    {
        $buffer = '';

        while (hrtime(true) < $deadlineNanoseconds) {
            $chunk = @fread($channel, self::MAXIMUM_PROTOCOL_BYTES);

            if ($chunk === false) {
                return null;
            }

            if ($chunk !== '') {
                $buffer .= $chunk;

                if (strlen($buffer) > self::MAXIMUM_PROTOCOL_BYTES) {
                    return null;
                }

                $newlinePosition = strpos($buffer, "\n");

                if ($newlinePosition !== false) {
                    if ($newlinePosition !== strlen($buffer) - 1) {
                        return null;
                    }

                    try {
                        $message = json_decode(
                            substr($buffer, 0, $newlinePosition),
                            true,
                            8,
                            JSON_THROW_ON_ERROR,
                        );
                    } catch (Throwable) {
                        return null;
                    }

                    return is_array($message) ? $message : null;
                }
            }

            if (@feof($channel)) {
                return null;
            }

            usleep(1_000);
        }

        return null;
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
            && defined('WNOHANG')
            && defined('STREAM_PF_UNIX')
            && defined('STREAM_SOCK_STREAM')
            && defined('STREAM_IPPROTO_IP');
    }

    private static function startWorker(
        int $parentProcessId,
        int $supervisorProcessId,
        int $deadlineNanoseconds,
        string $publicationToken,
        mixed $watcherChannel,
        string $phpBinary,
        string $artisanPath,
        string $disabledFunctions,
        array $previousSignalMask,
    ): int {
        $workerProcessId = getmypid();

        if (! is_int($workerProcessId)
            || $workerProcessId < 2
            || posix_getppid() !== $supervisorProcessId
            || ! @posix_kill($parentProcessId, 0)
            || ! self::writeMessage($watcherChannel, [
                'type' => 'worker',
                'token' => $publicationToken,
                'supervisor_pid' => $supervisorProcessId,
                'worker_pid' => $workerProcessId,
            ], $deadlineNanoseconds)) {
            self::closeChannel($watcherChannel);

            return 126;
        }

        $permission = self::readMessage($watcherChannel, $deadlineNanoseconds);
        $permissionIsValid = self::messageHasKeys($permission, ['type', 'token', 'worker_pid'])
            && $permission['type'] === 'go'
            && is_string($permission['token'])
            && hash_equals($publicationToken, $permission['token'])
            && $permission['worker_pid'] === $workerProcessId;
        self::closeChannel($watcherChannel);

        if (! $permissionIsValid
            || posix_getppid() !== $supervisorProcessId
            || ! @posix_kill($parentProcessId, 0)) {
            return hrtime(true) >= $deadlineNanoseconds ? 124 : 126;
        }

        $terminationSignals = [SIGTERM, SIGINT, SIGHUP];

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

    private static function supervise(
        int $parentProcessId,
        int $deadlineNanoseconds,
        int $watcherProcessId,
        int $workerProcessId,
        ?int &$terminationSignal,
    ): int {
        while (true) {
            $workerStatus = 0;
            $waitedWorkerProcessId = pcntl_waitpid($workerProcessId, $workerStatus, WNOHANG);

            if ($waitedWorkerProcessId === $workerProcessId) {
                self::killAndReap($watcherProcessId);

                if (hrtime(true) >= $deadlineNanoseconds) {
                    return 124;
                }

                $requestParentDied = posix_getppid() !== $parentProcessId
                    || ! @posix_kill($parentProcessId, 0);

                if (is_int($terminationSignal) || $requestParentDied) {
                    return is_int($terminationSignal) ? 128 + $terminationSignal : 125;
                }

                return self::exitCode($workerStatus);
            }

            if ($waitedWorkerProcessId === -1 && pcntl_get_last_error() !== PCNTL_EINTR) {
                self::killAndReap($watcherProcessId);

                return 126;
            }

            $watcherStatus = 0;
            $waitedWatcherProcessId = pcntl_waitpid($watcherProcessId, $watcherStatus, WNOHANG);

            if ($waitedWatcherProcessId === $watcherProcessId) {
                self::killAndReap($workerProcessId);

                return self::exitCode($watcherStatus);
            }

            if ($waitedWatcherProcessId === -1 && pcntl_get_last_error() !== PCNTL_EINTR) {
                self::killAndReap($workerProcessId);

                return 126;
            }

            $requestParentDied = posix_getppid() !== $parentProcessId
                || ! @posix_kill($parentProcessId, 0);
            $deadlineExpired = hrtime(true) >= $deadlineNanoseconds;

            if (is_int($terminationSignal) || $requestParentDied || $deadlineExpired) {
                self::killAndReap($workerProcessId);
                self::killAndReap($watcherProcessId);

                if ($deadlineExpired) {
                    return 124;
                }

                return is_int($terminationSignal) ? 128 + $terminationSignal : 125;
            }

            usleep(10_000);
        }
    }

    private static function watch(
        int $parentProcessId,
        int $supervisorProcessId,
        int $deadlineNanoseconds,
        string $publicationToken,
        mixed $supervisorChannel,
        mixed $workerChannel,
        array $previousSignalMask,
    ): int {
        $terminationSignal = null;
        $terminationSignals = [SIGTERM, SIGINT, SIGHUP];
        $workerProcessId = 0;
        $captureSignal = function (int $signal) use (&$terminationSignal, &$workerProcessId): void {
            $terminationSignal = $signal;

            if ($workerProcessId > 1) {
                @posix_kill($workerProcessId, SIGKILL);
            }
        };

        if (! self::installSignalHandlers($terminationSignals, $captureSignal)
            || posix_getppid() !== $supervisorProcessId
            || ! @posix_kill($parentProcessId, 0)) {
            self::closeChannel($supervisorChannel);
            self::closeChannel($workerChannel);

            return 126;
        }

        $publication = self::readMessage($workerChannel, $deadlineNanoseconds);
        $watcherProcessId = getmypid();
        $publicationIsValid = is_int($watcherProcessId)
            && self::messageHasKeys($publication, [
                'type',
                'token',
                'supervisor_pid',
                'worker_pid',
            ])
            && $publication['type'] === 'worker'
            && is_string($publication['token'])
            && hash_equals($publicationToken, $publication['token'])
            && $publication['supervisor_pid'] === $supervisorProcessId
            && is_int($publication['worker_pid'])
            && $publication['worker_pid'] > 1
            && $publication['worker_pid'] !== $watcherProcessId
            && $publication['worker_pid'] !== $supervisorProcessId
            && $publication['worker_pid'] !== $parentProcessId
            && @posix_kill($publication['worker_pid'], 0);

        if (! $publicationIsValid) {
            self::closeChannel($supervisorChannel);
            self::closeChannel($workerChannel);

            return hrtime(true) >= $deadlineNanoseconds ? 124 : 126;
        }

        $workerProcessId = $publication['worker_pid'];

        if (! self::writeMessage($supervisorChannel, [
            'type' => 'published',
            'token' => $publicationToken,
            'watcher_pid' => $watcherProcessId,
            'worker_pid' => $workerProcessId,
        ], $deadlineNanoseconds)) {
            @posix_kill($workerProcessId, SIGKILL);
            self::closeChannel($supervisorChannel);
            self::closeChannel($workerChannel);

            return 126;
        }

        $acknowledgement = self::readMessage($supervisorChannel, $deadlineNanoseconds);
        $acknowledgementIsValid = self::messageHasKeys($acknowledgement, ['type', 'token', 'worker_pid'])
            && $acknowledgement['type'] === 'ack'
            && is_string($acknowledgement['token'])
            && hash_equals($publicationToken, $acknowledgement['token'])
            && $acknowledgement['worker_pid'] === $workerProcessId;

        if (! $acknowledgementIsValid
            || posix_getppid() !== $supervisorProcessId
            || ! @posix_kill($parentProcessId, 0)
            || ! self::writeMessage($workerChannel, [
                'type' => 'go',
                'token' => $publicationToken,
                'worker_pid' => $workerProcessId,
            ], $deadlineNanoseconds)) {
            @posix_kill($workerProcessId, SIGKILL);
            self::closeChannel($supervisorChannel);
            self::closeChannel($workerChannel);

            return hrtime(true) >= $deadlineNanoseconds ? 124 : 126;
        }

        self::closeChannel($supervisorChannel);
        self::closeChannel($workerChannel);

        if (! self::restoreSignalMask($previousSignalMask)) {
            @posix_kill($workerProcessId, SIGKILL);

            return 126;
        }

        while (true) {
            $supervisorDied = posix_getppid() !== $supervisorProcessId;
            $parentDied = ! @posix_kill($parentProcessId, 0);
            $workerDied = ! @posix_kill($workerProcessId, 0);
            $deadlineExpired = hrtime(true) >= $deadlineNanoseconds;

            if ($workerDied) {
                return 0;
            }

            if (is_int($terminationSignal) || $supervisorDied || $parentDied || $deadlineExpired) {
                @posix_kill($workerProcessId, SIGKILL);

                if ($deadlineExpired) {
                    return 124;
                }

                return is_int($terminationSignal) ? 128 + $terminationSignal : 125;
            }

            usleep(10_000);
        }
    }

    /** @param array<string, int|string> $message */
    private static function writeMessage(mixed $channel, array $message, int $deadlineNanoseconds): bool
    {
        try {
            $payload = json_encode($message, JSON_THROW_ON_ERROR)."\n";
        } catch (Throwable) {
            return false;
        }

        if (strlen($payload) > self::MAXIMUM_PROTOCOL_BYTES) {
            return false;
        }

        $offset = 0;

        while ($offset < strlen($payload) && hrtime(true) < $deadlineNanoseconds) {
            $written = @fwrite($channel, substr($payload, $offset));

            if ($written === false) {
                return false;
            }

            if ($written > 0) {
                $offset += $written;
            } elseif (@feof($channel)) {
                return false;
            } else {
                usleep(1_000);
            }
        }

        return $offset === strlen($payload);
    }
}
