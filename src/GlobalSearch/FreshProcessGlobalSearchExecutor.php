<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Commands\RunGlobalSearchWorker;
use Aura\Base\Exceptions\GlobalSearchExecutionFailed;
use Aura\Base\Exceptions\GlobalSearchExecutionTimedOut;
use Aura\Base\Exceptions\GlobalSearchExecutionUnavailable;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Throwable;

final class FreshProcessGlobalSearchExecutor
{
    private const CLOSE_INHERITED_DESCRIPTORS_SCRIPT = <<<'SH'
for descriptor_path in /proc/self/fd/* /dev/fd/*; do
    descriptor=${descriptor_path##*/}

    case "$descriptor" in
        0|1|2|''|*[!0-9]*) continue ;;
    esac

    eval "exec ${descriptor}>&-"
done

exec "$@"
SH;

    private const FORBIDDEN_WORKER_FUNCTIONS = [
        'dl',
        'exec',
        'mail',
        'mb_send_mail',
        'passthru',
        'pcntl_exec',
        'pcntl_fork',
        'popen',
        'posix_kill',
        'posix_setpgid',
        'posix_setsid',
        'proc_open',
        'shell_exec',
        'system',
    ];

    private static ?bool $asynchronousSignalsWereEnabled = null;

    /** @var array<int, Process> */
    private static array $runningProcesses = [];

    private static bool $shutdownHandlerRegistered = false;

    /** @var array<int, callable|int> */
    private static array $signalHandlers = [];

    /**
     * @param  array<string, string|false>  $environment
     */
    public function __construct(
        private readonly ?string $artisanPath = null,
        private readonly array $environment = [],
        private readonly ?string $workingDirectory = null,
        private readonly ?string $phpBinary = null,
    ) {}

    public function isAvailable(): bool
    {
        return config('aura.global_search.execution_backend', 'process') === 'process'
            && function_exists('proc_open')
            && is_string($this->resolvedShellPath())
            && is_string($this->resolvedPhpBinary())
            && is_string($this->resolvedArtisanPath());
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    public function run(array $request, int $timeoutMilliseconds, int $maximumPayloadBytes): array
    {
        $artisanPath = $this->resolvedArtisanPath();
        $phpBinary = $this->resolvedPhpBinary();
        $shellPath = $this->resolvedShellPath();

        if (config('aura.global_search.execution_backend', 'process') !== 'process'
            || ! function_exists('proc_open')
            || $shellPath === null
            || $phpBinary === null
            || $artisanPath === null) {
            throw new GlobalSearchExecutionUnavailable('Fresh global search execution is unavailable.');
        }

        try {
            $input = json_encode($request, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new GlobalSearchExecutionFailed(
                'The global search worker request could not be encoded.',
                previous: $exception,
            );
        }

        if (strlen($input) > 65_536) {
            throw new GlobalSearchExecutionFailed('The global search worker request exceeded its payload budget.');
        }

        $process = new Process(
            $this->command($shellPath, $phpBinary, $artisanPath),
            $this->resolvedWorkingDirectory(),
            $this->environment === [] ? null : $this->environment,
            $input,
            max(0.001, $timeoutMilliseconds / 1_000),
        );
        $outputBytes = 0;
        $payloadLimitExceeded = false;
        $processId = spl_object_id($process);

        self::registerProcess($processId, $process);

        try {
            $exitCode = $process->run(function (string $type, string $buffer) use (
                &$outputBytes,
                &$payloadLimitExceeded,
                $maximumPayloadBytes,
                $process,
            ): void {
                $outputBytes += strlen($buffer);

                if ($outputBytes > $maximumPayloadBytes) {
                    $payloadLimitExceeded = true;
                    self::terminate($process);
                }
            });
        } catch (ProcessTimedOutException $exception) {
            self::terminate($process);

            throw new GlobalSearchExecutionTimedOut(
                'Fresh global search execution exceeded its deadline.',
                previous: $exception,
            );
        } catch (Throwable $exception) {
            self::terminate($process);

            throw new GlobalSearchExecutionFailed(
                'Fresh global search execution failed.',
                previous: $exception,
            );
        } finally {
            unset(self::$runningProcesses[$processId]);
            self::restoreSignalHandlersWhenIdle();
        }

        if ($payloadLimitExceeded) {
            throw new GlobalSearchExecutionFailed('The global search worker exceeded its payload budget.');
        }

        if ($exitCode !== 0) {
            throw new GlobalSearchExecutionFailed('The global search worker exited unsuccessfully.');
        }

        $markerPosition = strrpos($process->getOutput(), RunGlobalSearchWorker::RESPONSE_MARKER);

        if ($markerPosition === false) {
            throw new GlobalSearchExecutionFailed('The global search worker returned no response envelope.');
        }

        $encodedEnvelope = trim(substr(
            $process->getOutput(),
            $markerPosition + strlen(RunGlobalSearchWorker::RESPONSE_MARKER),
        ));

        try {
            $envelope = json_decode($encodedEnvelope, true, 32, JSON_THROW_ON_ERROR);
        } catch (Throwable $exception) {
            throw new GlobalSearchExecutionFailed(
                'The global search worker returned malformed JSON.',
                previous: $exception,
            );
        }

        if (! is_array($envelope)
            || ($envelope['successful'] ?? null) !== true
            || ! is_array($envelope['result'] ?? null)) {
            throw new GlobalSearchExecutionFailed('The global search worker failed closed.');
        }

        return $envelope['result'];
    }

    public static function terminateAll(): void
    {
        foreach (self::$runningProcesses as $process) {
            self::terminate($process);
        }

        self::$runningProcesses = [];
    }

    public static function workerRuntimeIsContained(): bool
    {
        foreach (self::FORBIDDEN_WORKER_FUNCTIONS as $function) {
            if (function_exists($function)) {
                return false;
            }
        }

        return in_array(strtolower(trim((string) ini_get('ffi.enable'))), ['', '0', 'off', 'false'], true);
    }

    /** @return array<int, string> */
    private function command(string $shellPath, string $phpBinary, string $artisanPath): array
    {
        return [
            $shellPath,
            '-c',
            self::CLOSE_INHERITED_DESCRIPTORS_SCRIPT,
            'aura-global-search-worker',
            $phpBinary,
            '-d',
            'ffi.enable=0',
            '-d',
            'disable_functions='.implode(',', self::FORBIDDEN_WORKER_FUNCTIONS),
            $artisanPath,
            'aura:global-search-worker',
            '--no-interaction',
        ];
    }

    private static function handleSignal(int $signal): void
    {
        self::terminateAll();
        $previousHandler = self::$signalHandlers[$signal] ?? SIG_DFL;

        if ($previousHandler === SIG_IGN) {
            return;
        }

        if (is_callable($previousHandler)) {
            $previousHandler($signal);

            return;
        }

        if (function_exists('pcntl_signal') && function_exists('posix_kill')) {
            pcntl_signal($signal, SIG_DFL);
            posix_kill(getmypid(), $signal);
        }
    }

    private static function installSignalHandlers(): void
    {
        if (self::$signalHandlers !== []
            || ! function_exists('pcntl_async_signals')
            || ! function_exists('pcntl_signal')
            || ! function_exists('pcntl_signal_get_handler')) {
            return;
        }

        self::$asynchronousSignalsWereEnabled = pcntl_async_signals();
        pcntl_async_signals(true);

        foreach ([SIGTERM, SIGINT] as $signal) {
            self::$signalHandlers[$signal] = pcntl_signal_get_handler($signal);
            pcntl_signal($signal, self::handleSignal(...));
        }
    }

    private static function registerProcess(int $processId, Process $process): void
    {
        self::$runningProcesses[$processId] = $process;

        if (! self::$shutdownHandlerRegistered) {
            register_shutdown_function(self::terminateAll(...));
            self::$shutdownHandlerRegistered = true;
        }

        self::installSignalHandlers();
    }

    private function resolvedArtisanPath(): ?string
    {
        $configured = $this->artisanPath ?? config('aura.global_search.worker_artisan') ?? base_path('artisan');

        if (! is_string($configured) || $configured === '' || strlen($configured) > 4_096) {
            return null;
        }

        $resolved = realpath($configured);

        return is_string($resolved) && is_file($resolved) && is_readable($resolved)
            ? $resolved
            : null;
    }

    private function resolvedPhpBinary(): ?string
    {
        $configured = $this->phpBinary ?? config('aura.global_search.worker_php');
        $candidate = $configured ?? (new PhpExecutableFinder)->find(false);

        if (! is_string($candidate) || $candidate === '' || strlen($candidate) > 4_096) {
            return null;
        }

        $resolved = realpath($candidate);

        return is_string($resolved) && is_file($resolved) && is_executable($resolved)
            ? $resolved
            : null;
    }

    private function resolvedShellPath(): ?string
    {
        $resolved = realpath('/bin/sh');

        return is_string($resolved) && is_file($resolved) && is_executable($resolved)
            ? $resolved
            : null;
    }

    private function resolvedWorkingDirectory(): string
    {
        $configured = $this->workingDirectory ?? base_path();
        $resolved = realpath($configured);

        return is_string($resolved) && is_dir($resolved) ? $resolved : base_path();
    }

    private static function restoreSignalHandlersWhenIdle(): void
    {
        if (self::$runningProcesses !== [] || self::$signalHandlers === []) {
            return;
        }

        foreach (self::$signalHandlers as $signal => $handler) {
            pcntl_signal($signal, $handler);
        }

        self::$signalHandlers = [];

        if (self::$asynchronousSignalsWereEnabled === false) {
            pcntl_async_signals(false);
        }

        self::$asynchronousSignalsWereEnabled = null;
    }

    private static function terminate(Process $process): void
    {
        if ($process->isRunning()) {
            $process->stop(0, defined('SIGKILL') ? SIGKILL : 9);
        }
    }
}
