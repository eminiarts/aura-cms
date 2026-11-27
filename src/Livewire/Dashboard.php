<?php

namespace Aura\Base\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Settings\SettingsPageAuthorizer;
use Aura\Base\Settings\SettingsRegistry;
use Illuminate\Support\Str;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $resources = $this->accessibleAppResources();
        $isEmptyWorkspace = $this->isEmptyWorkspace($resources);

        return view('aura::livewire.dashboard', [
            'stats' => $this->stats($resources),
            'createActions' => $this->createActions($resources),
            'recentItems' => $isEmptyWorkspace ? collect() : $this->recentItems($resources),
            'recentMedia' => $this->recentMedia(),
            'isEmptyWorkspace' => $isEmptyWorkspace,
            // The developer docs go to the people who also see the workspace links.
            'showDocs' => in_array(true, static::workspaceLinks(auth()->user()), true),
        ])->layout('aura::components.layout.app');
    }

    /**
     * Which workspace links the user may open, with the checks the targets themselves run
     * (CreateResource and PluginsPage mount, the settings page authorizer, the team edit policy).
     *
     * @return array{createResource: bool, settings: bool, editTeam: bool, plugins: bool}
     */
    public static function workspaceLinks($user): array
    {
        $isSuperAdmin = (bool) $user?->isSuperAdmin();
        $generalSettings = app(SettingsRegistry::class)->page('general');
        $team = config('aura.teams') ? $user?->currentTeam : null;

        return [
            'createResource' => config('aura.features.create_resource') && ! app()->environment('production') && $isSuperAdmin,
            'settings' => (bool) (config('aura.features.settings') && $generalSettings
                && app(SettingsPageAuthorizer::class)->canView($generalSettings, $user)),
            'editTeam' => (bool) ($team && $user->can('update', $team)),
            'plugins' => config('aura.features.plugins') && $isSuperAdmin,
        ];
    }

    protected function accessibleAppResources()
    {
        return collect(Aura::getResources())
            ->filter(fn ($resource) => class_exists($resource))
            ->map(fn ($resource) => app($resource))
            ->filter(fn ($resource) => $resource->isAppResource() && $resource::getShowInNavigation())
            ->filter(fn ($resource) => auth()->user()->can('viewAny', $resource))
            ->reverse()
            ->unique(fn ($resource) => class_basename($resource))
            ->reverse()
            // The app's own resources lead the stat cards and "New …" links; the package's base
            // resources (users, teams, … from config('aura.resources')) follow, then sort order.
            ->sortBy([
                fn ($a, $b) => $this->isBaseResource($a) <=> $this->isBaseResource($b),
                fn ($a, $b) => $a::getSort() <=> $b::getSort(),
            ])
            ->values();
    }

    /**
     * "New …" links for the stat card resources the viewer may create and that have a create page.
     */
    protected function createActions($resources)
    {
        $user = auth()->user();

        return $resources->take(4)
            ->filter(fn ($resource) => $user->can('create', $resource))
            ->map(fn ($resource) => [
                'label' => __('New :resource', ['resource' => Str::lower(__($resource->singularName()))]),
                'url' => $resource->createUrl(),
                // The viewer's own account is in every workspace, so an empty workspace does not lead with it.
                'isUser' => $resource->getTable() === $user->getTable(),
            ])
            ->filter(fn ($action) => $action['url'])
            ->values();
    }

    protected function isBaseResource($resource): bool
    {
        return in_array(get_class($resource), array_values(config('aura.resources', [])), true);
    }

    /**
     * True when the viewer's resources hold no records besides the viewer's
     * own user account, i.e. a team that has just been created.
     */
    protected function isEmptyWorkspace($resources): bool
    {
        $user = auth()->user();

        return $resources->every(function ($resource) use ($user) {
            $query = $resource->query();

            if ($user && $resource->getTable() === $user->getTable()) {
                $query->whereKeyNot($user->getKey());
            }

            return ! $query->exists();
        });
    }

    protected function recentItems($resources)
    {
        $items = collect();

        foreach ($resources->take(8) as $resource) {
            $latest = $resource->query()
                ->latest('updated_at')
                ->limit(4)
                ->get()
                ->map(fn ($item) => [
                    'title' => $item->title ?: $item->title(),
                    'resource' => $resource->singularName(),
                    'icon' => $resource->icon(),
                    'avatar' => $item->avatarHtml('md'),
                    'url' => $item->editUrl(),
                    'updated_at' => $item->updated_at,
                ]);

            $items = $items->concat($latest);
        }

        return $items->sortByDesc('updated_at')->take(7)->values();
    }

    protected function recentMedia()
    {
        $attachment = app(config('aura.resources.attachment'));

        if (! auth()->user()->can('viewAny', $attachment)) {
            return collect();
        }

        return $attachment->query()->latest()->limit(12)->get();
    }

    protected function sparkline(array $series): array
    {
        $max = max(max($series), 1);
        $count = count($series);
        $width = 100;
        $height = 28;
        $padding = 2;

        $points = collect($series)->values()->map(function ($value, $index) use ($count, $width, $height, $padding, $max) {
            $x = $count > 1 ? ($index / ($count - 1)) * $width : 0;
            $y = $height - $padding - ($value / $max) * ($height - 2 * $padding);

            return round($x, 2).','.round($y, 2);
        })->implode(' ');

        return [
            'line' => $points,
            'area' => "0,{$height} {$points} {$width},{$height}",
        ];
    }

    protected function stats($resources)
    {
        $start = now()->subDays(29)->startOfDay();
        $previousStart = now()->subDays(59)->startOfDay();

        return $resources->take(4)->map(function ($resource) use ($start, $previousStart) {
            $daily = $resource->query()
                ->where($resource->getTable().'.created_at', '>=', $start)
                ->selectRaw('DATE('.$resource->getTable().'.created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->pluck('total', 'date');

            $series = collect(range(29, 0))
                ->map(fn ($daysAgo) => (int) ($daily[now()->subDays($daysAgo)->toDateString()] ?? 0))
                ->all();

            $current = array_sum($series);
            $previous = $resource->query()
                ->where($resource->getTable().'.created_at', '>=', $previousStart)
                ->where($resource->getTable().'.created_at', '<', $start)
                ->count();

            return [
                'name' => $resource->pluralName(),
                'icon' => $resource->icon(),
                'url' => $resource->indexUrl(),
                'total' => $resource->query()->count(),
                'current' => $current,
                'previous' => $previous,
                // Change against the previous 30 days; unknown when that period was empty.
                'delta' => $previous > 0 ? $current - $previous : null,
                'deltaPercent' => $previous > 0 ? (int) round(($current - $previous) / $previous * 100) : null,
                // New records per day, oldest first: the stat card draws one bar per day, so a
                // sparse month reads as a few bars rather than a broken line.
                'series' => $series,
                'showSparkline' => count(array_filter($series)) >= 3,
                'sparkline' => $this->sparkline($series),
            ];
        })->values();
    }
}
