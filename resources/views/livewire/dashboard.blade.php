{{-- Product home: greeting, one row of stat cards, then unboxed sections (recent records and
     recent uploads in the wide column, a quiet column of quick actions and docs). Only the stat
     cards and the onboarding call-out are boxed.
     Apps add their own sections through three inject views (Aura::registerInjectView()), all empty by default:
     dashboard_stats replaces the count cards when it renders anything (build cards with x-aura::dashboard.stat),
     dashboard_main leads the wide column, dashboard_side leads the side column.
     With app sections (main or side) the side column is a third of the width (My tasks keeps 300px and more), the
     package's own links fold into one "Setup" block at its end for the people who see the team links (create
     actions, team links, documentation), and phones read: stats, main, side, recent records, uploads, setup.
     An empty team whose stats slot renders (an onboarding block) and no other app section shows that block alone. --}}
@php
    // Quick actions are the one place that creates records, also on an empty workspace.
    $createActions = collect($createActions ?? []);
    $statsSlot = trim((string) app('aura')::injectView('dashboard_stats'));
    $mainSlot = trim((string) app('aura')::injectView('dashboard_main'));
    $sideSlot = trim((string) app('aura')::injectView('dashboard_side'));
    $appSections = $mainSlot !== '' || $sideSlot !== '';
@endphp
<div class="grid grid-cols-12 gap-x-6 gap-y-8 lg:gap-x-10">

    {{-- A top-level page: no breadcrumb trail; the greeting carries the bookmark (dashboard.welcome). --}}
    <x-aura::dashboard.welcome cols="full" :empty="$isEmptyWorkspace ?? false" />

    @if ($statsSlot !== '' && ! $appSections && ($isEmptyWorkspace ?? false))
        {{-- The app's onboarding for an empty team carries the create actions; nothing repeats them. --}}
        <div class="col-span-12 min-w-0" data-dashboard-slot="stats">{!! $statsSlot !!}</div>
    @elseif ($appSections)
        <div class="col-span-12 min-w-0" data-dashboard-slot="stats">{!! $statsSlot !!}</div>

        {{-- Both columns dissolve into the grid below lg (contents), so their blocks take the phone order. --}}
        <div class="contents lg:flex lg:flex-col lg:col-span-8 lg:gap-8 lg:min-w-0">
            @if ($mainSlot !== '')
                <div class="flex flex-col order-1 col-span-12 gap-8 min-w-0 lg:order-none" data-dashboard-slot="main">{!! $mainSlot !!}</div>
            @endif

            <x-aura::dashboard.recent-activity :items="$recentItems" class="order-3 col-span-12 lg:order-none" />

            @if ($recentMedia->isNotEmpty())
                <x-aura::dashboard.media :media="$recentMedia" class="order-4 lg:order-none" />
            @endif
        </div>

        <div class="contents lg:flex lg:flex-col lg:col-span-4 lg:gap-8 lg:min-w-0">
            @if ($sideSlot !== '')
                <div class="flex flex-col order-2 col-span-12 gap-8 min-w-0 lg:order-none" data-dashboard-slot="side">{!! $sideSlot !!}</div>
            @endif

            <x-aura::dashboard.quick-actions cols="full" :create="$createActions" :title="__('Setup')" admin-only with-docs class="order-5 lg:order-none" />
        </div>
    @elseif ($stats->isNotEmpty() || $statsSlot !== '')
        @if ($statsSlot !== '')
            <div class="col-span-12 min-w-0" data-dashboard-slot="stats">{!! $statsSlot !!}</div>
        @else
            <div class="grid grid-cols-12 col-span-12 gap-4">
                <x-aura::dashboard.stats :stats="$stats" :calm="$isEmptyWorkspace ?? false" />
            </div>
        @endif

        {{-- Phones: the create actions right under the stats, then recent records and uploads, then the
             workspace links, then docs. The side column (and the quick actions in it) dissolves into the
             grid there (contents) so its blocks can be ordered. --}}
        <div class="flex flex-col order-2 col-span-12 gap-8 min-w-0 lg:order-none lg:col-span-8 xl:col-span-9">
            @if ($mainSlot !== '')
                <div class="flex flex-col gap-8 min-w-0" data-dashboard-slot="main">{!! $mainSlot !!}</div>
            @endif

            <x-aura::dashboard.recent-activity :items="$recentItems" />

            @if ($recentMedia->isNotEmpty())
                <x-aura::dashboard.media :media="$recentMedia" />
            @endif
        </div>

        <div class="contents lg:flex lg:flex-col lg:col-span-4 lg:gap-8 xl:col-span-3">
            @if ($sideSlot !== '')
                <div class="flex flex-col order-1 col-span-12 gap-8 min-w-0 lg:order-none" data-dashboard-slot="side">{!! $sideSlot !!}</div>
            @endif
            <x-aura::dashboard.quick-actions :create="$createActions" split-on-phones />
            @if ($showDocs ?? true)
                <x-aura::dashboard.docs class="order-4 col-span-12 lg:order-none" />
            @endif
        </div>
    @else
        <x-aura::dashboard.onboarding cols="full" />

        <x-aura::dashboard.quick-actions cols="6" :create="$createActions" />
        @if ($showDocs ?? true)
            <x-aura::dashboard.docs cols="6" />
        @endif

        @if ($recentMedia->isNotEmpty())
            <x-aura::dashboard.media :media="$recentMedia" cols="full" />
        @endif
    @endif

</div>
