<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Exceptions\GlobalSearchExecutionFailed;
use Aura\Base\Exceptions\GlobalSearchExecutionTimedOut;
use Aura\Base\Exceptions\GlobalSearchExecutionUnavailable;
use Closure;
use Illuminate\Support\Collection;
use Throwable;

final class ForkedGlobalSearchExecutor
{
    /** @var array<int, resource> */
    private static array $childOutputStreams = [];

    private static ?int $isolatedChildProcessId = null;

    public function isAvailable(): bool
    {
        $backend = config('aura.global_search.execution_backend', 'auto');

        if (! in_array($backend, ['auto', 'fork'], true)
            || self::$isolatedChildProcessId === getmypid()
            || $this->isOctaneRuntime()) {
            return false;
        }

        return in_array(PHP_SAPI, ['cli', 'cli-server'], true)
            && function_exists('pcntl_fork')
            && function_exists('pcntl_waitpid')
            && function_exists('posix_kill')
            && function_exists('posix_setpgid')
            && function_exists('stream_socket_pair')
            && defined('STREAM_PF_UNIX')
            && defined('STREAM_SOCK_STREAM')
            && defined('STREAM_IPPROTO_IP')
            && defined('SIGKILL');
    }

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $callback
     * @return TResult
     */
    public function run(Closure $callback, int $timeoutMilliseconds, int $maximumPayloadBytes): mixed
    {
        if (! $this->isAvailable()) {
            throw new GlobalSearchExecutionUnavailable('Global search process isolation is unavailable.');
        }

        $sockets = @stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        if ($sockets === false) {
            throw new GlobalSearchExecutionUnavailable('Global search process isolation could not create a socket pair.');
        }

        $processId = pcntl_fork();

        if ($processId === -1) {
            fclose($sockets[0]);
            fclose($sockets[1]);

            throw new GlobalSearchExecutionUnavailable('Global search process isolation could not fork.');
        }

        if ($processId === 0) {
            fclose($sockets[0]);
            $this->runChild($sockets[1], $callback, $maximumPayloadBytes);
        }

        fclose($sockets[1]);
        @posix_setpgid($processId, $processId);
        stream_set_blocking($sockets[0], false);

        try {
            $payload = $this->readPayload($sockets[0], $timeoutMilliseconds, $maximumPayloadBytes);
        } finally {
            fclose($sockets[0]);
            $this->terminateAndReap($processId);
        }

        try {
            $envelope = @unserialize($payload, [
                'allowed_classes' => [Collection::class, GlobalSearchResult::class],
            ]);
        } catch (Throwable $exception) {
            throw new GlobalSearchExecutionFailed('Global search isolation returned an invalid payload.', previous: $exception);
        }

        if (! is_array($envelope) || ! is_bool($envelope['successful'] ?? null)) {
            throw new GlobalSearchExecutionFailed('Global search isolation returned an invalid envelope.');
        }

        if ($envelope['successful'] !== true) {
            throw new GlobalSearchExecutionFailed('Global search isolated execution failed.');
        }

        return $envelope['result'] ?? null;
    }

    private function isOctaneRuntime(): bool
    {
        $octaneFlag = $_SERVER['LARAVEL_OCTANE'] ?? $_ENV['LARAVEL_OCTANE'] ?? null;

        return filter_var($octaneFlag, FILTER_VALIDATE_BOOL)
            || (function_exists('app') && app()->bound('octane'));
    }

    /** @param  resource  $socket */
    private function readPayload($socket, int $timeoutMilliseconds, int $maximumPayloadBytes): string
    {
        $deadline = hrtime(true) + ($timeoutMilliseconds * 1_000_000);
        $payload = '';

        while (true) {
            $remainingNanoseconds = $deadline - hrtime(true);

            if ($remainingNanoseconds <= 0) {
                throw new GlobalSearchExecutionTimedOut('Global search isolated execution exceeded its deadline.');
            }

            $read = [$socket];
            $write = null;
            $except = null;
            $seconds = intdiv($remainingNanoseconds, 1_000_000_000);
            $microseconds = max(1, intdiv($remainingNanoseconds % 1_000_000_000, 1_000));
            $selected = @stream_select($read, $write, $except, $seconds, $microseconds);

            if ($selected === false) {
                continue;
            }

            if ($selected === 0) {
                throw new GlobalSearchExecutionTimedOut('Global search isolated execution exceeded its deadline.');
            }

            $chunk = fread($socket, 8192);

            if ($chunk === false) {
                throw new GlobalSearchExecutionFailed('Global search isolation could not read its payload.');
            }

            if ($chunk === '') {
                if (feof($socket)) {
                    break;
                }

                continue;
            }

            $payload .= $chunk;

            if (strlen($payload) > $maximumPayloadBytes) {
                throw new GlobalSearchExecutionFailed('Global search isolation exceeded its payload budget.');
            }
        }

        if ($payload === '') {
            throw new GlobalSearchExecutionFailed('Global search isolation returned no payload.');
        }

        return $payload;
    }

    /**
     * @param  resource  $socket
     * @param  Closure(): mixed  $callback
     */
    private function runChild($socket, Closure $callback, int $maximumPayloadBytes): never
    {
        self::$isolatedChildProcessId = getmypid();
        @posix_setpgid(0, 0);
        $this->silenceChildOutput();

        try {
            $serialized = serialize([
                'successful' => true,
                'result' => $callback(),
            ]);

            if (strlen($serialized) > $maximumPayloadBytes) {
                $serialized = serialize([
                    'successful' => false,
                    'reason' => 'payload_budget_exceeded',
                ]);
            }
        } catch (Throwable) {
            $serialized = serialize([
                'successful' => false,
                'reason' => 'execution_failed',
            ]);
        }

        stream_set_blocking($socket, true);
        $written = 0;
        $length = strlen($serialized);

        while ($written < $length) {
            $bytes = @fwrite($socket, substr($serialized, $written));

            if (! is_int($bytes) || $bytes < 1) {
                break;
            }

            $written += $bytes;
        }

        @fflush($socket);
        fclose($socket);
        @posix_kill(getmypid(), SIGKILL);

        exit(255);
    }

    private function silenceChildOutput(): void
    {
        if (defined('STDOUT') && is_resource(STDOUT)) {
            fclose(STDOUT);
            $stream = @fopen('/dev/null', 'wb');

            if (is_resource($stream)) {
                self::$childOutputStreams[] = $stream;
            }
        }

        if (defined('STDERR') && is_resource(STDERR)) {
            fclose(STDERR);
            $stream = @fopen('/dev/null', 'wb');

            if (is_resource($stream)) {
                self::$childOutputStreams[] = $stream;
            }
        }
    }

    private function terminateAndReap(int $processId): void
    {
        $status = 0;
        $waitResult = @pcntl_waitpid($processId, $status, WNOHANG);

        if ($waitResult === 0) {
            @posix_kill(-$processId, SIGKILL);
            @posix_kill($processId, SIGKILL);
            @pcntl_waitpid($processId, $status);

            return;
        }

        @posix_kill(-$processId, SIGKILL);
    }
}
