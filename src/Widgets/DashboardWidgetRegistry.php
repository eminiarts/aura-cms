<?php

namespace Aura\Base\Widgets;

use Aura\Base\Facades\Aura;
use Aura\Base\Preferences\PreferenceContext;
use Aura\Base\Preferences\PreferenceManager;
use Aura\Base\Resource;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Component;
use ReflectionClass;
use Throwable;

final readonly class DashboardWidgetRegistry
{
    public function __construct(private PreferenceManager $preferences) {}

    /**
     * @return list<array{id: string, component: class-string<Component>, arguments: array<string, mixed>, class: string}>
     */
    public function forUser(User $user): array
    {
        $definitions = [];

        foreach (Aura::getWidgets() as $index => $registered) {
            $definition = $this->normalize($registered, $index);

            if ($definition === null
                || isset($definitions[$definition['id']])
                || ! $this->authorized($definition, $user)) {
                continue;
            }

            $definitions[$definition['id']] = $definition;
        }

        [$hidden, $preferredOrder] = $this->preferences($user);
        $preferredPositions = array_flip($preferredOrder);

        return collect($definitions)
            ->reject(fn (array $definition): bool => in_array($definition['id'], $hidden, true))
            ->sort(function (array $left, array $right) use ($preferredPositions): int {
                $leftPreferred = $preferredPositions[$left['id']] ?? null;
                $rightPreferred = $preferredPositions[$right['id']] ?? null;

                if ($leftPreferred !== null || $rightPreferred !== null) {
                    return ($leftPreferred ?? PHP_INT_MAX) <=> ($rightPreferred ?? PHP_INT_MAX);
                }

                return [$left['order'], $left['index'], $left['id']]
                    <=> [$right['order'], $right['index'], $right['id']];
            })
            ->map(fn (array $definition): array => [
                'id' => $definition['id'],
                'component' => $definition['component'],
                'arguments' => $definition['arguments'],
                'class' => $this->columnClass($definition['columns']),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function authorized(array $definition, User $user): bool
    {
        if ($definition['authorization'] === false) {
            return false;
        }

        $resource = $definition['resource'];
        $authorization = $definition['authorization'];

        if ($authorization === null && $resource === null) {
            return true;
        }

        $ability = is_array($authorization) ? ($authorization['ability'] ?? null) : null;
        $subject = is_array($authorization) ? ($authorization['subject'] ?? null) : null;

        if ($ability === null && $resource !== null) {
            $ability = 'viewAny';
            $subject = $resource;
        }

        if (! is_string($ability) || trim($ability) === '' || $subject === null || is_array($subject)) {
            return false;
        }

        try {
            if (is_string($subject) && is_a($subject, Resource::class, true)) {
                $subject = app($subject);
            }

            return Gate::forUser($user)->allows($ability, $subject);
        } catch (Throwable) {
            return false;
        }
    }

    private function columnClass(int $columns): string
    {
        return match ($columns) {
            1 => 'col-span-12 lg:col-span-1',
            2 => 'col-span-12 sm:col-span-6 lg:col-span-2',
            3 => 'col-span-12 sm:col-span-6 lg:col-span-3',
            4 => 'col-span-12 sm:col-span-6 lg:col-span-4',
            5 => 'col-span-12 sm:col-span-6 lg:col-span-5',
            6 => 'col-span-12 sm:col-span-6',
            7 => 'col-span-12 lg:col-span-7',
            8 => 'col-span-12 lg:col-span-8',
            9 => 'col-span-12 lg:col-span-9',
            10 => 'col-span-12 lg:col-span-10',
            11 => 'col-span-12 lg:col-span-11',
            default => 'col-span-12',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalize(mixed $registered, int $index): ?array
    {
        $registered = is_string($registered) ? [
            'component' => $registered,
            'id' => Str::kebab(class_basename($registered)),
        ] : $registered;

        if (! is_array($registered)) {
            return null;
        }

        $id = $registered['id'] ?? $registered['slug'] ?? null;
        $component = $registered['component'] ?? $registered['type'] ?? null;
        $arguments = $registered['arguments'] ?? [];
        $resource = $registered['resource'] ?? null;
        $authorization = $registered['authorization'] ?? null;
        $order = $registered['order'] ?? 0;
        $columns = $registered['columns'] ?? 12;
        $visible = $registered['visible'] ?? true;

        if (! is_string($id)
            || preg_match('/\A[a-z][a-z0-9._-]*\z/', $id) !== 1
            || ! is_string($component)
            || ! class_exists($component)
            || ! is_subclass_of($component, Component::class)
            || ! (new ReflectionClass($component))->isInstantiable()
            || ! is_array($arguments)
            || ! $this->safeArguments($arguments)
            || ! is_int($order)
            || ! is_int($columns)
            || $columns < 1
            || $columns > 12
            || ! is_bool($visible)
            || ! $visible
            || ($resource !== null && (! is_string($resource) || ! is_a($resource, Resource::class, true)))
            || ! ($authorization === null || $authorization === false || is_array($authorization))) {
            return null;
        }

        if ($resource !== null) {
            $reflection = new ReflectionClass($resource);

            if (! $reflection->isInstantiable()) {
                return null;
            }

            try {
                $resource = app($resource);
            } catch (Throwable) {
                return null;
            }

            if (! $resource instanceof Resource) {
                return null;
            }

            $arguments['model'] = $resource;
        }

        if (array_key_exists('widget', $registered)) {
            if (! is_array($registered['widget']) || ! $this->safeArguments($registered['widget'])) {
                return null;
            }

            $arguments['widget'] = ['id' => $id, ...$registered['widget']];
        }

        return [
            'arguments' => $arguments,
            'authorization' => $authorization,
            'columns' => $columns,
            'component' => $component,
            'id' => $id,
            'index' => $index,
            'order' => $order,
            'resource' => $resource,
        ];
    }

    /**
     * @return array{0: list<string>, 1: list<string>}
     */
    private function preferences(User $user): array
    {
        $team = config('aura.teams') ? $user->currentTeam : null;
        $context = new PreferenceContext('aura.dashboard', $user, $team instanceof Team ? $team : null);

        try {
            $hidden = $this->preferences->get('dashboard.widgets.hidden', $context);
            $order = $this->preferences->get('dashboard.widgets.order', $context);
        } catch (InvalidArgumentException) {
            return [[], []];
        }

        return [
            is_array($hidden) ? array_values(array_unique($hidden)) : [],
            is_array($order) ? array_values(array_unique($order)) : [],
        ];
    }

    private function safeArguments(mixed $value): bool
    {
        if ($value === null || is_scalar($value)) {
            return ! is_float($value) || is_finite($value);
        }

        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $key => $item) {
            if ((! is_string($key) && ! is_int($key)) || ! $this->safeArguments($item)) {
                return false;
            }
        }

        return true;
    }
}
