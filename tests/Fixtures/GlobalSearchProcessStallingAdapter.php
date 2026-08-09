<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RuntimeException;

class GlobalSearchProcessStallingAdapter implements GlobalSearchAdapter
{
    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        $mode = (string) getenv('AURA_GLOBAL_SEARCH_FIXTURE_MODE');
        $marker = getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER');

        if (is_string($marker) && $marker !== '') {
            file_put_contents($marker, $mode.'-entered-'.getmypid());
        }

        if ($mode === 'sleeping') {
            usleep(750_000);
        } elseif ($mode === 'cpu') {
            $deadline = hrtime(true) + 750_000_000;

            while (hrtime(true) < $deadline) {
                hash('sha256', (string) hrtime(true));
            }
        } elseif ($mode === 'blocking') {
            $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

            if ($sockets === false) {
                throw new RuntimeException('Could not open the blocking fixture socket.');
            }

            fread($sockets[0], 1);
        }

        return collect();
    }
}
