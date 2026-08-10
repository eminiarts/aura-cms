<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

final class GlobalSearchProcessServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->commands([GlobalSearchSupervisorFixtureCommand::class]);

        if (getenv('AURA_GLOBAL_SEARCH_PROCESS_FIXTURE') !== '1') {
            return;
        }

        $fixtureMode = getenv('AURA_GLOBAL_SEARCH_FIXTURE_MODE');
        $databaseConfiguration = $fixtureMode === 'tenant-collision'
            ? [
                'aura.global_search.worker_connections' => [
                    'process_search_tenant_a',
                    'process_search_tenant_b',
                ],
                'database.default' => 'process_search_tenant_b',
                'database.connections.process_search_tenant_a' => [
                    'driver' => 'sqlite',
                    'database' => getenv('DB_DATABASE_TENANT_A'),
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                ],
                'database.connections.process_search_tenant_b' => [
                    'driver' => 'sqlite',
                    'database' => getenv('DB_DATABASE_TENANT_B'),
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                ],
            ]
            : [
                'aura.global_search.worker_connections' => ['process_search'],
                'database.default' => 'process_search',
                'database.connections.process_search' => [
                    'driver' => 'sqlite',
                    'database' => getenv('DB_DATABASE'),
                    'prefix' => '',
                    'foreign_key_constraints' => true,
                ],
            ];

        config(array_merge([
            'aura.features.global_search' => true,
            'aura.features.legacy_fields_append' => false,
            'aura.global_search.execution_backend' => 'process',
            'aura.global_search.max_queries_per_resource' => 8,
            'aura.global_search.max_total_queries' => 50,
            'aura.global_search.candidate_limit' => $fixtureMode === 'before-query-mutation' ? 2 : 100,
            'aura.global_search.allowed_route_names' => ['aura.*'],
            'aura.resources.user' => User::class,
            'aura.teams' => true,
            'auth.defaults.guard' => 'web',
            'auth.guards.web.driver' => 'session',
            'auth.guards.web.provider' => 'users',
            'auth.providers.users.driver' => 'eloquent',
            'auth.providers.users.model' => User::class,
            'cache.default' => 'array',
        ], $databaseConfiguration));

        $resources = match ($fixtureMode) {
            'blocking-discovery' => [
                GlobalSearchProcessBlockingDiscoveryResource::class,
                GlobalSearchProcessResource::class,
            ],
            'tenant-collision' => [GlobalSearchProcessDefaultConnectionResource::class],
            'slow-title' => [
                GlobalSearchProcessSlowTitleResource::class,
                GlobalSearchProcessResource::class,
            ],
            'slow-discovery' => [
                GlobalSearchProcessSlowDiscoveryResource::class,
                GlobalSearchProcessResource::class,
            ],
            'spawning' => [
                GlobalSearchProcessSpawningResource::class,
                GlobalSearchProcessResource::class,
            ],
            'descriptor-probe' => [
                GlobalSearchProcessDescriptorProbeResource::class,
                GlobalSearchProcessResource::class,
            ],
            'sleeping', 'cpu', 'blocking' => [
                GlobalSearchProcessStallingResource::class,
                GlobalSearchProcessResource::class,
            ],
            'query-adapter' => [
                GlobalSearchProcessQueryFloodAdapterResource::class,
                GlobalSearchProcessResource::class,
            ],
            'query-visibility' => [
                GlobalSearchProcessQueryFloodVisibilityResource::class,
                GlobalSearchProcessResource::class,
            ],
            'query-policy' => [
                GlobalSearchProcessQueryFloodPolicyResource::class,
                GlobalSearchProcessResource::class,
            ],
            'before-query-mutation' => [GlobalSearchProcessBeforeQueryMutationResource::class],
            'before-query-union' => [GlobalSearchProcessUnionMutationResource::class],
            'auth-before-construction' => [
                GlobalSearchProcessDeniedConstructionResource::class,
                GlobalSearchProcessResource::class,
            ],
            'host-restriction' => [GlobalSearchProcessHostRestrictionResource::class],
            'query-churn' => [
                GlobalSearchProcessConnectionChurnResource::class,
                GlobalSearchProcessResource::class,
            ],
            'query-churn-event-forget' => [
                GlobalSearchProcessEventForgetConnectionChurnResource::class,
                GlobalSearchProcessResource::class,
            ],
            'forged-exit', 'forged-die', 'forged-completed-code', 'forged-fatal', 'forged-multiple', 'forged-partial', 'stderr-noise' => [
                GlobalSearchProcessOutputAttackResource::class,
            ],
            'raw-pdo' => [
                GlobalSearchProcessRawPdoAdapterResource::class,
                GlobalSearchProcessResource::class,
            ],
            default => [GlobalSearchProcessResource::class],
        };

        Aura::fake();
        Aura::registerResources($resources);

        foreach ($resources as $resource) {
            if ((new ReflectionClass($resource))->isInstantiable()) {
                Aura::registerRoutes($resource::getSlug(), $resource);
                Gate::policy($resource, GlobalSearchProcessPolicy::class);
            }
        }

        $modelResource = $fixtureMode === 'auth-before-construction'
            ? GlobalSearchProcessResource::class
            : $resources[0];
        Aura::setModel(new $modelResource);
    }
}
