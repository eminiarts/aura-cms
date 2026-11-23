@php
    // Fixed labels keep screenshots stable.
    $days = collect(range(1, 14))->map(fn (int $day): string => "Jul {$day}");
    $series = fn (array $values): array => $days->combine($values)->all();

    $chartWidgets = [
        'value' => [
            'widget' => ['name' => 'Posts', 'slug' => 'styleguide-value'],
            'values' => ['current' => "2'741", 'previous' => "2'533", 'change' => '8.21'],
            'span' => 'md:col-span-3',
        ],
        'value-goal' => [
            'view' => 'value',
            'widget' => ['name' => 'Signups vs. goal', 'slug' => 'styleguide-value-goal', 'goal' => 5000],
            'values' => ['current' => "3'120", 'previous' => "2'980", 'change' => '4.7'],
            'span' => 'md:col-span-3',
        ],
        'sparkline-area' => [
            'widget' => ['name' => 'Visits', 'slug' => 'styleguide-sparkline-area'],
            'values' => [
                'current' => $series([12, 18, 15, 22, 19, 27, 24, 30, 26, 33, 29, 36, 31, 38]),
                'previous' => $series([10, 12, 14, 13, 17, 16, 19, 18, 22, 20, 24, 23, 26, 25]),
            ],
            'span' => 'md:col-span-3',
        ],
        'sparkline-bar' => [
            'widget' => ['name' => 'Orders', 'slug' => 'styleguide-sparkline-bar'],
            'values' => [
                'current' => $series([4, 7, 5, 9, 6, 8, 11, 7, 10, 12, 9, 13, 11, 14]),
                'previous' => $series([3, 5, 4, 6, 5, 7, 6, 8, 7, 9, 8, 10, 9, 11]),
            ],
            'span' => 'md:col-span-3',
        ],
        'bar' => [
            'widget' => ['name' => 'Posts per weekday', 'slug' => 'styleguide-bar', 'previous' => true],
            'values' => [
                'current' => ['Mon' => 32, 'Tue' => 41, 'Wed' => 38, 'Thu' => 45, 'Fri' => 29, 'Sat' => 12, 'Sun' => 9],
                'previous' => ['Mon' => 28, 'Tue' => 35, 'Wed' => 40, 'Thu' => 37, 'Fri' => 31, 'Sat' => 10, 'Sun' => 7],
            ],
            'span' => 'md:col-span-6',
        ],
        'donut' => [
            'widget' => ['name' => 'Posts by status', 'slug' => 'styleguide-donut'],
            'values' => ['current' => ['Published' => 48, 'Draft' => 12, 'In Review' => 7, 'Archived' => 5], 'previous' => []],
            'span' => 'md:col-span-3',
        ],
        'pie' => [
            'widget' => ['name' => 'Users by role', 'slug' => 'styleguide-pie'],
            'values' => ['current' => ['Editor' => 18, 'Author' => 31, 'Admin' => 4], 'previous' => []],
            'span' => 'md:col-span-3',
        ],
    ];
@endphp

<div class="grid gap-4 mt-6 md:grid-cols-12">
    @foreach ($chartWidgets as $key => $chart)
        {{-- Widgets are h-full, so the caption gets its own grid row instead of overflowing the cell. --}}
        <div class="grid grid-rows-[auto_1fr] gap-1.5 {{ $chart['span'] }}" data-chart="{{ $key }}">
            <code class="text-[11px] text-gray-400 dark:text-gray-500">widgets/{{ $chart['view'] ?? $key }}</code>
            @livewire('aura::styleguide-widget', [
                'widgetView' => $chart['view'] ?? $key,
                'widget' => $chart['widget'],
                'demoValues' => $chart['values'],
            ], key('styleguide-widget-'.$key))
        </div>
    @endforeach
</div>
<p class="mt-2 text-xs text-gray-400 dark:text-gray-500">
    {{ __('On a resource dashboard the widgets sit in widgets/index with a fixed 16px gutter (-mx-2 px-2 pb-4), not --aura-layout-gap. Sparklines bleed to the card edge with a fixed -mx-5 -mb-5, which only matches the comfortable panel padding.') }}
</p>
