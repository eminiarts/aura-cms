<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Commands\RunGlobalSearchWorker;
use Closure;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Throwable;

final class FreshProcessGlobalSearchSupervisor
{
    public const CONFIGURATION_EXIT_CODE = 78;

    public const SUPERVISOR_ATTESTATION_MARKER = "\x1eAURA_GLOBAL_SEARCH_ATTESTATION\x1f";

    public const TRUSTED_BOOTSTRAP_COMPLETION_MARKER = "\x1eAURA_GLOBAL_SEARCH_BOOTSTRAP_COMPLETION\x1f";

    public const WORKER_AUTOLOAD_PATH_ENVIRONMENT_KEY = 'AURA_GLOBAL_SEARCH_WORKER_AUTOLOAD';

    public const WORKER_BOOTSTRAP_PATH_ENVIRONMENT_KEY = 'AURA_GLOBAL_SEARCH_WORKER_BOOTSTRAP';

    public const WORKER_COMPLETED_EXIT_CODE = 64;

    public const WORKER_EARLY_TERMINATION_EXIT_CODE = 70;

    public const WORKER_RESPONSE_MARKER = "\x1eAURA_GLOBAL_SEARCH_RESPONSE\x1f";

    private const MAXIMUM_PROTOCOL_BYTES = 512;

    private const REQUIRED_FUNCTIONS = [
        'fclose',
        'feof',
        'fflush',
        'fread',
        'ftruncate',
        'fopen',
        'fwrite',
        'getenv',
        'hash_hmac',
        'ini_get',
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
        'php_ini_scanned_files',
        'posix_getppid',
        'posix_kill',
        'random_bytes',
        'rewind',
        'stream_set_blocking',
        'stream_socket_pair',
        'sys_get_temp_dir',
        'umask',
        'unlink',
    ];

    private const WORKER_COMPLETION_CAPABILITY_PATH_ENVIRONMENT_KEY = 'AURA_GLOBAL_SEARCH_COMPLETION_CAPABILITY';

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
        $completionCapability = self::createCompletionCapability();

        if ($completionCapability === null) {
            self::closeChannel($supervisorWatcherChannel[0]);
            self::closeChannel($watcherWorkerChannel[0]);
            self::killAndReap($watcherProcessId);
            self::restoreSignalMask($previousSignalMask);

            return 126;
        }

        [$completionCapabilityPath, $completionCapabilityToken] = $completionCapability;
        register_shutdown_function(static function () use ($completionCapabilityPath): void {
            @unlink($completionCapabilityPath);
        });
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
                $completionCapabilityPath,
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
            $completionCapabilityToken,
        );
    }

    /** @param array<int, string> $arguments */
    public static function runWorker(array $arguments): int
    {
        if (count($arguments) !== 2) {
            return self::CONFIGURATION_EXIT_CODE;
        }

        [$artisanPath, $disabledFunctions] = $arguments;
        $functions = self::normalizedFunctionList($disabledFunctions);

        if ($artisanPath === '' || $functions === null || $functions === []) {
            return self::CONFIGURATION_EXIT_CODE;
        }

        $availableFunctions = array_values(array_filter(
            $functions,
            fn (string $function): bool => function_exists($function),
        ));
        $ffiEnabled = ! in_array(
            strtolower(trim((string) ini_get('ffi.enable'))),
            ['', '0', 'off', 'false'],
            true,
        );

        if ($availableFunctions !== [] || $ffiEnabled) {
            fwrite(
                STDERR,
                'Unable to apply the inherited global search worker PHP restrictions. '
                ."Verify the worker PHP INI scan configuration.\n",
            );

            return self::CONFIGURATION_EXIT_CODE;
        }

        $_SERVER['argv'] = [$artisanPath, 'aura:global-search-worker', '--no-interaction'];
        $_SERVER['argc'] = count($_SERVER['argv']);
        $GLOBALS['argv'] = $_SERVER['argv'];
        $GLOBALS['argc'] = $_SERVER['argc'];

        $autoloadPath = getenv(self::WORKER_AUTOLOAD_PATH_ENVIRONMENT_KEY);
        $bootstrapPath = getenv(self::WORKER_BOOTSTRAP_PATH_ENVIRONMENT_KEY);
        $completionCapabilityPath = getenv(self::WORKER_COMPLETION_CAPABILITY_PATH_ENVIRONMENT_KEY);

        if (! is_string($autoloadPath)
            || ! is_string($bootstrapPath)
            || ! is_string($completionCapabilityPath)) {
            require $artisanPath;

            return 127;
        }

        if (! self::validBootstrapPath($autoloadPath)
            || ! self::validBootstrapPath($bootstrapPath)
            || ! self::validBootstrapPath($completionCapabilityPath)) {
            return self::CONFIGURATION_EXIT_CODE;
        }

        $completionCapabilityToken = self::consumeCompletionCapability($completionCapabilityPath);

        if ($completionCapabilityToken === null) {
            return self::CONFIGURATION_EXIT_CODE;
        }

        $completed = false;
        self::registerApplicationWorkerSentinel($completed);

        $status = self::handleApplicationCommand($autoloadPath, $bootstrapPath);

        if ($status !== self::WORKER_COMPLETED_EXIT_CODE
            || ! self::writeTrustedBootstrapCompletion($completionCapabilityToken, $status)) {
            return self::WORKER_EARLY_TERMINATION_EXIT_CODE;
        }

        $completed = true;

        return $status;
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

    private static function consumeCompletionCapability(string $path, ?Closure $unlinkCapability = null): ?string
    {
        $handle = @fopen($path, 'r+b');

        if (! is_resource($handle)) {
            return null;
        }

        $token = @fread($handle, 65);
        $neutralized = @rewind($handle)
            && @fwrite($handle, str_repeat("\0", 64)) === 64
            && @fflush($handle);
        $truncated = $neutralized && @ftruncate($handle, 0) && @fflush($handle);
        @fclose($handle);
        $unlinked = $unlinkCapability === null
            ? @unlink($path)
            : $unlinkCapability($path);

        return $truncated
            && $unlinked
            && is_string($token)
            && preg_match('/^[a-f0-9]{64}$/D', $token) === 1
            ? $token
            : null;
    }

    /** @return array{0: string, 1: string}|null */
    private static function createCompletionCapability(): ?array
    {
        try {
            $token = bin2hex(random_bytes(32));
            $path = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR)
                .DIRECTORY_SEPARATOR
                .'aura-global-search-completion-'.bin2hex(random_bytes(24));
        } catch (Throwable) {
            return null;
        }

        if (strlen($path) > 4_096 || ! str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return null;
        }

        $previousMask = umask(0177);

        try {
            $handle = @fopen($path, 'x+b');
        } finally {
            umask($previousMask);
        }

        if (! is_resource($handle)) {
            return null;
        }

        $written = @fwrite($handle, $token);
        @fclose($handle);

        if ($written !== strlen($token)) {
            @unlink($path);

            return null;
        }

        return [$path, $token];
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

    private static function handleApplicationCommand(string $autoloadPath, string $bootstrapPath): int
    {
        require_once $autoloadPath;
        require_once dirname(__DIR__).'/Commands/RunGlobalSearchWorker.php';

        $application = require $bootstrapPath;

        if (! $application instanceof Application) {
            return self::WORKER_EARLY_TERMINATION_EXIT_CODE;
        }

        $kernel = $application->make(ConsoleKernel::class);

        if (! $kernel instanceof ConsoleKernel) {
            return self::WORKER_EARLY_TERMINATION_EXIT_CODE;
        }

        $kernel->bootstrap();
        $registeredWorker = $kernel->all()['aura:global-search-worker'] ?? null;

        if (! $registeredWorker instanceof RunGlobalSearchWorker
            || get_class($registeredWorker) !== RunGlobalSearchWorker::class) {
            return self::WORKER_EARLY_TERMINATION_EXIT_CODE;
        }

        $worker = $application->make(GlobalSearchWorker::class);

        if (! $worker instanceof GlobalSearchWorker || get_class($worker) !== GlobalSearchWorker::class) {
            return self::WORKER_EARLY_TERMINATION_EXIT_CODE;
        }

        $input = new ArgvInput;
        $status = (new RunGlobalSearchWorker)->handle($worker);
        $kernel->terminate($input, $status);

        return $status;
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

    /** @return array<int, string>|null */
    private static function normalizedFunctionList(string $functions): ?array
    {
        $normalized = [];

        foreach (explode(',', $functions) as $function) {
            $function = strtolower(trim($function));

            if ($function === '') {
                continue;
            }

            if (strlen($function) > 128
                || preg_match('/^[a-z_][a-z0-9_]*$/', $function) !== 1) {
                return null;
            }

            $normalized[$function] = true;
        }

        return count($normalized) <= 256 ? array_keys($normalized) : null;
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

    private static function registerApplicationWorkerSentinel(bool &$completed): void
    {
        register_shutdown_function(static function () use (&$completed): void {
            if ($completed) {
                return;
            }

            @fwrite(STDOUT, self::WORKER_RESPONSE_MARKER.'{"successful":false}');
            exit(self::WORKER_EARLY_TERMINATION_EXIT_CODE);
        });
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
        string $completionCapabilityPath,
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

        $workerEnvironment = self::workerEnvironment(
            $disabledFunctions,
            $completionCapabilityPath,
        );
        $supervisorPath = realpath(__FILE__);

        if ($workerEnvironment === null || ! is_string($supervisorPath)) {
            fwrite(
                STDERR,
                "Unable to prepare the inherited global search worker PHP restrictions.\n",
            );

            return self::CONFIGURATION_EXIT_CODE;
        }

        pcntl_exec($phpBinary, [
            '-r',
            'require $argv[1]; exit(\Aura\Base\GlobalSearch\FreshProcessGlobalSearchSupervisor::runWorker(array_slice($argv, 2)));',
            '--',
            $supervisorPath,
            $artisanPath,
            $disabledFunctions,
        ], $workerEnvironment);

        fwrite(STDERR, "Unable to execute the global search worker.\n");

        return 127;
    }

    private static function supervise(
        int $parentProcessId,
        int $deadlineNanoseconds,
        int $watcherProcessId,
        int $workerProcessId,
        ?int &$terminationSignal,
        string $completionCapabilityToken,
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

                $workerExitCode = self::exitCode($workerStatus);

                if ($workerExitCode === self::WORKER_COMPLETED_EXIT_CODE
                    && ! self::writeSupervisorAttestation(
                        $workerProcessId,
                        $workerExitCode,
                        $deadlineNanoseconds,
                        $completionCapabilityToken,
                    )) {
                    return 126;
                }

                return $workerExitCode;
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

    private static function validBootstrapPath(string $path): bool
    {
        return $path !== ''
            && strlen($path) <= 4_096
            && str_starts_with($path, DIRECTORY_SEPARATOR)
            && is_file($path)
            && is_readable($path);
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

    /** @return array<string, string>|null */
    private static function workerEnvironment(
        string $requiredDisabledFunctions,
        string $completionCapabilityPath,
    ): ?array {
        $required = self::normalizedFunctionList($requiredDisabledFunctions);
        $inherited = self::normalizedFunctionList((string) ini_get('disable_functions'));
        $environment = getenv();
        $workerIniPath = realpath(__DIR__.'/global-search-worker.ini');

        if ($required === null
            || $inherited === null
            || ! is_array($environment)
            || ! is_string($workerIniPath)) {
            return null;
        }

        $disabledFunctions = array_values(array_unique([...$inherited, ...$required]));
        $scanDirectories = self::workerIniScanDirectories();

        if ($disabledFunctions === [] || $scanDirectories === null) {
            return null;
        }

        $environment['AURA_GLOBAL_SEARCH_DISABLED_FUNCTIONS'] = implode(',', $disabledFunctions);
        $environment[self::WORKER_COMPLETION_CAPABILITY_PATH_ENVIRONMENT_KEY] = $completionCapabilityPath;
        $environment['PHP_INI_SCAN_DIR'] = $scanDirectories === ''
            ? dirname($workerIniPath)
            : $scanDirectories.PATH_SEPARATOR.dirname($workerIniPath);

        return $environment;
    }

    private static function workerIniScanDirectories(): ?string
    {
        $scannedFiles = php_ini_scanned_files();

        if ($scannedFiles === false || trim($scannedFiles) === '') {
            return '';
        }

        $directories = [];

        foreach (preg_split('/,\s*/', trim($scannedFiles)) ?: [] as $file) {
            $file = trim($file);

            if ($file !== '') {
                $directories[dirname($file)] = true;
            }
        }

        return implode(PATH_SEPARATOR, array_keys($directories));
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

    private static function writeSupervisorAttestation(
        int $workerProcessId,
        int $workerExitCode,
        int $deadlineNanoseconds,
        string $completionCapabilityToken,
    ): bool {
        try {
            $payload = self::SUPERVISOR_ATTESTATION_MARKER.json_encode([
                'worker_pid' => $workerProcessId,
                'contained' => true,
                'completion_exit_code' => $workerExitCode,
                'completion_capability' => $completionCapabilityToken,
            ], JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return false;
        }

        $offset = 0;
        $payloadLength = strlen($payload);

        while ($offset < $payloadLength && hrtime(true) < $deadlineNanoseconds) {
            $written = @fwrite(STDOUT, substr($payload, $offset));

            if ($written === false) {
                return false;
            }

            if ($written > 0) {
                $offset += $written;
            } else {
                usleep(1_000);
            }
        }

        return $offset === $payloadLength;
    }

    private static function writeTrustedBootstrapCompletion(string $token, int $status): bool
    {
        $workerProcessId = getmypid();

        if (! is_int($workerProcessId) || $workerProcessId < 2) {
            return false;
        }

        $proof = hash_hmac('sha256', "{$workerProcessId}:{$status}", $token);
        $payload = self::TRUSTED_BOOTSTRAP_COMPLETION_MARKER.$proof;

        return @fwrite(STDOUT, $payload) === strlen($payload);
    }
}
