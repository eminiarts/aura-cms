{{-- Product home: greeting, one row of stat cards, then unboxed sections (recent records and
     recent uploads in the wide column, a quiet column of quick actions and docs). Only the stat
     cards and the onboarding call-out are boxed. --}}
@php
    $createActions = collect($createActions ?? []);
    // An empty workspace leads with creating its first record (not another user: the viewer is one).
    $emptyAction = ($isEmptyWorkspace ?? false) ? $createActions->firstWhere('isUser', false) : null;
@endphp
<div class="grid grid-cols-12 gap-x-6 gap-y-8 lg:gap-x-10">

    <x-aura::dashboard.breadcrumbs cols="full" />

    <x-aura::dashboard.welcome cols="full" :empty="$isEmptyWorkspace ?? false" />

    @if ($stats->isNotEmpty())
        <div class="grid grid-cols-12 col-span-12 gap-4">
            <x-aura::dashboard.stats :stats="$stats" :calm="$isEmptyWorkspace ?? false" />
        </div>

        {{-- Phones: quick actions right under the stats, then recent records and uploads, then docs.
             The side column dissolves into the grid there (contents) so its blocks can be ordered. --}}
        <div class="flex flex-col order-2 col-span-12 gap-8 min-w-0 lg:order-none lg:col-span-8 xl:col-span-9">
            <x-aura::dashboard.recent-activity :items="$recentItems" :action="$emptyAction" />

            @if ($recentMedia->isNotEmpty())
                <x-aura::dashboard.media :media="$recentMedia" />
            @endif
        </div>

        <div class="contents lg:flex lg:flex-col lg:col-span-4 lg:gap-8 xl:col-span-3">
            <x-aura::dashboard.quick-actions :create="$createActions" class="order-1 col-span-12 lg:order-none" />
            @if ($showDocs ?? true)
                <x-aura::dashboard.docs class="order-3 col-span-12 lg:order-none" />
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
