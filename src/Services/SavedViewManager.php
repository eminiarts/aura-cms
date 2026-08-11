<?php

namespace Aura\Base\Services;

use Aura\Base\Facades\Aura;
use Aura\Base\Models\SavedView;
use Aura\Base\Policies\SavedViewPolicy;
use Aura\Base\Preferences\PreferenceContext;
use Aura\Base\Preferences\PreferenceManager;
use Aura\Base\Preferences\PreferenceScope;
use Aura\Base\Resource;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\SavedViews\SavedViewState;
use Aura\Base\SavedViews\SavedViewVisibility;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use InvalidArgumentException;
use LogicException;

final readonly class SavedViewManager
{
    public const DEFAULT_PREFERENCE = 'table.saved_view.default';

    public function __construct(
        private PreferenceManager $preferences,
        private SavedViewPolicy $policy,
    ) {}

    public function available(Resource $resource): bool
    {
        return config('aura.features.saved_views', false) === true
            && Schema::connection($resource->getConnectionName())->hasTable((new SavedView)->getTable());
    }

    /** @param array<string, mixed> $state */
    public function createPrivate(
        Resource $resource,
        User $actor,
        ?Team $team,
        string $name,
        array $state,
    ): SavedView {
        $this->authorize($this->policy->createPrivate($actor, $resource, $team));

        return $this->create($resource, $actor, $team, $name, $state, SavedViewVisibility::Private);
    }

    /** @param array<string, mixed> $state */
    public function createShared(
        Resource $resource,
        User $actor,
        ?Team $team,
        string $name,
        array $state,
    ): SavedView {
        $this->authorize($this->policy->createShared($actor, $resource, $team));

        return $this->create($resource, $actor, $team, $name, $state, SavedViewVisibility::Team);
    }

    public function delete(SavedView $savedView, Resource $resource, User $actor, ?Team $team): void
    {
        $savedView = $this->resolve($savedView->getKey(), $resource, $actor, $team);
        $this->authorize($this->policy->delete($actor, $savedView, $resource, $team));

        $this->connection($resource)->transaction(function () use ($actor, $resource, $savedView, $team): void {
            if ($savedView->visibility === SavedViewVisibility::Private
                && $this->preferences->get(self::DEFAULT_PREFERENCE, $this->preferenceContext($resource, $actor, $team)) === $savedView->getKey()) {
                $this->preferences->reset(
                    self::DEFAULT_PREFERENCE,
                    PreferenceScope::User,
                    $this->preferenceContext($resource, $actor, $team),
                    $actor,
                );
            }

            $savedView->deleteOrFail();
        });
    }

    public function duplicate(
        SavedView $savedView,
        Resource $resource,
        User $actor,
        ?Team $team,
        string $name,
    ): SavedView {
        $savedView = $this->resolve($savedView->getKey(), $resource, $actor, $team);
        $this->authorize($this->policy->duplicate($actor, $savedView, $resource, $team));

        return $savedView->visibility === SavedViewVisibility::Team
            ? $this->createShared($resource, $actor, $team, $name, $savedView->state)
            : $this->createPrivate($resource, $actor, $team, $name, $savedView->state);
    }

    /** @return Collection<int, SavedView> */
    public function list(Resource $resource, User $actor, ?Team $team): Collection
    {
        $this->assertAvailable($resource);
        $this->authorize($this->policy->createPrivate($actor, $resource, $team));

        $views = $this->query($resource)
            ->where('context_key', $this->contextKey($resource, $team))
            ->where('resource_type', $resource::class)
            ->where(function ($query) use ($actor): void {
                $query->where('visibility', SavedViewVisibility::Team->value)
                    ->orWhere(function ($query) use ($actor): void {
                        $query->where('visibility', SavedViewVisibility::Private->value)
                            ->where('owner_id', $actor->getKey());
                    });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return $views->filter(
            fn (SavedView $view): bool => $this->policy->view($actor, $view, $resource, $team)
        )->values();
    }

    public function rename(
        SavedView $savedView,
        Resource $resource,
        User $actor,
        ?Team $team,
        string $name,
    ): SavedView {
        $savedView = $this->resolve($savedView->getKey(), $resource, $actor, $team);
        $this->authorize($this->policy->update($actor, $savedView, $resource, $team));
        $savedView->forceFill(['name' => $this->normalizeName($name)])->saveOrFail();

        return $savedView->refresh();
    }

    public function resolve(int|string $id, Resource $resource, User $actor, ?Team $team): SavedView
    {
        $this->assertAvailable($resource);
        $savedView = $this->query($resource)->whereKey($id)->first();

        if (! $savedView instanceof SavedView || ! $this->policy->view($actor, $savedView, $resource, $team)) {
            throw (new ModelNotFoundException)->setModel(SavedView::class, [$id]);
        }

        return $savedView;
    }

    public function resolveDefault(Resource $resource, User $actor, ?Team $team): ?SavedView
    {
        if (! $this->available($resource)) {
            return null;
        }

        $preferred = $this->preferences->get(
            self::DEFAULT_PREFERENCE,
            $this->preferenceContext($resource, $actor, $team),
        );

        if (is_int($preferred)) {
            try {
                return $this->resolve($preferred, $resource, $actor, $team);
            } catch (ModelNotFoundException) {
                // A stale preference never broadens the table query.
            }
        }

        $shared = $this->query($resource)
            ->where('default_key', $this->defaultKey($resource, $team))
            ->first();

        return $shared instanceof SavedView && $this->policy->view($actor, $shared, $resource, $team)
            ? $shared
            : null;
    }

    public function setDefault(SavedView $savedView, Resource $resource, User $actor, ?Team $team): void
    {
        $savedView = $this->resolve($savedView->getKey(), $resource, $actor, $team);
        $this->authorize($this->policy->setDefault($actor, $savedView, $resource, $team));

        if ($savedView->visibility === SavedViewVisibility::Private) {
            $this->preferences->set(
                self::DEFAULT_PREFERENCE,
                (int) $savedView->getKey(),
                PreferenceScope::User,
                $this->preferenceContext($resource, $actor, $team),
                $actor,
            );

            return;
        }

        $this->connection($resource)->transaction(function () use ($resource, $savedView, $team): void {
            $this->query($resource)
                ->where('context_key', $this->contextKey($resource, $team))
                ->where('resource_type', $resource::class)
                ->where('visibility', SavedViewVisibility::Team->value)
                ->lockForUpdate()
                ->update(['default_key' => null]);

            $savedView->forceFill(['default_key' => $this->defaultKey($resource, $team)])->saveOrFail();
        });
    }

    /** @param array<string, mixed> $state */
    public function updateState(
        SavedView $savedView,
        Resource $resource,
        User $actor,
        ?Team $team,
        array $state,
    ): SavedView {
        $savedView = $this->resolve($savedView->getKey(), $resource, $actor, $team);
        $this->authorize($this->policy->update($actor, $savedView, $resource, $team));
        $validated = SavedViewState::fromArray($state, $resource);
        $savedView->forceFill([
            'schema_version' => SavedViewState::VERSION,
            'state' => $validated->toArray(),
        ])->saveOrFail();

        return $savedView->refresh();
    }

    public function validatedState(SavedView $savedView, Resource $resource, User $actor, ?Team $team): SavedViewState
    {
        $savedView = $this->resolve($savedView->getKey(), $resource, $actor, $team);

        if (! is_array($savedView->state)
            || ! is_int($savedView->schema_version)
            || (int) ($savedView->state['v'] ?? -1) !== $savedView->schema_version) {
            throw new InvalidArgumentException('The saved-view schema metadata is inconsistent.');
        }

        return SavedViewState::fromArray($savedView->state, $resource);
    }

    private function assertAvailable(Resource $resource): void
    {
        if (! $this->available($resource)) {
            throw new LogicException('Aura saved views are disabled or their migration is not installed.');
        }

        if (! in_array($resource::class, Aura::getResources(), true)) {
            throw new InvalidArgumentException('Saved views require a registered Aura resource.');
        }
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException('This saved-view operation is not authorized.');
        }
    }

    private function connection(Resource $resource): Connection
    {
        return $resource->getConnection();
    }

    private function contextKey(Resource $resource, ?Team $team): string
    {
        $teamIdentity = config('aura.teams') ? $team?->getKey() : 'instance';

        return hash('sha256', implode("\0", [
            'aura-saved-view-context-v1',
            User::connectionCacheIdentity($resource->getConnection()),
            (string) $teamIdentity,
        ]));
    }

    /** @param array<string, mixed> $state */
    private function create(
        Resource $resource,
        User $actor,
        ?Team $team,
        string $name,
        array $state,
        SavedViewVisibility $visibility,
    ): SavedView {
        $this->assertAvailable($resource);
        $validated = SavedViewState::fromArray($state, $resource);
        $savedView = new SavedView;
        $savedView->setConnection($resource->getConnectionName());
        $savedView->fill([
            'context_key' => $this->contextKey($resource, $team),
            'name' => $this->normalizeName($name),
            'owner_id' => $actor->getKey(),
            'resource_type' => $resource::class,
            'schema_version' => SavedViewState::VERSION,
            'state' => $validated->toArray(),
            'team_id' => config('aura.teams') ? $team?->getKey() : null,
            'visibility' => $visibility,
        ]);
        $savedView->saveOrFail();

        return $savedView;
    }

    private function defaultKey(Resource $resource, ?Team $team): string
    {
        return hash('sha256', implode("\0", [
            'aura-saved-view-default-v1',
            $this->contextKey($resource, $team),
            $resource::class,
        ]));
    }

    private function normalizeName(string $name): string
    {
        $name = trim($name);

        if ($name === '' || Str::length($name) > 120 || str_contains($name, "\0")) {
            throw new InvalidArgumentException('Saved-view names must contain between 1 and 120 characters.');
        }

        return $name;
    }

    private function preferenceContext(Resource $resource, User $actor, ?Team $team): PreferenceContext
    {
        return new PreferenceContext('aura.table', $actor, $team, $resource::class);
    }

    private function query(Resource $resource)
    {
        $savedView = new SavedView;
        $savedView->setConnection($resource->getConnectionName());

        return $savedView->newQuery();
    }
}
