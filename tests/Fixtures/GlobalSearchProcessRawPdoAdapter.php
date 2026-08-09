<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use PDO;

class GlobalSearchProcessRawPdoAdapter implements GlobalSearchAdapter
{
    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        $marker = (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER');
        $pdo = $resource->getConnection()->getPdo();

        if ($pdo instanceof PDO) {
            for ($queryIndex = 0; $queryIndex < 10; $queryIndex++) {
                $pdo->query('SELECT COUNT(*) FROM global_search_process_records')?->fetchColumn();
                file_put_contents($marker, 'p', FILE_APPEND);
            }
        }

        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);

        if (is_array($sockets)) {
            fread($sockets[0], 1);
        }

        return collect();
    }
}
