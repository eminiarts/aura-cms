<?php

namespace Aura\Base\Commands;

use Aura\Base\GlobalSearch\FreshProcessGlobalSearchExecutor;
use Aura\Base\GlobalSearch\GlobalSearchWorker;
use Illuminate\Console\Command;
use Throwable;

final class RunGlobalSearchWorker extends Command
{
    public const COMPLETED_EXIT_CODE = 64;

    public const EARLY_TERMINATION_EXIT_CODE = 70;

    public const RESPONSE_MARKER = "\x1eAURA_GLOBAL_SEARCH_RESPONSE\x1f";

    protected $description = 'Execute one isolated global search operation';

    protected $hidden = true;

    protected $signature = 'aura:global-search-worker';

    public function handle(GlobalSearchWorker $worker): int
    {
        $completed = false;
        register_shutdown_function(static function () use (&$completed): void {
            if ($completed) {
                return;
            }

            self::writeEnvelope(['successful' => false]);
            exit(self::EARLY_TERMINATION_EXIT_CODE);
        });
        $envelope = ['successful' => false];

        try {
            $input = stream_get_contents(STDIN, 65_537);

            if (! FreshProcessGlobalSearchExecutor::workerRuntimeIsContained()
                || ! is_string($input)
                || strlen($input) > 65_536) {
                $completed = true;

                return $this->respond($envelope);
            }

            $request = json_decode($input, true, 32, JSON_THROW_ON_ERROR);

            if (! is_array($request)) {
                $completed = true;

                return $this->respond($envelope);
            }

            $result = $worker->execute($request);

            if ($result !== []) {
                $envelope = [
                    'successful' => true,
                    'result' => $result,
                ];
            }
        } catch (Throwable) {
            // The response is deliberately metadata-only. Search terms, result
            // data, and exception messages never cross this failure boundary.
        }

        $completed = true;

        return $this->respond($envelope);
    }

    /** @param  array<string, mixed>  $envelope */
    private function respond(array $envelope): int
    {
        self::writeEnvelope($envelope);

        return self::COMPLETED_EXIT_CODE;
    }

    /** @param  array<string, mixed>  $envelope */
    private static function writeEnvelope(array $envelope): void
    {
        try {
            $encoded = json_encode($envelope, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $encoded = '{"successful":false}';
        }

        fwrite(STDOUT, self::RESPONSE_MARKER.$encoded);
    }
}
