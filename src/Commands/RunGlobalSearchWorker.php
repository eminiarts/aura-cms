<?php

namespace Aura\Base\Commands;

use Aura\Base\GlobalSearch\FreshProcessGlobalSearchExecutor;
use Aura\Base\GlobalSearch\GlobalSearchWorker;
use Illuminate\Console\Command;
use Throwable;

final class RunGlobalSearchWorker extends Command
{
    public const RESPONSE_MARKER = "\x1eAURA_GLOBAL_SEARCH_RESPONSE\x1f";

    protected $description = 'Execute one isolated global search operation';

    protected $hidden = true;

    protected $signature = 'aura:global-search-worker';

    public function handle(GlobalSearchWorker $worker): int
    {
        $envelope = ['successful' => false];

        try {
            $input = stream_get_contents(STDIN, 65_537);

            if (! FreshProcessGlobalSearchExecutor::workerRuntimeIsContained()
                || ! is_string($input)
                || strlen($input) > 65_536) {
                return $this->respond($envelope);
            }

            $request = json_decode($input, true, 32, JSON_THROW_ON_ERROR);

            if (! is_array($request)) {
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

        return $this->respond($envelope);
    }

    /** @param  array<string, mixed>  $envelope */
    private function respond(array $envelope): int
    {
        try {
            $encoded = json_encode($envelope, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $encoded = '{"successful":false}';
        }

        $this->output->write(self::RESPONSE_MARKER.$encoded);

        return 0;
    }
}
