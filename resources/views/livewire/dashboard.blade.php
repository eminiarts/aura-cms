<div class="grid grid-cols-12 gap-6">

    <x-aura::dashboard.breadcrumbs cols="full" />

    <x-aura::dashboard.welcome cols="full" />

    @if ($stats->isNotEmpty())
        <x-aura::dashboard.stats :stats="$stats" />

        <x-aura::dashboard.recent-activity :items="$recentItems" class="col-span-12 lg:col-span-7 xl:col-span-8" />

        <div class="flex flex-col col-span-12 gap-6 lg:col-span-5 xl:col-span-4">
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
