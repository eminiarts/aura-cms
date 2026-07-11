<?php

namespace Aura\Base\Livewire;

use Aura\Base\Facades\Aura;
use Livewire\Component;

class Dashboard extends Component
{
    public function render()
    {
        $resources = $this->accessibleAppResources();

        return view('aura::livewire.dashboard', [
            'stats' => $this->stats($resources),
            'recentItems' => $this->recentItems($resources),
            'recentMedia' => $this->recentMedia(),
        ])->layout('aura::components.layout.app');
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
            ->sortBy(fn ($resource) => $resource::getSort())
            ->values();
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
                'sparkline' => $this->sparkline($series),
            ];
        })->values();
    }
}
