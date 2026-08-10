<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Commands\RunGlobalSearchWorker;
use Aura\Base\Facades\Aura;
use Aura\Base\GlobalSearch\FreshProcessGlobalSearchSupervisor;
use Aura\Base\GlobalSearch\GlobalSearchGuardedEventDispatcher;
use Aura\Base\Resources\User;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
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

        if ($fixtureMode === 'provider-replaced-worker-command') {
            $this->commands([GlobalSearchReplacementWorkerCommand::class]);
        }

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

        if ($fixtureMode === 'provider-forged-completed-code') {
            ob_start();
            register_shutdown_function(static function (): void {
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }

                fwrite(STDOUT, RunGlobalSearchWorker::RESPONSE_MARKER.json_encode([
                    'successful' => true,
                    'result' => [
                        'results' => [[
                            'id' => 2,
                            'type' => 'ForgedResult',
                            'title' => 'Provider policy bypass',
                            'icon' => '<svg viewBox="0 0 10 10"><path d="M0 0h10v10z"/></svg>',
                            'url' => '/admin/process-search-output-attack/2',
                            'rank' => 999,
                        ]],
                        'query_count' => 1,
                    ],
                ], JSON_THROW_ON_ERROR));
                exit(RunGlobalSearchWorker::COMPLETED_EXIT_CODE);
            });
        }

        if ($fixtureMode === 'provider-public-completion-forge') {
            if (is_callable([
                FreshProcessGlobalSearchSupervisor::class,
                'markApplicationWorkerCompleted',
            ])) {
                FreshProcessGlobalSearchSupervisor::markApplicationWorkerCompleted();
            }

            fwrite(STDOUT, RunGlobalSearchWorker::RESPONSE_MARKER.json_encode([
                'successful' => true,
                'result' => [
                    'results' => [[
                        'id' => 2,
                        'type' => 'ForgedResult',
                        'title' => 'Public completion authority bypass',
                        'icon' => '<svg viewBox="0 0 10 10"><path d="M0 0h10v10z"/></svg>',
                        'url' => '/admin/process-search-output-attack/2',
                        'rank' => 999,
                    ]],
                    'query_count' => 1,
                ],
            ], JSON_THROW_ON_ERROR));
            exit(RunGlobalSearchWorker::COMPLETED_EXIT_CODE);
        }

        if (in_array($fixtureMode, [
            'query-churn-captured-manager',
            'query-churn-late-extension-captured-manager',
            'query-churn-dispatcher-rebind-late-extension-name',
            'query-churn-dispatcher-rebind-late-extension-driver',
            'query-churn-dispatcher-prebound-callback-late-extension-name',
        ], true)) {
            GlobalSearchProcessCapturedManagerConnectionChurnResource::captureDatabase(app('db'));
        }

        if ($fixtureMode === 'query-churn-dispatcher-prebound-callback-late-extension-name') {
            $capturedDatabase = app('db');

            app()->rebinding('events', static function (mixed $application, mixed $replacement) use ($capturedDatabase): void {
                if (! $capturedDatabase instanceof DatabaseManager
                    || ! $replacement instanceof Dispatcher
                    || $replacement instanceof GlobalSearchGuardedEventDispatcher) {
                    return;
                }

                file_put_contents(
                    (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'),
                    'raw-dispatcher-observed',
                    FILE_APPEND,
                );
                Event::forget(ConnectionEstablished::class);
                $capturedDatabase->extend(
                    'process_search',
                    fn (array $configuration, string $connectionName) => (new ConnectionFactory(app()))
                        ->make($configuration, $connectionName),
                );
                $capturedDatabase->purge('process_search');

                foreach (range(1, 10) as $iteration) {
                    $capturedDatabase->connection('process_search')->select('select 1');
                    file_put_contents(
                        (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'),
                        'q',
                        FILE_APPEND,
                    );
                }
            });
        }

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
            'query-churn-captured-manager' => [
                GlobalSearchProcessCapturedManagerConnectionChurnResource::class,
                GlobalSearchProcessResource::class,
            ],
            'query-churn-late-extension-captured-manager', 'query-churn-late-extension-current-manager', 'query-churn-dispatcher-rebind-late-extension-name', 'query-churn-dispatcher-rebind-late-extension-driver', 'query-churn-dispatcher-prebound-callback-late-extension-name' => [
                GlobalSearchProcessCapturedManagerConnectionChurnResource::class,
                GlobalSearchProcessResource::class,
            ],
            'forged-exit', 'forged-die', 'forged-completed-code', 'provider-forged-completed-code', 'provider-public-completion-forge', 'provider-replaced-worker-command', 'forged-fatal', 'forged-multiple', 'forged-partial', 'stderr-noise' => [
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
