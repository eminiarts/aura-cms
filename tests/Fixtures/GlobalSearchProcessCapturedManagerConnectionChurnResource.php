<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\Exceptions\GlobalSearchExecutionFailed;
use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\GlobalSearch\GlobalSearchGuardedEventDispatcher;
use Aura\Base\Resource;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

final class GlobalSearchProcessCapturedManagerConnectionChurnAdapter implements GlobalSearchAdapter
{
    public static ?DatabaseManager $capturedDatabase = null;

    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        $mode = (string) getenv('AURA_GLOBAL_SEARCH_FIXTURE_MODE');
        $database = $mode === 'query-churn-late-extension-current-manager'
            ? app('db')
            : self::$capturedDatabase;

        if (! $database instanceof DatabaseManager) {
            return collect();
        }

        if (str_contains($mode, 'dispatcher-')) {
            $replacementDispatcher = new Dispatcher(app());
            $applicationReplacementWasRejected = false;
            $facadeReplacementWasRejected = false;

            try {
                app()->instance('events', $replacementDispatcher);
            } catch (GlobalSearchExecutionFailed) {
                $applicationReplacementWasRejected = true;
            }

            try {
                Event::swap($replacementDispatcher);
            } catch (GlobalSearchExecutionFailed) {
                $facadeReplacementWasRejected = true;
            }

            Event::forget(ConnectionEstablished::class);
            $clearPathWasHandled = false;

            if (str_contains($mode, 'offset-unset')) {
                $application = app();
                unset($application['events']);
                $resolvedDispatcher = app('events');
                $clearPathWasHandled = true;
            } elseif (str_contains($mode, 'guarded-binding-forget')) {
                $guardedDispatcherBinding = app()->getAlias('events');
                app()->forgetInstance($guardedDispatcherBinding);

                try {
                    app('events');
                } catch (GlobalSearchExecutionFailed) {
                    $clearPathWasHandled = true;
                }

                $resolvedDispatcher = app('events');
            } else {
                app()->forgetInstance('events');
                $resolvedDispatcher = app('events');
                $clearPathWasHandled = true;
            }

            file_put_contents(
                (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'),
                ($applicationReplacementWasRejected ? 'A' : 'a')
                    .($facadeReplacementWasRejected ? 'F' : 'f')
                    .($clearPathWasHandled ? 'C' : 'c')
                    .($resolvedDispatcher instanceof GlobalSearchGuardedEventDispatcher ? 'G' : 'R'),
                FILE_APPEND,
            );
        }

        Event::forget(ConnectionEstablished::class);

        if (str_contains($mode, 'late-extension')) {
            $database->extend(
                str_ends_with($mode, '-driver') ? 'sqlite' : 'process_search',
                fn (array $configuration, string $connectionName) => (new ConnectionFactory(app()))
                    ->make($configuration, $connectionName),
            );
        }

        $database->purge('process_search');

        foreach (range(1, 10) as $iteration) {
            try {
                $database->connection('process_search')->select('select 1');
            } catch (GlobalSearchExecutionFailed $exception) {
                if (! str_contains($mode, 'dispatcher-')) {
                    throw $exception;
                }

                file_put_contents(
                    (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'),
                    'X',
                    FILE_APPEND,
                );

                break;
            }

            file_put_contents(
                (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'),
                'q',
                FILE_APPEND,
            );
        }

        return collect();
    }
}

final class GlobalSearchProcessCapturedManagerConnectionChurnResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-captured-manager-churn';

    public static string $type = 'ProcessSearchCapturedManagerChurn';

    public static function captureDatabase(DatabaseManager $database): void
    {
        GlobalSearchProcessCapturedManagerConnectionChurnAdapter::$capturedDatabase = $database;
    }

    public function globalSearchAdapter()
    {
        return GlobalSearchProcessCapturedManagerConnectionChurnAdapter::class;
    }
}
