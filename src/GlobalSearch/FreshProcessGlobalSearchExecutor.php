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
descriptor_directory=$1
shift

if [ ! -d "$descriptor_directory" ] || [ ! -r "$descriptor_directory" ] || [ ! -x "$descriptor_directory" ]; then
    exit 126
fi

descriptor_entries_found=0

for descriptor_path in "$descriptor_directory"/*; do
    descriptor=${descriptor_path##*/}

    case "$descriptor" in
        ''|*[!0-9]*) continue ;;
    esac

    descriptor_entries_found=1

    case "$descriptor" in
        0|1|2) continue ;;
    esac

    eval "exec ${descriptor}>&-" || exit 126
done

[ "$descriptor_entries_found" -eq 1 ] || exit 126

exec "$@"
SH;

    private const DEFAULT_DESCRIPTOR_DIRECTORIES = ['/proc/self/fd', '/dev/fd'];

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

    private const REQUIRED_SUPERVISION_FUNCTIONS = [
        'pcntl_async_signals',
        'pcntl_signal',
        'pcntl_signal_get_handler',
        'posix_kill',
    ];

    private static ?bool $asynchronousSignalsWereEnabled = null;

    /** @var array<int, Process> */
    private static array $runningProcesses = [];

    private static bool $shutdownHandlerRegistered = false;

    /** @var array<int, callable|int> */
    private static array $signalHandlers = [];

    /**
     * @param  array<string, string|false>  $environment
     * @param  array<int, string>|null  $descriptorDirectories
     */
    public function __construct(
        private readonly ?string $artisanPath = null,
        private readonly array $environment = [],
        private readonly ?string $workingDirectory = null,
        private readonly ?string $phpBinary = null,
        private readonly ?array $descriptorDirectories = null,
    ) {}

    public function isAvailable(): bool
    {
        return config('aura.global_search.execution_backend', 'process') === 'process'
            && function_exists('proc_open')
            && $this->parentRuntimeSupportsSupervision()
            && is_string($this->resolvedShellPath())
            && is_string($this->resolvedPhpBinary())
            && is_string($this->resolvedArtisanPath())
            && is_string($this->resolvedSupervisorPath())
            && is_string($this->resolvedDescriptorDirectory());
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
        $supervisorPath = $this->resolvedSupervisorPath();
        $descriptorDirectory = $this->resolvedDescriptorDirectory();

        if (config('aura.global_search.execution_backend', 'process') !== 'process'
            || ! function_exists('proc_open')
            || ! $this->parentRuntimeSupportsSupervision()
            || $shellPath === null
            || $phpBinary === null
            || $artisanPath === null
            || $supervisorPath === null
            || $descriptorDirectory === null) {
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
            $this->command(
                $shellPath,
                $phpBinary,
                $artisanPath,
                $supervisorPath,
                $descriptorDirectory,
                hrtime(true) + ($timeoutMilliseconds * 1_000_000),
            ),
            $this->resolvedWorkingDirectory(),
            $this->environment === [] ? null : $this->environment,
            $input,
            max(0.001, ($timeoutMilliseconds + 250) / 1_000),
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

        if ($exitCode === 124) {
            throw new GlobalSearchExecutionTimedOut('Fresh global search execution exceeded its deadline.');
        }

        if ($exitCode === 126) {
            throw new GlobalSearchExecutionUnavailable('Fresh global search supervision is unavailable.');
        }

        if ($exitCode !== 0) {
            throw new GlobalSearchExecutionFailed("The global search worker exited unsuccessfully ({$exitCode}).");
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
    private function command(
        string $shellPath,
        string $phpBinary,
        string $artisanPath,
        string $supervisorPath,
        string $descriptorDirectory,
        int $deadlineNanoseconds,
    ): array {
        return [
            $shellPath,
            '-c',
            self::CLOSE_INHERITED_DESCRIPTORS_SCRIPT,
            'aura-global-search-worker',
            $descriptorDirectory,
            $phpBinary,
            '-d',
            'ffi.enable=0',
            '-r',
            'require $argv[1]; exit(\Aura\Base\GlobalSearch\FreshProcessGlobalSearchSupervisor::run(array_slice($argv, 2)));',
            '--',
            $supervisorPath,
            (string) getmypid(),
            (string) $deadlineNanoseconds,
            $phpBinary,
            $artisanPath,
            implode(',', self::FORBIDDEN_WORKER_FUNCTIONS),
        ];
    }

    private function descriptorDirectoryIsEnumerable(string $directory): bool
    {
        $entries = @scandir($directory);

        if (! is_array($entries)) {
            return false;
        }

        foreach ($entries as $entry) {
            if (ctype_digit($entry)) {
                return true;
            }
        }

        return false;
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

    private function parentRuntimeSupportsSupervision(): bool
    {
        foreach (self::REQUIRED_SUPERVISION_FUNCTIONS as $function) {
            if (! function_exists($function)) {
                return false;
            }
        }

        return defined('SIGTERM') && defined('SIGINT') && defined('SIGKILL');
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

    private function resolvedDescriptorDirectory(): ?string
    {
        $directories = $this->descriptorDirectories ?? self::DEFAULT_DESCRIPTOR_DIRECTORIES;

        if (! array_is_list($directories) || $directories === [] || count($directories) > 4) {
            return null;
        }

        foreach ($directories as $directory) {
            if (! is_string($directory)
                || $directory === ''
                || strlen($directory) > 4_096
                || ! str_starts_with($directory, '/')
                || ! is_dir($directory)
                || ! is_readable($directory)
                || ! is_executable($directory)
                || ! $this->descriptorDirectoryIsEnumerable($directory)) {
                continue;
            }

            return $directory;
        }

        return null;
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

    private function resolvedSupervisorPath(): ?string
    {
        $resolved = realpath(__DIR__.'/FreshProcessGlobalSearchSupervisor.php');

        return is_string($resolved) && is_file($resolved) && is_readable($resolved)
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
