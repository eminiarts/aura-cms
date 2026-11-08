{{-- Product home: greeting, one row of stat cards, then unboxed sections (recent records wide,
     a quiet column of quick actions and docs, recent uploads below). Only the stat cards and
     the onboarding call-out are boxed. --}}
<div class="grid grid-cols-12 gap-x-6 gap-y-8 lg:gap-x-10">

    <x-aura::dashboard.breadcrumbs cols="full" />

    <x-aura::dashboard.welcome cols="full" :empty="$isEmptyWorkspace ?? false" />

    @if ($stats->isNotEmpty())
        <div class="grid grid-cols-12 col-span-12 gap-4">
            <x-aura::dashboard.stats :stats="$stats" :calm="$isEmptyWorkspace ?? false" />
        </div>

        <x-aura::dashboard.recent-activity :items="$recentItems" class="col-span-12 lg:col-span-8 xl:col-span-9" />

        <div class="flex flex-col col-span-12 gap-8 lg:col-span-4 xl:col-span-3">
            <x-aura::dashboard.quick-actions />
            <x-aura::dashboard.docs />
        </div>
    @else
        <x-aura::dashboard.onboarding cols="full" />

        <x-aura::dashboard.quick-actions cols="6" />
        <x-aura::dashboard.docs cols="6" />
    @endif

    @if ($recentMedia->isNotEmpty())
        <x-aura::dashboard.media :media="$recentMedia" cols="full" />
    @endif

</div>
