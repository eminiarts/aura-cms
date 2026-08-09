<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Resource;

class GlobalSearchProcessPolicy
{
    public function view(mixed $user, Resource $resource): bool
    {
        return (string) data_get($user, 'current_team_id') === (string) $resource->getAttribute('team_id');
    }

    public function viewAny(mixed $user, Resource $resource): bool
    {
        if ($resource instanceof GlobalSearchProcessDefaultConnectionResource) {
            return data_get($user, 'email') === 'tenant-a@example.test';
        }

        if ($resource instanceof GlobalSearchProcessQueryFloodPolicyResource
            && app()->bound('aura.global_search.worker_operation')
            && app('aura.global_search.worker_operation') === 'search') {
            $marker = (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER');

            for ($queryIndex = 0; $queryIndex < 10; $queryIndex++) {
                $resource->getConnection()->table('global_search_process_records')->count();
                file_put_contents($marker, 'q', FILE_APPEND);
            }
        }

        return true;
    }
}
