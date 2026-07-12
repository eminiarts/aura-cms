@php
    $sections = [
        'colors' => 'Colors',
        'typography' => 'Typography',
        'icons' => 'Icons',
        'buttons' => 'Buttons',
        'forms' => 'Forms',
        'badges' => 'Badges',
        'cards' => 'Cards & Widgets',
        'table' => 'Table',
        'navigation' => 'Sidebar',
        'menus' => 'Dropdowns & Tooltips',
        'overlays' => 'Modals & Slide-overs',
        'notifications' => 'Notifications',
        'feedback' => 'Feedback',
        'auth' => 'Auth',
    ];

    $shades = [25, 50, 100, 200, 300, 400, 500, 600, 700, 800, 900];

    $sidebarTokens = [
        '--sidebar-bg' => 'sidebar-bg',
        '--sidebar-bg-hover' => 'sidebar-bg-hover',
        '--sidebar-bg-dropdown' => 'sidebar-bg-dropdown',
        '--sidebar-icon' => 'sidebar-icon',
        '--sidebar-icon-hover' => 'sidebar-icon-hover',
        '--sidebar-text' => 'sidebar-text',
    ];

    $icons = [
        'adjustments', 'arrow-left', 'arrow-right', 'attachment', 'bookmark', 'bookmarked', 'brush', 'check',
        'chevron-down', 'chevron-up', 'circle', 'close', 'cog', 'collection', 'color-swatch', 'config',
        'dashboard', 'dots', 'edit', 'empty', 'exclamation', 'file', 'filter', 'grid', 'impersonate', 'info',
        'kanban', 'list', 'loading', 'media', 'minus', 'move', 'notifications', 'notifications-unread',
        'option', 'permission', 'plus', 'role', 'search', 'table', 'team', 'team-invitation', 'template-plain',
        'template-tabs', 'template-tabs-panels', 'trash', 'upload', 'user', 'user-impersonate', 'view',
    ];

    $statusPills = [
        'Draft' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        'In Review' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-400',
        'Scheduled' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-400',
        'Published' => 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-400',
        'Archived' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-400',
        'Failed' => 'bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-400',
    ];

    $navBadgeColors = [
        'primary' => 'bg-primary-100 text-primary-700',
        'gray' => 'bg-gray-100 text-gray-600',
        'red' => 'bg-red-100 text-red-700',
        'yellow' => 'bg-yellow-100 text-yellow-800',
        'green' => 'bg-green-100 text-green-700',
        'blue' => 'bg-blue-100 text-blue-700',
        'indigo' => 'bg-indigo-100 text-indigo-700',
        'purple' => 'bg-purple-100 text-purple-700',
        'pink' => 'bg-pink-100 text-pink-700',
    ];

    $sparklineLine = '0,22 8,20 17,23 25,17 33,19 42,14 50,16 58,11 67,13 75,9 83,12 92,7 100,9';
    $sparklineArea = $sparklineLine.' 100,28 0,28';

    $stats = [
        [
            'name' => 'Posts',
            'total' => 1284,
            'current' => 86,
            'url' => '#cards',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>',
            'sparkline' => ['line' => $sparklineLine, 'area' => $sparklineArea],
        ],
        [
            'name' => 'Users',
            'total' => 342,
            'current' => 0,
            'url' => '#cards',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>',
            'sparkline' => ['line' => $sparklineLine, 'area' => $sparklineArea],
        ],
    ];

    $tableRows = [
        ['id' => 1, 'title' => 'Getting started with Aura', 'status' => 'Published', 'statusClass' => $statusPills['Published'], 'author' => 'Bajram Emini', 'date' => '2026-07-01', 'selected' => false],
        ['id' => 2, 'title' => 'Designing resource fields', 'status' => 'Draft', 'statusClass' => $statusPills['Draft'], 'author' => 'Sarah Keller', 'date' => '2026-07-04', 'selected' => true],
        ['id' => 3, 'title' => 'Team permissions explained', 'status' => 'In Review', 'statusClass' => $statusPills['In Review'], 'author' => 'Jonas Weber', 'date' => '2026-07-08', 'selected' => false],
        ['id' => 4, 'title' => 'Publishing workflows', 'status' => 'Failed', 'statusClass' => $statusPills['Failed'], 'author' => 'Mia Brunner', 'date' => '2026-07-10', 'selected' => false],
    ];
@endphp

<div class="pb-24">

    {{-- Page header --}}
    <div>
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Style Guide') }}</h1>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Every UI component in Aura on one page — rendered with the real Blade components wherever possible. Improve a component here and the whole admin follows.') }}
        </p>
    </div>

    {{-- Anchor navigation --}}
    <nav class="sticky top-0 z-[5] -mx-4 px-4 py-3 mt-6 bg-white/95 backdrop-blur-md border-b border-gray-950/5 dark:bg-gray-900/95 dark:border-white/10">
        <div class="flex flex-wrap gap-1">
            @foreach ($sections as $id => $label)
                <a href="#{{ $id }}"
                    class="px-3 py-1.5 text-sm rounded-md text-gray-500 transition hover:text-gray-900 hover:bg-gray-50 dark:text-gray-400 dark:hover:text-white dark:hover:bg-white/5">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </nav>


    {{-- ============================================================ --}}
    {{-- Colors --}}
    {{-- ============================================================ --}}
    <section id="colors" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">01</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Colors') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('All colors are CSS variables set by the theme engine (config/aura.php → layout/colors.blade.php). Swatches below render the live values of the current theme.') }}
        </p>

        <div class="mt-6 space-y-8">
            <div>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Primary <span class="ml-2 font-normal text-gray-400">--primary-*</span></h3>
                <div class="grid grid-cols-6 gap-2 mt-3 sm:grid-cols-11">
                    @foreach ($shades as $shade)
                        <div>
                            <div class="h-12 rounded-lg ring-1 ring-inset ring-gray-950/10 dark:ring-white/10" style="background-color: rgb(var(--primary-{{ $shade }}))"></div>
                            <span class="block mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $shade }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Gray <span class="ml-2 font-normal text-gray-400">--gray-*</span></h3>
                <div class="grid grid-cols-6 gap-2 mt-3 sm:grid-cols-11">
                    @foreach ($shades as $shade)
                        <div>
                            <div class="h-12 rounded-lg ring-1 ring-inset ring-gray-950/10 dark:ring-white/10" style="background-color: rgb(var(--gray-{{ $shade }}))"></div>
                            <span class="block mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $shade }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">Sidebar tokens <span class="ml-2 font-normal text-gray-400">--sidebar-*</span></h3>
                <div class="grid grid-cols-3 gap-2 mt-3 sm:grid-cols-6">
                    @foreach ($sidebarTokens as $var => $label)
                        <div>
                            <div class="h-12 rounded-lg ring-1 ring-inset ring-gray-950/10 dark:ring-white/10" style="background-color: rgb(var({{ $var }}))"></div>
                            <span class="block mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Semantic colors') }}</h3>
                <div class="grid grid-cols-2 gap-2 mt-3 sm:grid-cols-4">
                    <div>
                        <div class="h-12 bg-green-500 rounded-lg"></div>
                        <span class="block mt-1.5 text-xs text-gray-500 dark:text-gray-400">success · green-500</span>
                    </div>
                    <div>
                        <div class="h-12 bg-red-500 rounded-lg"></div>
                        <span class="block mt-1.5 text-xs text-gray-500 dark:text-gray-400">error · red-500</span>
                    </div>
                    <div>
                        <div class="h-12 bg-yellow-400 rounded-lg"></div>
                        <span class="block mt-1.5 text-xs text-gray-500 dark:text-gray-400">warning · yellow-400</span>
                    </div>
                    <div>
                        <div class="h-12 bg-blue-500 rounded-lg"></div>
                        <span class="block mt-1.5 text-xs text-gray-500 dark:text-gray-400">info · blue-500</span>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Typography --}}
    {{-- ============================================================ --}}
    <section id="typography" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">02</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Typography') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Font family: Inter. The admin mostly lives between text-xs and text-2xl.') }}
        </p>

        <div class="p-6 mt-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <div class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ([
                    ['text-3xl font-semibold tracking-tight', 'text-3xl · semibold', 'Aura CMS'],
                    ['text-2xl font-semibold tracking-tight', 'text-2xl · semibold — page title', 'Page title'],
                    ['text-xl font-semibold', 'text-xl · semibold — section title', 'Section title'],
                    ['text-lg font-semibold', 'text-lg · semibold — modal title', 'Modal title'],
                    ['text-base font-medium', 'text-base · medium', 'The quick brown fox jumps over the lazy dog'],
                    ['text-sm', 'text-sm — body / inputs / table cells', 'The quick brown fox jumps over the lazy dog'],
                    ['text-xs', 'text-xs — captions / table headers / badges', 'The quick brown fox jumps over the lazy dog'],
                    ['text-2xs', 'text-2xs — compact sidebar headings', 'The quick brown fox jumps over the lazy dog'],
                ] as [$classes, $label, $sample])
                    <div class="flex flex-col gap-1 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-baseline sm:gap-6">
                        <span class="w-64 text-xs text-gray-400 shrink-0 dark:text-gray-500">{{ $label }}</span>
                        <span class="text-gray-900 dark:text-white {{ $classes }}">{{ $sample }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-6 mt-4 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Recurring text styles') }}</span>
            <div class="mt-4 space-y-4">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-64 text-xs text-gray-400 shrink-0 dark:text-gray-500">field label</span>
                    <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">Title <span class="text-red-500">*</span></span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-64 text-xs text-gray-400 shrink-0 dark:text-gray-500">table header</span>
                    <span class="text-xs font-semibold text-gray-500 dark:text-gray-400">CREATED AT</span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-64 text-xs text-gray-400 shrink-0 dark:text-gray-500">help text</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">A short hint that helps the user fill this field.</span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-64 text-xs text-gray-400 shrink-0 dark:text-gray-500">validation error</span>
                    <span class="text-sm font-semibold text-red-500">The title field is required.</span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-64 text-xs text-gray-400 shrink-0 dark:text-gray-500">sidebar heading</span>
                    <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400">Resources</span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-64 text-xs text-gray-400 shrink-0 dark:text-gray-500">link</span>
                    <a href="#typography" class="text-sm font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-400">Forgot your password?</a>
                </div>
            </div>
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Icons --}}
    {{-- ============================================================ --}}
    <section id="icons" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">03</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Icons') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            <code class="text-xs">&lt;x-aura::icon icon="…" size="xs|sm|base|lg" /&gt;</code>
        </p>

        <div class="grid grid-cols-3 gap-2 mt-6 sm:grid-cols-5 lg:grid-cols-8">
            @foreach ($icons as $icon)
                <div class="flex flex-col gap-2 items-center py-4 px-2 text-gray-600 bg-white rounded-lg ring-1 shadow-sm dark:text-gray-300 dark:bg-gray-800 ring-gray-950/5 dark:ring-white/10">
                    <x-aura::icon :icon="$icon" size="sm" />
                    <span class="text-xs text-gray-400 truncate max-w-full dark:text-gray-500">{{ $icon }}</span>
                </div>
            @endforeach
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Buttons --}}
    {{-- ============================================================ --}}
    <section id="buttons" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">04</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Buttons') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            <code class="text-xs">&lt;x-aura::button&gt;</code> {{ __('with variants as sub-components and sizes via the size prop.') }}
        </p>

        <div class="p-6 mt-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Variants') }}</span>
            <div class="flex flex-wrap gap-4 items-center mt-4">
                <div class="flex flex-col gap-2 items-center">
                    <x-aura::button>Default</x-aura::button>
                    <span class="text-xs text-gray-400">button</span>
                </div>
                <div class="flex flex-col gap-2 items-center">
                    <x-aura::button.primary>Primary</x-aura::button.primary>
                    <span class="text-xs text-gray-400">button.primary</span>
                </div>
                <div class="flex flex-col gap-2 items-center">
                    <x-aura::button.light>Light</x-aura::button.light>
                    <span class="text-xs text-gray-400">button.light</span>
                </div>
                <div class="flex flex-col gap-2 items-center">
                    <x-aura::button.border>Border</x-aura::button.border>
                    <span class="text-xs text-gray-400">button.border</span>
                </div>
                <div class="flex flex-col gap-2 items-center">
                    <x-aura::button.transparent>Transparent</x-aura::button.transparent>
                    <span class="text-xs text-gray-400">button.transparent</span>
                </div>
                <div class="flex flex-col gap-2 items-center">
                    <x-aura::button.danger>Danger</x-aura::button.danger>
                    <span class="text-xs text-gray-400">button.danger</span>
                </div>
                <div class="flex flex-col gap-2 items-center">
                    <x-aura::button.error>Error</x-aura::button.error>
                    <span class="text-xs text-gray-400">button.error</span>
                </div>
            </div>
        </div>

        <div class="p-6 mt-4 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Sizes') }}</span>
            <div class="flex flex-wrap gap-4 items-end mt-4">
                @foreach (['xs', 'sm', 'base', 'lg', 'xl'] as $size)
                    <div class="flex flex-col gap-2 items-center">
                        <x-aura::button :size="$size">Button</x-aura::button>
                        <span class="text-xs text-gray-400">{{ $size }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-6 mt-4 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('States & compositions') }}</span>
            <div class="flex flex-wrap gap-4 items-center mt-4">
                <div class="flex flex-col gap-2 items-center">
                    <x-aura::button>
                        <x-slot:icon><x-aura::icon icon="plus" size="sm" /></x-slot:icon>
                        <span>Create Post</span>
                    </x-aura::button>
                    <span class="text-xs text-gray-400">with icon</span>
                </div>
                <div class="flex flex-col gap-2 items-center">
                    <x-aura::button disabled>Disabled</x-aura::button>
                    <span class="text-xs text-gray-400">disabled</span>
                </div>
                <div class="flex flex-col gap-2 items-center">
                    <x-aura::button.border>
                        <x-aura::icon icon="dots" size="xs" />
                    </x-aura::button.border>
                    <span class="text-xs text-gray-400">icon only</span>
                </div>
                <div class="flex flex-col flex-1 gap-2 items-center min-w-48">
                    <x-aura::button block>Block button</x-aura::button>
                    <span class="text-xs text-gray-400">block</span>
                </div>
            </div>
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Forms --}}
    {{-- ============================================================ --}}
    <section id="forms" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">05</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Forms') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Raw inputs from components/input/*. Resource fields wrap these with label, help tooltip and validation error.') }}
        </p>

        <div class="grid grid-cols-1 gap-4 mt-6 lg:grid-cols-2">
            <div class="p-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Text inputs') }}</span>
                <div class="mt-4 space-y-5">
                    <x-aura::input.text label="Title" placeholder="My first post" />
                    <x-aura::input.text label="Disabled" value="Read only value" disabled />
                    <x-aura::input.text label="With prefix" prefix="https://" placeholder="example.com" />
                    <x-aura::input.text label="With suffix" suffix=".test" placeholder="aura-demo" />
                    <x-aura::input.text label="Size xs" size="xs" placeholder="Compact input" />

                    {{-- Field anatomy: label + input + error --}}
                    <div>
                        <x-aura::fields.label label="Slug *" />
                        <x-aura::input.text value="my first pöst!" />
                        <span class="text-sm font-semibold text-red-500 error">{{ __('The slug may only contain letters, numbers and dashes.') }}</span>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Input types') }}</span>
                <div class="mt-4 space-y-5">
                    <x-aura::input.email label="Email" placeholder="you@example.com" />
                    <x-aura::input.number label="Number" placeholder="42" />
                    <div class="grid grid-cols-2 gap-4">
                        <x-aura::input.text label="Date" type="date" />
                        <x-aura::input.text label="Time" type="time" />
                    </div>
                    <div>
                        <x-aura::fields.label label="Select" />
                        <x-aura::input.select
                            name="styleguide-select"
                            :options="['draft' => 'Draft', 'review' => 'In Review', 'published' => 'Published']"
                            selected="published"
                        />
                    </div>
                    <div>
                        <x-aura::fields.label label="Textarea" />
                        <x-aura::input.textarea placeholder="Write something…" rows="3"></x-aura::input.textarea>
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Checkboxes & radios') }}</span>
                <div class="mt-4 space-y-3">
                    <x-aura::input.checkbox label="Unchecked" />
                    <x-aura::input.checkbox label="Checked" checked />
                    <x-aura::input.checkbox label="Disabled" disabled />
                    <div class="pt-3 space-y-3 border-t border-gray-100 dark:border-white/5">
                        <x-aura::input.radio name="styleguide-radio" label="Option A" checked />
                        <x-aura::input.radio name="styleguide-radio" label="Option B" />
                        <x-aura::input.radio name="styleguide-radio" label="Disabled" disabled />
                    </div>
                </div>
            </div>

            <div class="p-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Toggles') }}</span>
                <div class="mt-4 space-y-4">
                    <x-aura::input.toggle wire:model="toggleOn" labelAfter="Enabled" />
                    <x-aura::input.toggle wire:model="toggleOff" labelAfter="Disabled" />
                </div>

                <div class="pt-4 mt-6 border-t border-gray-100 dark:border-white/5">
                    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Search input (table toolbar)') }}</span>
                    <div class="relative mt-4">
                        <div class="flex absolute inset-y-0 left-0 items-center pl-3 pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <input type="text"
                            class="block py-2 pl-9 pr-3 w-64 max-w-full text-sm text-gray-900 bg-white rounded-lg border-0 ring-1 shadow-xs transition appearance-none placeholder:text-gray-400 ring-gray-950/10 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/10 dark:placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 z-[1]"
                            placeholder="{{ __('Search for items') }}">
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Badges --}}
    {{-- ============================================================ --}}
    <section id="badges" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">06</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Badges & Pills') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Three badge shapes are in use: status pills (dot + ring), sidebar badges and trend pills (both rounded-full).') }}
        </p>

        <div class="p-6 mt-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Status pills (fields/status-index)') }}</span>
            <div class="flex flex-wrap gap-2 mt-4">
                @foreach ($statusPills as $label => $classes)
                    <span class="inline-flex items-center gap-x-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-gray-950/10 dark:ring-white/10 {{ $classes }}">
                        <svg class="size-1.5 shrink-0 fill-current opacity-70" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
                        {{ $label }}
                    </span>
                @endforeach
            </div>
        </div>

        <div class="p-6 mt-4 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Sidebar badges (navigation/item, light-sidebar color map)') }}</span>
            <div class="flex flex-wrap gap-2 mt-4">
                @foreach ($navBadgeColors as $color => $classes)
                    <span class="inline-flex items-center justify-center rounded-full px-1.5 py-0.5 min-w-[1.25rem] text-xs font-medium tabular-nums {{ $classes }}">{{ $color }}</span>
                @endforeach
            </div>
        </div>

        <div class="p-6 mt-4 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Trend pills (dashboard/stats)') }}</span>
            <div class="flex flex-wrap gap-2 items-center mt-4">
                <span class="inline-flex items-center gap-0.5 rounded-full bg-green-50 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">
                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7" /></svg>
                    12%
                </span>
                <span class="inline-flex items-center gap-0.5 rounded-full bg-red-50 dark:bg-red-900/40 px-2 py-0.5 text-xs font-medium text-red-700 dark:text-red-400">
                    <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M19 12l-7 7-7-7" /></svg>
                    4%
                </span>
                <span class="inline-flex items-center rounded-full bg-gray-100 dark:bg-white/10 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-300">neutral</span>
            </div>
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Cards & Widgets --}}
    {{-- ============================================================ --}}
    <section id="cards" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">07</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Cards & Widgets') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('.aura-card is the base surface. KPI stat cards below are the real dashboard/stats component with sample data.') }}
        </p>

        <div class="grid grid-cols-1 gap-4 mt-6 sm:grid-cols-2">
            <div class="aura-card">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">.aura-card</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">p-5, rounded-xl, shadow-sm, ring-1 ring-gray-950/5.</p>
            </div>
            <div class="flex items-center aura-card-small">
                <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold text-gray-900 dark:text-white">.aura-card-small</span> — p-2, rounded-lg, ring-gray-950/5.</p>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4 mt-4">
            <x-aura::dashboard.stats :stats="$stats" />
        </div>

        <div class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-2">
            <div class="aura-card">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('KPI value (widgets/value)') }}</span>
                <div class="flex gap-2 items-baseline mt-2">
                    <span class="text-3xl font-semibold tracking-tight text-gray-900 dark:text-white">2,741</span>
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-green-50 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7" /></svg>
                        8.2%
                    </span>
                </div>
            </div>
            <div class="aura-card">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Loading skeleton') }}</span>
                <div class="mt-3 animate-pulse">
                    <div class="w-24 h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
                    <div class="mt-3 w-32 h-8 bg-gray-200 rounded dark:bg-gray-700"></div>
                </div>
            </div>
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Table --}}
    {{-- ============================================================ --}}
    <section id="table" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">08</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Table') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('The resource index table. Headings use the real table.heading component; rows are a faithful specimen of table/row (the live rows are Livewire-bound). Second row shows the selected state.') }}
        </p>

        {{-- Filter tabs (specimen of table/filter-tabs) --}}
        <div class="flex flex-wrap gap-1 items-center p-1 mt-6 max-w-full bg-gray-50 rounded-lg dark:bg-white/5 w-fit">
            <button type="button" class="flex items-center px-3 py-1.5 space-x-2 text-sm font-medium text-gray-900 bg-white rounded-md ring-1 shadow-sm transition cursor-pointer ring-gray-950/5 dark:bg-gray-700 dark:text-white dark:ring-white/10">{{ __('All') }}</button>
            <button type="button" class="flex items-center px-3 py-1.5 space-x-2 text-sm text-gray-500 rounded-md transition cursor-pointer hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ __('My drafts') }}</button>
            <button type="button" class="flex items-center px-3 py-1.5 space-x-2 text-sm text-gray-500 rounded-md transition cursor-pointer hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ __('Published') }}</button>
        </div>

        {{-- Table card (specimen of table/list-view) --}}
        <div class="overflow-hidden mt-4 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50/80 dark:bg-white/[0.03] border-b border-gray-200/80 dark:border-white/10">
                            <th class="py-2.5 pr-0 pl-6 w-px">
                                <x-aura::input.checkbox hideLabel label="Select all" />
                            </th>
                            <x-aura::table.heading sortable direction="asc">{{ __('Title') }}</x-aura::table.heading>
                            <x-aura::table.heading sortable>{{ __('Status') }}</x-aura::table.heading>
                            <x-aura::table.heading>{{ __('Author') }}</x-aura::table.heading>
                            <x-aura::table.heading sortable direction="desc">{{ __('Created at') }}</x-aura::table.heading>
                            <th class="px-3 py-2.5"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($tableRows as $row)
                            <tr class="transition-colors duration-150 ease-in-out cm-table-row hover:bg-gray-50/80 dark:hover:bg-white/[0.04] {{ $row['selected'] ? 'bg-primary-50/60 dark:bg-primary-500/10' : '' }}">
                                <td class="py-3 pr-0 pl-6">
                                    <x-aura::input.checkbox hideLabel :label="'Row '.$row['id']" :checked="$row['selected']" />
                                </td>
                                <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $row['title'] }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="inline-flex items-center gap-x-1.5 whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ring-gray-950/10 dark:ring-white/10 {{ $row['statusClass'] }}">
                                        <svg class="size-1.5 shrink-0 fill-current opacity-70" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['author'] }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['date'] }}</td>
                                <td class="px-3 py-2 text-right">
                                    <x-aura::dropdown align="right" width="48">
                                        <x-slot:trigger>
                                            <button type="button" class="p-1.5 text-gray-400 rounded-md transition hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-white/10">
                                                <x-aura::icon icon="dots" size="xs" />
                                            </button>
                                        </x-slot:trigger>
                                        <x-slot:content>
                                            <x-aura::dropdown-link href="#table">{{ __('View') }}</x-aura::dropdown-link>
                                            <x-aura::dropdown-link href="#table">{{ __('Edit') }}</x-aura::dropdown-link>
                                            <x-aura::dropdown-link href="#table">{{ __('Delete') }}</x-aura::dropdown-link>
                                        </x-slot:content>
                                    </x-aura::dropdown>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination (specimen of table/pagination) --}}
            <div class="flex justify-between items-center px-6 py-3 border-t border-gray-100 dark:border-white/5">
                <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Showing') }} <span class="font-medium text-gray-900 dark:text-white">1</span> {{ __('to') }} <span class="font-medium text-gray-900 dark:text-white">10</span> {{ __('of') }} <span class="font-medium text-gray-900 dark:text-white">42</span> {{ __('results') }}</span>
                <div class="flex gap-1">
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-gray-300 dark:text-gray-600">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.79 5.23a.75.75 0 0 1-.02 1.06L8.832 10l3.938 3.71a.75.75 0 1 1-1.04 1.08l-4.5-4.25a.75.75 0 0 1 0-1.08l4.5-4.25a.75.75 0 0 1 1.06.02Z" clip-rule="evenodd" /></svg>
                    </span>
                    <span class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-sm font-medium rounded-lg bg-white text-gray-900 shadow-xs ring-1 ring-gray-950/10 dark:bg-white/10 dark:text-white dark:ring-white/10">1</span>
                    <button type="button" class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-950/5 dark:text-gray-400 dark:hover:bg-white/5 transition-colors duration-150">2</button>
                    <button type="button" class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-950/5 dark:text-gray-400 dark:hover:bg-white/5 transition-colors duration-150">3</button>
                    <button type="button" class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-950/5 dark:text-gray-400 dark:hover:bg-white/5 transition-colors duration-150">4</button>
                    <button type="button" class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-gray-600 hover:bg-gray-950/5 dark:text-gray-400 dark:hover:bg-white/5 transition-colors duration-150">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Empty state (specimen of table/list-view) --}}
        <div class="overflow-hidden mt-4 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <div class="flex flex-col items-center px-6 py-16 text-center">
                <div class="p-3 bg-gray-50 rounded-full dark:bg-gray-700/50">
                    <svg class="w-6 h-6 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                    </svg>
                </div>
                <h3 class="mt-3 text-sm font-medium text-gray-900 dark:text-white">{{ __('No entries available') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Entries you create will show up here.') }}</p>
            </div>
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Sidebar --}}
    {{-- ============================================================ --}}
    <section id="navigation" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">09</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Sidebar') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Real navigation.item / navigation.heading components inside the three sidebar themes (config: sidebar-type). The first item shows the active state.') }}
        </p>

        <div class="grid grid-cols-1 gap-4 mt-6 md:grid-cols-3">
            @foreach ([
                'primary' => 'sidebar-type: primary',
                'light' => 'sidebar-type: light',
                'dark' => 'sidebar-type: dark',
            ] as $type => $label)
                <div>
                    <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ $label }}</span>
                    <div class="mt-3 open-sidebar aura-sidebar-type-{{ $type }} aura-sidebar-darkmode-type-{{ $type }}">
                        <div class="p-3 rounded-xl border aura-sidebar-bg">
                            <x-aura::navigation.heading>{{ __('Resources') }}</x-aura::navigation.heading>

                            <div class="mt-2 space-y-1">
                                <x-aura::navigation.item href="#navigation" class="is-active">
                                    <x-aura::icon icon="dashboard" size="base" />
                                    <span>{{ __('Dashboard') }}</span>
                                </x-aura::navigation.item>

                                <x-aura::navigation.item href="#navigation">
                                    <x-aura::icon icon="collection" size="base" />
                                    <span>{{ __('Posts') }}</span>
                                </x-aura::navigation.item>

                                <x-aura::navigation.item href="#navigation" badge="12" badgeColor="{{ $type === 'primary' ? 'gray' : 'primary' }}">
                                    <x-aura::icon icon="media" size="base" />
                                    <span>{{ __('Media') }}</span>
                                </x-aura::navigation.item>

                                <x-aura::navigation.item href="#navigation">
                                    <x-aura::icon icon="user" size="base" />
                                    <span>{{ __('Users') }}</span>
                                </x-aura::navigation.item>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Dropdowns & Tooltips --}}
    {{-- ============================================================ --}}
    <section id="menus" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">10</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Dropdowns & Tooltips') }}</h2>

        <div class="grid grid-cols-1 gap-4 mt-6 sm:grid-cols-2">
            <div class="p-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Dropdown') }}</span>
                <div class="mt-4">
                    <x-aura::dropdown align="left" width="48">
                        <x-slot:trigger>
                            <x-aura::button.border>
                                <span class="flex items-center space-x-2">
                                    <span>{{ __('Actions') }}</span>
                                    <x-aura::icon icon="chevron-down" size="xs" />
                                </span>
                            </x-aura::button.border>
                        </x-slot:trigger>
                        <x-slot:content>
                            <x-aura::dropdown-link href="#menus">{{ __('Edit') }}</x-aura::dropdown-link>
                            <x-aura::dropdown-link href="#menus">{{ __('Duplicate') }}</x-aura::dropdown-link>
                            <x-aura::dropdown-link href="#menus">{{ __('Delete') }}</x-aura::dropdown-link>
                        </x-slot:content>
                    </x-aura::dropdown>
                </div>
            </div>

            <div class="p-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Tooltip (tippy)') }}</span>
                <div class="flex gap-4 mt-4">
                    <x-aura::tippy text="Tooltip on top" position="top">
                        <x-aura::button.border>{{ __('Hover me') }}</x-aura::button.border>
                    </x-aura::tippy>
                    <x-aura::tippy text="More information about this field" position="right">
                        <span class="inline-flex items-center text-gray-400 cursor-help">
                            <x-aura::icon icon="info" size="sm" />
                        </span>
                    </x-aura::tippy>
                </div>
            </div>
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Modals & Slide-overs --}}
    {{-- ============================================================ --}}
    <section id="overlays" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">11</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Modals & Slide-overs') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Alpine dialog components — these open the real overlays.') }}
        </p>

        <div class="flex flex-wrap gap-4 p-6 mt-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <x-aura::dialog>
                <x-aura::dialog.open>
                    <x-aura::button.border>{{ __('Open modal') }}</x-aura::button.border>
                </x-aura::dialog.open>
                <x-aura::dialog.panel>
                    <x-aura::dialog.title>{{ __('Delete post') }}</x-aura::dialog.title>
                    <p class="text-sm text-gray-600 dark:text-gray-300">{{ __('Are you sure you want to delete this post? This action cannot be undone.') }}</p>
                    <x-slot:footer>
                        <x-aura::dialog.close>
                            <x-aura::button.transparent>{{ __('Cancel') }}</x-aura::button.transparent>
                        </x-aura::dialog.close>
                        <x-aura::button.error>{{ __('Delete') }}</x-aura::button.error>
                    </x-slot:footer>
                </x-aura::dialog.panel>
            </x-aura::dialog>

            <x-aura::dialog>
                <x-aura::dialog.open>
                    <x-aura::button.border>{{ __('Open slide-over') }}</x-aura::button.border>
                </x-aura::dialog.open>
                <x-aura::dialog.slideover>
                    <x-aura::dialog.title>{{ __('Edit details') }}</x-aura::dialog.title>
                    <div class="space-y-5">
                        <x-aura::input.text label="Title" placeholder="My first post" />
                        <div>
                            <x-aura::fields.label label="Status" />
                            <x-aura::input.select name="styleguide-slideover-select" :options="['draft' => 'Draft', 'published' => 'Published']" selected="draft" />
                        </div>
                    </div>
                </x-aura::dialog.slideover>
            </x-aura::dialog>
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Notifications --}}
    {{-- ============================================================ --}}
    <section id="notifications" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">12</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Notifications') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Toasts are dispatched via the notify event and rendered by the notification component in the layout. The buttons fire real toasts; below is a static specimen of the toast card.') }}
        </p>

        <div class="flex flex-wrap gap-4 p-6 mt-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <x-aura::button.light x-data x-on:click="$dispatch('notify', { type: 'success', message: 'Changes saved successfully.' })">
                {{ __('Success toast') }}
            </x-aura::button.light>
            <x-aura::button.danger x-data x-on:click="$dispatch('notify', { type: 'error', message: 'Something went wrong.' })">
                {{ __('Error toast') }}
            </x-aura::button.danger>
        </div>

        <div class="p-6 mt-4 bg-gray-50 rounded-xl ring-1 dark:bg-white/5 ring-gray-950/5 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Toast card (static specimen)') }}</span>
            <div class="mt-4 w-full max-w-sm bg-white rounded-xl shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                <div class="overflow-hidden rounded-xl">
                    <div class="p-4">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="flex justify-center items-center w-6 h-6 text-green-600 bg-green-100 rounded-full dark:bg-green-500/15 dark:text-green-400">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 13L9.5 17.5L19 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                            </div>
                            <div class="flex-1 ml-3 w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ __('Changes saved successfully.') }}</p>
                            </div>
                            <div class="flex flex-shrink-0 ml-4">
                                <button class="inline-flex p-1 -m-1 text-gray-400 rounded-md transition duration-150 ease-out hover:bg-gray-950/5 hover:text-gray-600 dark:text-gray-500 dark:hover:bg-white/10 dark:hover:text-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                                    <span class="sr-only">{{ __('Dismiss notification') }}</span>
                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="relative h-0.5 bg-transparent">
                        <div class="absolute left-0 h-0.5 bg-primary-500/80 dark:bg-primary-400/80" style="width: 65%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Feedback --}}
    {{-- ============================================================ --}}
    <section id="feedback" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">13</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Feedback & Loading') }}</h2>

        <div class="grid grid-cols-1 gap-4 mt-6 sm:grid-cols-3">
            <div class="p-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Spinner') }}</span>
                <div class="flex gap-4 items-center mt-4 text-primary-600 dark:text-primary-400">
                    <x-aura::icon icon="loading" size="sm" />
                    <x-aura::icon icon="loading" size="base" />
                    <x-aura::icon icon="loading" size="lg" />
                </div>
            </div>

            <div class="p-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Skeleton') }}</span>
                <div class="mt-4 space-y-3 animate-pulse">
                    <div class="w-3/4 h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
                    <div class="w-full h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
                    <div class="w-1/2 h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
                </div>
            </div>

            <div class="p-6 bg-white rounded-xl ring-1 shadow-sm dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Inline alert') }}</span>
                <div class="p-4 mt-4 text-sm text-gray-700 bg-gray-100 rounded-lg border border-gray-200 dark:bg-white/5 dark:text-gray-300 dark:border-white/10">
                    {{ __('Plugins are managed via Composer.') }} <code class="px-2 py-1 text-xs font-semibold text-gray-800 bg-white rounded dark:bg-gray-900 dark:text-gray-200">composer require vendor/plugin</code>
                </div>
            </div>
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Auth --}}
    {{-- ============================================================ --}}
    <section id="auth" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">14</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Authentication') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('The login form composition, as rendered on /login (same components on register, reset password and 2FA).') }}
        </p>

        <div class="flex justify-center p-8 mt-6 bg-gray-50 rounded-xl ring-1 dark:bg-gray-900 ring-gray-950/5 dark:ring-white/10 sm:p-12">
            <div class="w-full max-w-md rounded-2xl bg-white/95 backdrop-blur-xl ring-1 ring-gray-950/5 shadow-xl shadow-gray-950/[0.08] dark:bg-gray-800/90 dark:ring-white/10 dark:shadow-black/40 p-8 sm:p-10">
                <h3 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Welcome back') }}</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Login to your account to continue.') }}</p>

                <div class="mt-8 space-y-5">
                    <x-aura::input.email label="Email" placeholder="you@example.com" />
                    <x-aura::input.text label="Password" type="password" placeholder="••••••••" />

                    <div class="flex justify-between items-center">
                        <x-aura::input.checkbox label="{{ __('Remember me') }}" />
                        <a href="#auth" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">{{ __('Forgot your password?') }}</a>
                    </div>

                    <div class="pt-1">
                        <x-aura::button block>{{ __('Log in') }}</x-aura::button>
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>
