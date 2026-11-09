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
        'option', 'permission', 'plus', 'role', 'search', 'sparkles', 'table', 'team', 'team-invitation', 'template-plain',
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

    $stats = [
        [
            'name' => 'Posts',
            'total' => 1284,
            'current' => 86,
            'previous' => 74,
            'delta' => 12,
            'deltaPercent' => 16,
            'url' => '#cards',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>',
            'series' => [2, 0, 4, 3, 0, 1, 5, 2, 0, 3, 4, 6, 1, 0, 2, 3, 5, 0, 4, 2, 6, 3, 1, 0, 5, 4, 2, 7, 3, 4],
        ],
        [
            // No previous period and a sparse month: no delta pill, and the row draws no trend line.
            'name' => 'Users',
            'total' => 342,
            'current' => 3,
            'previous' => 0,
            'url' => '#cards',
            'icon' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>',
            'series' => [0, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            'showSparkline' => false,
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
            {{ __('Inter 4 variable (bundled with the package under the SIL OFL, Latin subset, optical sizing on) is the default typeface; the theme font option replaces it. Four steps carry the admin: page title, section title, body, meta. Weight 600 is reserved for titles and the leading table column, 500 for labels, buttons and nav, 400 for everything else. Numerals are tabular everywhere (body-level font-variant-numeric).') }}
        </p>

        <div class="p-6 mt-6 bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Type scale') }}</span>
            <div class="mt-4 divide-y divide-gray-100 dark:divide-white/5">
                @foreach ([
                    ['aura-page-title', '.aura-page-title — 24px · 600 · -0.02em (--aura-tracking-display)', 'Northwind Software'],
                    ['aura-page-title', '.aura-page-title + .aura-page-count — list title with its record count', 'Companies <span class="aura-page-count">14</span>'],
                    ['aura-section-title', '.aura-section-title — 14px · 600', 'Contacts'],
                    ['aura-eyebrow', '.aura-eyebrow — 12px · 500 · gray-500 (resource label, view labels)', 'Company'],
                    ['aura-body', '.aura-body — 14px · 400 · gray-700', 'The quick brown fox jumps over the lazy dog'],
                    ['aura-meta', '.aura-meta — 12px · 400 · gray-500', 'Company · 2 minutes ago'],
                    ['aura-table-header', '.aura-table-header — 12px · 500 · gray-500, sentence case', 'Created at'],
                    ['aura-stat-value', '.aura-stat-value — 30px · 600 · -0.02em · tabular', '84,500'],
                    ['aura-record-meta !mt-0', '.aura-record-meta — 12px · gray-500, times and author in gray-700', 'Created <time>Nov 3, 2026</time> by <span data-record-meta-author>Bajram Emini</span> <span class="aura-record-meta-sep">&middot;</span> Updated <time>4 hours ago</time>'],
                ] as [$classes, $label, $sample])
                    <div class="flex flex-col gap-1 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-baseline sm:gap-6">
                        <span class="w-80 text-xs text-gray-400 shrink-0 dark:text-gray-500">{{ $label }}</span>
                        <span class="text-gray-900 dark:text-white {{ $classes }}">{!! $sample !!}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="p-6 mt-4 bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Page header pattern') }}</span>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Record pages: the record title is the H1, the eyebrow names the resource on the view page ("Deal") and the task on the edit page ("Edit deal"). Create pages have no eyebrow; the H1 says "New deal" in sentence case. Every form page has a ghost Cancel left of the primary Save (on edit, Cancel returns to the record); the view page has no primary action, its Edit is a secondary button with its pencil. .aura-record-header puts the actions right of the title from sm; on phones the row dissolves into .aura-record-block and the header has no action band of its own: the view page\'s Edit becomes a 36px pencil (button size="icon", aria-label and tooltip "Edit") in the title row, and forms move Cancel and Save into .aura-form-bar, a bar on the bottom edge (safe-area aware) with a ghost Cancel and a full-width primary Save. Other resource actions stay as a right-aligned row under the title or facts strip (.aura-record-header-actions-sm hides a row that would be empty on phones). Phones also replace the breadcrumb trail with one 14px back link above the title, built from the trail\'s last link ("‹ Companies" on a record, "‹ Northwind Software" on its edit form). A simple form caps breadcrumb, header and fields at one width, so Save sits at the edge of the form. The view page promotes key fields into a facts strip under the title (.aura-record-facts: by default the first Number field, large and tabular, and the first Status field as its tag; a resource overrides the pick with recordHeaderFields()). One primary button per screen; everything else is secondary or ghost.') }}</p>
            <div class="mt-4 aura-record-block">
            <div class="aura-record-header">
                <div class="flex-1 min-w-0">
                    <p class="aura-eyebrow">{{ __('Edit deal') }}</p>
                    <h3 class="mt-0.5 break-words aura-page-title line-clamp-2 sm:line-clamp-1">Northwind ERP Rollout 2026</h3>
                </div>
                <div class="aura-record-header-actions">
                    <x-aura::button.transparent>{{ __('Cancel') }}</x-aura::button.transparent>
                    <x-aura::button.primary>{{ __('Save') }}</x-aura::button.primary>
                </div>
            </div>
            <div class="aura-record-facts sm:mt-4">
                <div class="aura-record-fact">
                    <span class="aura-eyebrow">Amount</span>
                    <span class="aura-record-fact-number">CHF 84,500</span>
                </div>
                <div class="aura-record-fact">
                    <span class="aura-eyebrow">Stage</span>
                    <span class="aura-record-fact-value">
                        <span class="aura-tag aura-tag-status bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">
                            <svg class="aura-tag-dot" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
                            Proposal
                        </span>
                    </span>
                </div>
                <div class="aura-record-fact">
                    <span class="aura-eyebrow">Owner</span>
                    <span class="aura-record-fact-value"><a href="#typography" class="aura-record-link"><x-aura::avatar name="Martina Keller" shape="circle" size="xs" /><span class="aura-record-link-title">Martina Keller</span></a></span>
                </div>
            </div>
            </div>
            <div class="flex gap-4 justify-between items-center pt-4 mt-4 border-t border-gray-100 dark:border-white/5">
                <h3 class="aura-section-title">Contacts <span class="aura-section-count">12</span></h3>
                <x-aura::button.border size="sm">
                    <x-slot:icon><x-aura::icon icon="plus" /></x-slot:icon>
                    {{ __('Create') }}
                </x-aura::button.border>
            </div>
        </div>

        <div class="p-6 mt-4 bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Recurring text styles') }}</span>
            <div class="mt-4 space-y-4">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:gap-6">
                    <span class="w-80 text-xs text-gray-400 shrink-0 dark:text-gray-500">field label (form) + help marker</span>
                    <span class="flex gap-1.5 items-center">
                        <x-aura::fields.label label="Website" required class="!my-0" />
                        <x-aura::icon icon="info" class="flex" iconClass="h-3.5 w-3.5 text-gray-400 dark:text-gray-500" />
                    </span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-80 text-xs text-gray-400 shrink-0 dark:text-gray-500">field label (view) + value — 14px gray-500 beside 14px gray-900</span>
                    <span class="flex gap-6 items-baseline"><span class="w-28 text-sm text-gray-500 dark:text-gray-400">Website</span><span class="text-sm text-gray-900 dark:text-gray-100"><a href="#typography" class="aura-link">northwindsoftware.com</a></span></span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-80 text-xs text-gray-400 shrink-0 dark:text-gray-500">URL value (.aura-link) — no underline at rest; underline and an external mark on hover</span>
                    <span class="text-sm"><a href="#typography" class="aura-link">atlascomponents.de</a></span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-80 text-xs text-gray-400 shrink-0 dark:text-gray-500">Email value (.aura-link, mailto) — same link, no external mark; long values wrap in the details column</span>
                    <span class="text-sm"><a href="mailto:martina.keller@northwindsoftware.com" class="aura-link">martina.keller@northwindsoftware.com</a></span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-80 text-xs text-gray-400 shrink-0 dark:text-gray-500">empty value (tables and views)</span>
                    <span class="text-sm text-gray-400 dark:text-gray-500">–</span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-80 text-xs text-gray-400 shrink-0 dark:text-gray-500">help text</span>
                    <span class="text-sm text-gray-500 dark:text-gray-400">A short hint that helps the user fill this field.</span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-80 text-xs text-gray-400 shrink-0 dark:text-gray-500">validation error</span>
                    <span class="text-sm text-red-600 dark:text-red-400">The title field is required.</span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-80 text-xs text-gray-400 shrink-0 dark:text-gray-500">sidebar group label — 12px · 500 · sentence case · gray-500</span>
                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Resources</span>
                </div>
                <div class="flex flex-col gap-1 sm:flex-row sm:items-baseline sm:gap-6">
                    <span class="w-80 text-xs text-gray-400 shrink-0 dark:text-gray-500">link</span>
                    <a href="#typography" class="text-sm font-medium text-primary-600 hover:text-primary-700 hover:underline dark:text-primary-400">Forgot your password?</a>
                </div>
            </div>
        </div>

        <div class="p-6 mt-4 bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Where the primary color appears') }}</span>
            <ul class="grid grid-cols-1 gap-x-6 gap-y-1 mt-3 text-sm text-gray-600 sm:grid-cols-2 dark:text-gray-300">
                <li>{{ __('Active navigation item (on the dark sidebar: its icon only)') }}</li>
                <li>{{ __('The one primary button per screen (create and save; a view page has none)') }}</li>
                <li>{{ __('Text links in auth and settings copy') }}</li>
                <li>{{ __('Focus rings and selected rows') }}</li>
                <li>{{ __('Dashboard trend lines') }}</li>
                <li>{{ __('Nothing else: badges, icons and surfaces stay in the gray ramp') }}</li>
            </ul>
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
                <div class="flex flex-col gap-2 items-center py-4 px-2 text-gray-600 bg-white rounded-lg ring-1 shadow-sm dark:text-gray-300 dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
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
            <code class="text-xs">&lt;x-aura::button&gt;</code> {{ __('with variants as sub-components and sizes via the size prop. Hierarchy: primary (solid, one per screen), secondary (button.border: white, hairline ring), ghost (button.transparent). Fixed heights: xs 28, sm 32, base 36, lg 40, xl 48px.') }}
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
                    <x-aura::button.border size="icon" aria-label="{{ __('More') }}">
                        <x-aura::icon.dots class="size-4" />
                    </x-aura::button.border>
                    <span class="text-xs text-gray-400">icon only (size="icon", 36px)</span>
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
            {{ __('Raw inputs from components/input/*. Resource fields wrap these with label, help tooltip and validation error. Every control (input, select, relation picker, status) is 36px tall with 14px text, 16px on phones so iOS does not zoom. Empty selects show a gray placeholder from the field name and use one chevron.') }}
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
                        <x-aura::fields.label label="Select, empty" />
                        <x-aura::input.select
                            name="styleguide-select-empty"
                            class="[&:has(option[value='']:checked)]:text-gray-400 dark:[&:has(option[value='']:checked)]:text-gray-500 [&_option]:text-gray-900 dark:[&_option]:text-gray-100"
                            placeholder="Select company…"
                            :options="['northwind' => 'Northwind Software', 'atlas' => 'Atlas Components']"
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
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Toggles (36x20, the Boolean field uses the same switch)') }}</span>
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
                            class="block h-9 py-2 pl-9 pr-3 w-64 max-w-full text-sm text-gray-900 bg-white rounded-lg border-0 ring-1 shadow-xs transition appearance-none placeholder:text-gray-400 ring-gray-950/10 dark:bg-gray-800 dark:text-gray-100 dark:ring-white/10 dark:placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 z-[1]"
                            placeholder="{{ __('Search :resources…', ['resources' => 'companies']) }}">
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
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Tags, Avatars & Pills') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('One tag family for enum values: 22px high, 12px medium, the same radius and padding. Neutral for option values, tinted for an option that carries a color (a Select category at -50/-700 without a dot, a Status state at -100/-800 with a dot). A tinted tag\'s ring is its own hue at -600/20% (dark -400/30%), keyed on the text color class the option names, so a yellow state gets a yellow ring and the option only names fill and text. A relation is not a tag: it reads as the related record\'s name, 14px gray-900 with its 20px avatar (both initials at 10px, the row avatar one step smaller) in front, underlined on hover, so a row with three relations stays calm. Entity avatars put initials on a tint derived from the name (square for companies and things, circle for people) and sit before record names in tables and the record header. Small rounded-full pills remain for sidebar counts and trends.') }}
        </p>

        <div class="p-6 mt-6 bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Tag family (.aura-tag): neutral, select color (.aura-tag-status + option color), status (+ dot); relation as a record link (.aura-record-link)') }}</span>
            <div class="flex flex-wrap gap-1.5 items-center mt-4">
                @foreach (['Software', 'Retail', 'Finance', 'Other'] as $tag)
                    <span class="aura-tag">{{ $tag }}</span>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-1.5 items-center mt-3">
                @foreach ([
                    'Software' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
                    'Retail' => 'bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-300',
                    'Finance' => 'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-300',
                    'Healthcare' => 'bg-pink-50 text-pink-700 dark:bg-pink-500/10 dark:text-pink-300',
                    'Education' => 'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-300',
                ] as $label => $classes)
                    <span class="aura-tag aura-tag-status {{ $classes }}">{{ $label }}</span>
                @endforeach
                <span class="aura-tag">Other</span>
            </div>
            {{-- Specimen of fields/select when its options name colors: the trigger shows the selected option's tag. --}}
            <div class="mt-4 max-w-56">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Select with colored options') }}</span>
                <div class="flex relative items-center pr-9 pl-2 mt-2 w-full h-9 bg-white rounded-lg ring-1 shadow-xs ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10">
                    <span class="aura-tag aura-tag-status bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300">High</span>
                    <span class="flex absolute inset-y-0 right-0 items-center pr-2.5" aria-hidden="true">
                        <svg class="text-gray-400 size-4 dark:text-gray-500" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4.25 6.25 8 10l3.75-3.75" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </span>
                </div>
            </div>
            <div class="flex flex-wrap gap-1.5 items-center mt-3">
                @foreach ($statusPills as $label => $classes)
                    <span class="aura-tag aura-tag-status {{ $classes }}">
                        <svg class="aura-tag-dot" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
                        {{ $label }}
                    </span>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-6 items-center mt-4">
                <a href="#badges" class="aura-record-link">
                    <x-aura::avatar name="Northwind Software" shape="square" size="xs" />
                    <span class="aura-record-link-title">Northwind Software</span>
                </a>
                <a href="#badges" class="aura-record-link">
                    <x-aura::avatar name="Martina Keller" shape="circle" size="xs" />
                    <span class="aura-record-link-title">Martina Keller</span>
                </a>
                <a href="#badges" class="aura-record-link">
                    <span class="aura-record-link-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg></span>
                    <span class="aura-record-link-title">{{ __('Icon fallback') }}</span>
                </a>
            </div>
            <div class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                {{-- .aura-code: a path or command inside running text (plugins page, AI settings). --}}
                Run <code class="aura-code">php artisan config:cache</code> after changing <code class="aura-code">config/ai.php</code>.
            </div>
        </div>

        <div class="p-6 mt-4 bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Entity avatars (x-aura::avatar): sizes xs 20 (relation links, 10px initials), sm 24 (table rows, 11px initials), md 28, lg 40; tone from the name over 8 families the tags do not use (indigo, purple, fuchsia, pink, rose, violet, lime, cyan)') }}</span>
            <div class="flex flex-wrap gap-4 items-center mt-4">
                @foreach (['xs', 'sm', 'md', 'lg'] as $size)
                    <x-aura::avatar name="Atlas Components" shape="square" :size="$size" />
                @endforeach
                <span class="w-px h-6 bg-gray-200 dark:bg-white/10" aria-hidden="true"></span>
                @foreach (['xs', 'sm', 'md', 'lg'] as $size)
                    <x-aura::avatar name="Martina Keller" shape="circle" :size="$size" />
                @endforeach
            </div>
            <div class="flex flex-wrap gap-2 items-center mt-4">
                @foreach (\Aura\Base\Resource::avatarTones() as $tone)
                    <x-aura::avatar :name="Str::title($tone)" shape="square" size="md" :tone="$tone" title="{{ $tone }}" />
                @endforeach
            </div>
            <div class="flex gap-3 items-center mt-5">
                <x-aura::avatar name="Northwind Software" shape="square" size="lg" />
                <div class="min-w-0">
                    <p class="aura-eyebrow">Company</p>
                    <h3 class="mt-0.5 truncate aura-page-title">Northwind Software</h3>
                </div>
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
            {{ __('.aura-card is the base surface, used for forms and the few boxed surfaces: stat cards and related-record cards. Lists, record details and dashboard sections are unboxed under a hairline title. Stat cards below are the real dashboard/stats component with sample data; every card has the same anatomy: label and icon, the total with a 30-day trend beside it (the running total of new records as a line, the one place a card carries primary, a row decision: drawn on all cards when every card had records on at least three days, otherwise on none, as in this sample), then the new records in the last 30 days and the change against the 30 days before as a pill. A card without a previous period shows no pill. Phones show a 2x2 grid without the trend.') }}
        </p>

        <div class="grid grid-cols-1 gap-4 mt-6 sm:grid-cols-2">
            <div class="aura-card">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">.aura-card</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">p-5, rounded-xl (--aura-panel-radius), shadow-xs, ring-1 ring-gray-950/10. Dark: bg-gray-800, ring-white/10. Flat surfaces, one hairline, no stacked shadows.</p>
            </div>
            <div class="flex items-center aura-card-small">
                <p class="text-sm text-gray-500 dark:text-gray-400"><span class="font-semibold text-gray-900 dark:text-white">.aura-card-small</span> — p-2, rounded-lg, ring-gray-950/10.</p>
            </div>
        </div>

        <div class="grid grid-cols-12 gap-4 mt-4">
            <x-aura::dashboard.stats :stats="$stats" />
        </div>

        <div class="p-6 mt-4 bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Record page: details section (.aura-record-section) and related-record cards (x-aura::resource.record-card)') }}</span>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Every view panel renders as a titled section of label/value rows with hairlines; a single panel is titled "Details". From xl the sections sit in a 25rem (400px) column with the related records on the right: embedded lists, or, for a record without lists, one card per BelongsTo relation with the related record and up to three of its own list values. The column closes with the record meta line (.aura-record-meta, resource/record-meta): created date, author when the record has one, and the last update once it differs.') }}</p>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Right rail: when a record has a narrow panel (style width 33% or less, the sidebar card of its form) next to other values, the view page mirrors the form: from lg the other sections fill the main column and the narrow panels stack in a 20rem rail on the right (.aura-record-rail, 6.5rem label column), with the meta line under them. Relations in the rail stay inline record links instead of cards. On phones the rail follows the main column.') }}</p>
            <div class="grid grid-cols-1 gap-8 mt-4 lg:grid-cols-[minmax(0,25rem)_minmax(0,1fr)]">
                <div class="aura-record-section !mb-0">
                    <h3 class="aura-record-section-title">{{ __('Details') }}</h3>
                    <div class="aura-record-section-body">
                        <div>
                            @foreach ([
                                ['Amount', '<span class="tabular-nums">CHF 84,500</span>'],
                                ['Stage', '<span class="aura-tag aura-tag-status bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300"><svg class="aura-tag-dot" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>Proposal</span>'],
                                ['Company', '<a href="#cards" class="aura-record-link"><span class="aura-avatar aura-avatar-xs aura-avatar-square aura-avatar-tone-teal" aria-hidden="true">NS</span><span class="aura-record-link-title">Northwind Software</span></a>'],
                                ['Phone', '<span class="text-gray-400 dark:text-gray-500">–</span>'],
                            ] as [$label, $value])
                                <div class="px-4">
                                    <div class="py-2 sm:grid sm:grid-cols-[7rem_minmax(0,1fr)] sm:gap-x-3 sm:items-baseline aura-view-row">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</span>
                                        <span class="text-sm text-gray-900 dark:text-gray-100">{!! $value !!}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <p class="aura-record-meta px-4">
                        <span>Created <time datetime="2026-11-03T09:14:00+01:00" title="Tuesday, November 3, 2026 9:14 AM">Nov 3, 2026</time> by <span data-record-meta-author>Bajram Emini</span></span>
                        <span class="aura-record-meta-sep" aria-hidden="true">&middot;</span>
                        <span>Updated <time datetime="2026-11-08T12:40:00+01:00" title="Sunday, November 8, 2026 12:40 PM">4 hours ago</time></span>
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="aura-record-card">
                        <div class="flex gap-3 items-center">
                            <x-aura::avatar name="Northwind Software" shape="square" size="lg" />
                            <div class="flex-1 min-w-0">
                                <p class="aura-eyebrow">Company</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900 truncate dark:text-white">Northwind Software</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 shrink-0 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3.5 10.5 8 6 12.5" /></svg>
                        </div>
                        <dl class="pt-3 mt-3 space-y-1.5 border-t border-gray-100 dark:border-white/[0.06]">
                            <div class="flex gap-4 justify-between items-center text-sm"><dt class="text-gray-500 dark:text-gray-400">Website</dt><dd class="text-gray-900 dark:text-gray-100"><a href="#cards" class="aura-link">northwindsoftware.com</a></dd></div>
                            <div class="flex gap-4 justify-between items-center text-sm"><dt class="text-gray-500 dark:text-gray-400">Industry</dt><dd><span class="aura-tag aura-tag-status bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300">Software</span></dd></div>
                        </dl>
                    </div>
                    <div class="aura-record-card">
                        <div class="flex gap-3 items-center">
                            <x-aura::avatar name="Martina Keller" shape="circle" size="lg" />
                            <div class="flex-1 min-w-0">
                                <p class="aura-eyebrow">Contact</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900 truncate dark:text-white">Martina Keller</p>
                            </div>
                            <svg class="w-4 h-4 text-gray-300 shrink-0 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 3.5 10.5 8 6 12.5" /></svg>
                        </div>
                        <dl class="pt-3 mt-3 space-y-1.5 border-t border-gray-100 dark:border-white/[0.06]">
                            <div class="flex gap-4 justify-between items-center text-sm"><dt class="text-gray-500 dark:text-gray-400">Email</dt><dd class="min-w-0 text-gray-900 truncate dark:text-gray-100">martina.keller@northwindsoftware.com</dd></div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        {{-- Board column and media tiles: the two dense surfaces, both at 13px. --}}
        <div class="p-6 mt-4 bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Board column (.aura-board-column-title, .aura-board-card) and media tiles (.aura-tile-ring, .aura-tile-name, .aura-tile-meta)') }}</span>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('A board column has no fill: a title of 8px status dot, name and count, then cards 8px apart; columns sit 20px apart. A card is a 13px tile with a hairline ring and the smallest shadow (hover darkens the ring): title at 500, body at gray-500 clamped to two lines, then a 20px meta row with the due date (red at 500 when overdue), the priority as colored text with a bar mark, and the 20px assignee avatar on the right. An empty column shows a dashed placeholder. A media tile is a 10:7 thumbnail under an inset hairline overlay; selected tiles draw a 2px primary ring inside the edge, no shadow and no zoom. Its caption: the name at 13px 500 (truncated before the extension), then "type · size" at 12px.') }}</p>
            <div class="grid grid-cols-1 gap-8 mt-4 md:grid-cols-[16rem_minmax(0,1fr)]">
                <div>
                    <h3 class="aura-board-column-title">
                        <svg class="text-blue-500 size-2 shrink-0 fill-current dark:text-blue-400" viewBox="0 0 8 8" aria-hidden="true"><circle cx="4" cy="4" r="4" /></svg>
                        <span class="truncate">In progress</span>
                        <span class="aura-board-column-count">2</span>
                    </h3>
                    <div class="space-y-2">
                        <a href="#cards" class="aura-board-card">
                            <h4 class="aura-board-card-title">Migrate media library to S3</h4>
                            <p class="aura-board-card-body line-clamp-2">Move existing attachments to object storage and update the disk configuration for every team.</p>
                            <div class="aura-board-card-meta">
                                <span class="inline-flex gap-1 items-center tabular-nums"><svg class="text-gray-400 size-3.5 shrink-0 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5" /><path d="M3.5 10h17M8 3v4M16 3v4" /></svg>Nov 14</span>
                                <span class="inline-flex gap-1 items-center font-medium text-amber-700 dark:text-amber-300"><svg class="size-3.5 shrink-0" viewBox="0 0 14 14" aria-hidden="true"><rect x="1" y="9" width="2.5" height="3" rx="0.75" class="fill-current" /><rect x="5" y="5" width="2.5" height="7" rx="0.75" class="fill-current" /><rect x="9" y="1" width="2.5" height="11" rx="0.75" class="fill-current opacity-25" /></svg>Medium</span>
                                <x-aura::avatar name="Enes Emini" shape="circle" size="xs" class="ml-auto" />
                            </div>
                        </a>
                        <a href="#cards" class="aura-board-card">
                            <h4 class="aura-board-card-title">Build kanban board component</h4>
                            <div class="aura-board-card-meta">
                                <span class="inline-flex gap-1 items-center font-medium tabular-nums text-red-600 dark:text-red-400"><svg class="size-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5" /><path d="M3.5 10h17M8 3v4M16 3v4" /></svg>Nov 1</span>
                                <x-aura::avatar name="Bajram Emini" shape="circle" size="xs" class="ml-auto" />
                            </div>
                        </a>
                        <div class="aura-board-empty">{{ __('No tasks') }}</div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:gap-x-4 md:gap-y-5">
                    @foreach ([
                        ['northwind-office.jpg', 'JPEG', '49 KB', 'from-orange-200 via-rose-200 to-amber-100', false],
                        ['brand-moodboard.png', 'PNG', '19 KB', 'from-amber-300 via-yellow-200 to-orange-200', true],
                        ['proposal-northwind-erp.pdf', 'PDF', '86 KB', null, false],
                    ] as [$name, $type, $size, $gradient, $selected])
                        <div class="relative group select-none">
                            <div class="overflow-hidden relative w-full bg-gray-100 rounded-lg dark:bg-gray-800 aspect-w-10 aspect-h-7">
                                @if ($gradient)
                                    <div class="bg-gradient-to-br {{ $gradient }}"></div>
                                @else
                                    <div class="flex flex-col gap-1.5 justify-center items-center w-full h-full text-gray-400 bg-gray-100 rounded-lg dark:bg-gray-800 dark:text-gray-500">
                                        <x-aura::icon.file-document class="w-7 h-7" />
                                        <span class="px-1 text-[11px] font-medium text-gray-500 dark:text-gray-400">{{ $type }}</span>
                                    </div>
                                @endif
                                <div class="aura-tile-ring {{ $selected ? 'aura-tile-ring-selected' : '' }}"></div>
                            </div>
                            @if ($selected)
                                <div class="absolute top-3 left-3"><x-aura::input.checkbox checked /></div>
                            @endif
                            <div class="mt-2">
                                <p class="aura-tile-name">{{ $name }}</p>
                                <p class="aura-tile-meta"><span>{{ $type }}</span><span class="text-gray-300 dark:text-gray-600" aria-hidden="true">&middot;</span><span>{{ $size }}</span></p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 mt-4 sm:grid-cols-2">
            <div class="aura-card">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('KPI value (widgets/value)') }}</span>
                <div class="flex gap-2 items-baseline mt-2">
                    <span class="aura-stat-value text-gray-900 dark:text-white">2,741</span>
                    <span class="inline-flex items-center gap-0.5 rounded-full bg-green-50 dark:bg-green-900/40 px-2 py-0.5 text-xs font-medium text-green-700 dark:text-green-400">
                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 19V5M5 12l7-7 7 7" /></svg>
                        8.2%
                    </span>
                </div>
            </div>
            {{-- Specimen of widgets/index on a resource index (compact): one hairline-divided strip, date range as the last cell. --}}
            <div class="sm:col-span-2">
                <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Index widget strip (widgets, compact)') }}</span>
                <div class="flex overflow-hidden flex-wrap gap-px mt-2 rounded-xl ring-1 shadow-xs bg-gray-950/[0.06] ring-gray-950/10 dark:bg-white/10 dark:ring-white/10">
                    <div class="flex flex-col justify-center px-4 py-3 min-w-0 bg-white dark:bg-gray-800" style="flex: 33 1 11rem">
                        <span class="text-xs font-medium text-gray-500 truncate dark:text-gray-400">Total tasks</span>
                        <div class="flex gap-2 items-baseline mt-1">
                            <span class="text-xl font-semibold tracking-tight tabular-nums text-gray-900 dark:text-white">12</span>
                            <span class="text-xs font-medium tabular-nums text-green-700 dark:text-green-400">+20%</span>
                        </div>
                    </div>
                    <div class="flex gap-4 items-center px-4 py-3 min-w-0 bg-white dark:bg-gray-800" style="flex: 66 1 11rem">
                        <div class="min-w-0 shrink-0">
                            <span class="block text-xs font-medium text-gray-500 truncate dark:text-gray-400">Tasks created per day</span>
                            <span class="block mt-1 text-xl font-semibold tracking-tight tabular-nums text-gray-900 dark:text-white">12</span>
                        </div>
                        <svg viewBox="0 0 200 40" preserveAspectRatio="none" class="flex-1 ml-auto max-w-md h-10 text-primary-400" aria-hidden="true">
                            @foreach ([12, 30, 18, 40, 22, 8, 26, 34, 16, 28, 20, 36] as $i => $h)
                                <rect x="{{ $i * 16.6 + 3 }}" y="{{ 40 - $h }}" width="10" height="{{ $h }}" rx="2" fill="currentColor" />
                            @endforeach
                        </svg>
                    </div>
                    <div class="flex grow justify-end items-center px-3 py-2 bg-white dark:bg-gray-800">
                        <span class="px-2 py-1 text-sm font-medium text-gray-600 dark:text-gray-300">Last 30 days</span>
                    </div>
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
            {{ __('The resource index table. No card around it: a header hairline, row dividers and a closing hairline carry the list. Headings use the real table.heading component (sentence case, 12px medium gray-500) and lead with a 12px gray-400 mark for the column\'s value type (text, email, phone, number, date, status, relation; table/column-icon from the field class). A number column is right-aligned: the mark still leads the label and the sort arrow moves in front, so the label stays flush with the figures below; rows are a faithful specimen of table/row (the live rows are Livewire-bound). First column: entity avatar (24px, when the resource has one) plus the record name, 500 gray-900, linked to the record; other cells 400 gray-600, numerals tabular, empty cells show a dash. The page title carries the record count. With a pointer, the whole row opens the record and the row actions (gray-500) appear on hover; on touch they are always visible. Row hover (the row-hover: variant) waits until the pointer has moved, so a page that loads under a resting cursor shows no highlighted row. Related lists on record pages use the same rows. Second row shows the selected state.') }}
        </p>

        {{-- Filter tabs (specimen of table/filter-tabs) --}}
        <div class="flex flex-wrap gap-1 items-center p-1 mt-6 max-w-full bg-gray-50 rounded-lg dark:bg-white/5 w-fit">
            <button type="button" class="flex items-center px-3 py-1.5 space-x-2 text-sm font-medium text-gray-900 bg-white rounded-md ring-1 shadow-sm transition cursor-pointer ring-gray-950/10 dark:bg-gray-700 dark:text-white dark:ring-white/10">{{ __('All') }}</button>
            <button type="button" class="flex items-center px-3 py-1.5 space-x-2 text-sm text-gray-500 rounded-md transition cursor-pointer hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ __('My drafts') }}</button>
            <button type="button" class="flex items-center px-3 py-1.5 space-x-2 text-sm text-gray-500 rounded-md transition cursor-pointer hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">{{ __('Published') }}</button>
        </div>

        {{-- Table card (specimen of table/list-view) --}}
        <div class="overflow-hidden mt-4">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-200/80 dark:border-white/10">
                            <th class="py-2.5 pr-0 pl-4 w-px">
                                <x-aura::input.checkbox hideLabel label="Select all" />
                            </th>
                            <x-aura::table.heading sortable direction="asc" type="Aura\Base\Fields\Text">{{ __('Title') }}</x-aura::table.heading>
                            <x-aura::table.heading sortable align="right" type="Aura\Base\Fields\Number">{{ __('Amount') }}</x-aura::table.heading>
                            <x-aura::table.heading sortable type="Aura\Base\Fields\Status">{{ __('Status') }}</x-aura::table.heading>
                            <x-aura::table.heading type="Aura\Base\Fields\BelongsTo">{{ __('Author') }}</x-aura::table.heading>
                            <x-aura::table.heading sortable direction="desc" type="Aura\Base\Fields\Datetime">{{ __('Created at') }}</x-aura::table.heading>
                            <th class="px-3 py-2.5"><span class="sr-only">{{ __('Actions') }}</span></th>
                        </tr>
                    </thead>
                    <tbody class="border-b divide-y divide-gray-200/60 border-gray-200/80 dark:divide-white/[0.06] dark:border-white/10">
                        @foreach ($tableRows as $row)
                            <tr class="transition-colors duration-150 ease-in-out cm-table-row hover:bg-gray-50/80 dark:hover:bg-white/[0.04] {{ $row['selected'] ? 'bg-primary-50/60 dark:bg-primary-500/10' : '' }}">
                                <td class="relative py-3 pr-0 pl-4">
                                    @if ($row['selected'])
                                        <span class="absolute inset-y-0 left-0 w-0.5 bg-primary-600" aria-hidden="true"></span>
                                    @endif
                                    <x-aura::input.checkbox hideLabel :label="'Row '.$row['id']" :checked="$row['selected']" />
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                    <div class="flex gap-2.5 items-center min-w-0">
                                        <x-aura::avatar :name="$row['title']" shape="square" size="sm" />
                                        <a href="#table" class="min-w-0 truncate rounded-sm decoration-gray-400 underline-offset-[3px] transition-colors duration-150 hover:underline dark:decoration-gray-500">{{ $row['title'] }}</a>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-600 dark:text-gray-300">CHF {{ number_format(12450 * (int) $row['id']) }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="aura-tag aura-tag-status {{ $row['statusClass'] }}">
                                        <svg class="aura-tag-dot" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>
                                        {{ $row['status'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    <span class="aura-tag">
                                        <span class="aura-record-link-title">{{ $row['author'] }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row['date'] }}</td>
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
                    <span class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-sm font-medium rounded-lg bg-primary-50 text-primary-700 ring-1 ring-primary-200 dark:bg-primary-500/15 dark:text-primary-300 dark:ring-primary-500/20">1</span>
                    <button type="button" class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-950/5 dark:text-gray-400 dark:hover:bg-white/5 transition-colors duration-150">2</button>
                    <button type="button" class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-950/5 dark:text-gray-400 dark:hover:bg-white/5 transition-colors duration-150">3</button>
                    <button type="button" class="inline-flex items-center justify-center h-8 min-w-8 px-2 text-sm font-medium rounded-lg text-gray-600 hover:bg-gray-950/5 dark:text-gray-400 dark:hover:bg-white/5 transition-colors duration-150">4</button>
                    <button type="button" class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-gray-600 hover:bg-gray-950/5 dark:text-gray-400 dark:hover:bg-white/5 transition-colors duration-150">
                        <svg class="size-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 0 1 .02-1.06L11.168 10 7.23 6.29a.75.75 0 1 1 1.04-1.08l4.5 4.25a.75.75 0 0 1 0 1.08l-4.5 4.25a.75.75 0 0 1-1.06-.02Z" clip-rule="evenodd" /></svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- Phone list (specimen of table/list-view below sm) --}}
        <p class="mt-6 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Below sm the default row turns into a stacked list: the first column as the primary line, the next two values as quiet meta lines, a chevron when the record can be opened. A Number and a Status column share one line, the tag left and the value right, so a deal takes three lines. Number columns are right-aligned on desktop; phone and dates do not wrap; long emails truncate with the full address as tooltip.') }}
        </p>
        <ul class="overflow-hidden mt-3 max-w-sm bg-white rounded-xl ring-1 divide-y divide-gray-100 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10 dark:divide-white/5">
            @foreach (array_slice($tableRows, 0, 3) as $row)
                <li>
                    <a href="#table" class="flex gap-3 items-center px-4 py-3">
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium text-gray-900 truncate dark:text-white">{{ $row['title'] }}</div>
                            <div class="flex overflow-hidden gap-x-2 items-center mt-1 text-sm text-gray-500 whitespace-nowrap dark:text-gray-400">
                                <span class="min-w-0 truncate">{{ $row['author'] }}</span>
                                <span class="text-gray-300 dark:text-gray-600" aria-hidden="true">&middot;</span>
                                <span class="min-w-0 truncate">{{ $row['date'] }}</span>
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-300 shrink-0 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </a>
                </li>
            @endforeach
        </ul>

        {{-- Empty state (specimen of table/list-view) --}}
        <p class="mt-6 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Empty index: resource icon, "No {plural} yet", one helpful line and a "New {singular}" button for users who may create. No header row or select-all. Relation tables only show the title line; a search with no hits says so instead.') }}
        </p>
        <div class="overflow-hidden mt-3 bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <div class="flex flex-col items-center px-6 py-14 text-center">
                <div class="flex justify-center items-center mb-3 w-10 h-10 rounded-lg bg-gray-950/[0.04] text-gray-400 dark:bg-white/5 dark:text-gray-500" aria-hidden="true">
                    <svg class="size-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                </div>
                <h3 class="aura-section-title">{{ __('No :resources yet', ['resources' => 'companies']) }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Create the first one to get started.') }}</p>
                <div class="mt-5">
                    <x-aura::button href="#table">
                        <x-slot:icon><x-aura::icon icon="plus" /></x-slot>
                        <span>{{ __('New :resource', ['resource' => 'company']) }}</span>
                    </x-aura::button>
                </div>
            </div>
        </div>

        {{-- Record view (specimen of fields/wrapper in view mode) --}}
        <p class="mt-6 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('View pages list fields as a label/value grid: a 10rem gray label column and a 14px value, stacked on phones. No required markers in view mode.') }}
        </p>
        <div class="mt-3 max-w-2xl aura-card">
            @foreach (['Name' => 'Northwind Software', 'Website' => 'https://northwindsoftware.com', 'Amount' => '84,500.00'] as $label => $value)
                <div class="py-2 sm:grid sm:grid-cols-[10rem_minmax(0,1fr)] sm:gap-x-6 sm:items-baseline">
                    <span class="block mb-1 sm:mb-0 aura-eyebrow">{{ $label }}</span>
                    <div class="text-sm text-gray-900 dark:text-gray-100">{{ $value }}</div>
                </div>
            @endforeach
        </div>
    </section>


    {{-- ============================================================ --}}
    {{-- Sidebar --}}
    {{-- ============================================================ --}}
    <section id="navigation" class="pt-14 scroll-mt-16">
        <span class="text-xs font-semibold tracking-wider uppercase text-primary-600 dark:text-primary-400">09</span>
        <h2 class="mt-1 text-xl font-semibold text-gray-900 dark:text-white">{{ __('Sidebar') }}</h2>
        <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">
            {{ __('Real navigation.item / navigation.heading components inside the three sidebar themes (config: sidebar-type). Standard size: 32px items, 13px medium labels, 18px icons at 1.5 stroke; group labels 12px medium in sentence case. The light and primary sidebars mark the active item with a 2px hairline on its left edge; the dark sidebar, whose surface is gray-900 of the chosen gray palette rather than a fixed near-black, uses one quiet white tint with the icon in primary. The first item shows the active state.') }}
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

        <div class="p-6 mt-4 bg-gray-50 rounded-xl ring-1 dark:bg-white/5 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Toast card (static specimen)') }}</span>
            <div class="mt-4 w-full max-w-sm bg-white rounded-xl shadow-lg ring-1 ring-gray-950/10 dark:bg-gray-800 dark:ring-white/10">
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

        {{-- Inbox rows (the notifications page): the dashboard's recently-edited list with a second line. --}}
        <div class="p-6 mt-4 bg-white rounded-xl ring-1 shadow-xs dark:bg-gray-800 ring-gray-950/10 dark:ring-white/10">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Inbox rows (notifications page)') }}</span>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('An unboxed hairline list, the same row as Recently edited with a body line: a 6px primary dot in an 8px column for unread, the 28px level icon (neutral for info, tinted for success, warning and error), title and body at 14px, the relative time as .aura-meta in a fixed right column. Unread titles take the leading weight (600) in gray-900; read titles are 500 in gray-700. Row actions appear on hover from sm, in a ⋯ menu on phones.') }}</p>
            <ul class="mt-4 border-t border-gray-200 divide-y divide-gray-100 dark:border-white/10 dark:divide-white/[0.06]">
                @foreach ([
                    ['Deal won', 'Helvetia Capital Partners signed the Portfolio Reporting Platform deal.', '6 hours ago', true, 'bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400'],
                    ['Task assigned', 'Enes Emini assigned "Review kanban column spacing" to you.', '9 hours ago', true, 'bg-gray-950/[0.04] text-gray-400 dark:bg-white/5 dark:text-gray-500'],
                    ['Import finished', '10 companies were imported from companies-q4.csv.', '1 day ago', false, 'bg-gray-950/[0.04] text-gray-400 dark:bg-white/5 dark:text-gray-500'],
                ] as [$title, $body, $time, $unread, $box])
                    <li class="flex gap-3 items-start py-3 -mx-2 px-2 rounded-md group row-hover:bg-gray-50 dark:row-hover:bg-white/[0.04]">
                        <span class="flex justify-center items-center w-2 h-7 shrink-0" aria-hidden="true">
                            @if ($unread)<span class="w-1.5 h-1.5 rounded-full bg-primary-500"></span>@endif
                        </span>
                        <span class="flex justify-center items-center w-7 h-7 rounded-md shrink-0 {{ $box }}" aria-hidden="true">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"></path></svg>
                        </span>
                        <span class="flex-1 min-w-0">
                            <span class="block text-sm leading-5 truncate {{ $unread ? 'font-semibold text-gray-900 dark:text-white' : 'font-medium text-gray-700 dark:text-gray-300' }}">{{ $title }}</span>
                            <span class="block mt-0.5 text-sm leading-5 text-gray-500 dark:text-gray-400 line-clamp-2">{{ $body }}</span>
                        </span>
                        <span class="w-20 text-right shrink-0 leading-5 aura-meta">{{ $time }}</span>
                    </li>
                @endforeach
            </ul>
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

        <div class="flex justify-center p-8 mt-6 bg-gray-50 rounded-xl ring-1 dark:bg-gray-900 ring-gray-950/10 dark:ring-white/10 sm:p-12">
            <div class="w-full max-w-md rounded-2xl bg-white/95 backdrop-blur-xl ring-1 ring-gray-950/10 shadow-xl shadow-gray-950/[0.08] dark:bg-gray-800/90 dark:ring-white/10 dark:shadow-black/40 p-8 sm:p-10">
                <h3 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Welcome back') }}</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Log in to your account to continue.') }}</p>

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
