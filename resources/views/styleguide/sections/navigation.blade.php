<div class="grid gap-4 mt-6 md:grid-cols-3">
    @foreach ([
        'primary' => 'sidebar-type: primary',
        'light' => 'sidebar-type: light',
        'dark' => 'sidebar-type: dark',
    ] as $type => $label)
        <div data-sidebar-type="{{ $type }}">
            <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ $label }}</span>
            {{-- .aura-sidebar applies the --aura-sidebar-* size tokens, as in the real sidebar. --}}
            <div class="mt-3 aura-sidebar open-sidebar aura-sidebar-type-{{ $type }} aura-sidebar-darkmode-type-{{ $type }}">
                <div class="p-3 rounded-xl border aura-sidebar-bg">
                    <x-aura::navigation.heading>{{ __('Resources') }}</x-aura::navigation.heading>

                    <div class="flex flex-col mt-2 aura-sidebar-groups">
                        <x-aura::navigation.item href="#navigation" class="is-active">
                            <x-aura::icon icon="dashboard" size="base" />
                            <span>{{ __('Dashboard') }}</span>
                        </x-aura::navigation.item>

                        <x-aura::navigation.item href="#navigation">
                            <x-aura::icon icon="collection" size="base" />
                            <span>{{ __('Posts') }}</span>
                        </x-aura::navigation.item>

                        <div class="sg-force-hover">
                            <x-aura::navigation.item href="#navigation" badge="12" badgeColor="{{ $type === 'primary' ? 'gray' : 'primary' }}">
                                <x-aura::icon icon="media" size="base" />
                                <span>{{ __('Media (hover)') }}</span>
                            </x-aura::navigation.item>
                        </div>

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
<p class="mt-3 text-xs text-gray-400 dark:text-gray-500">{{ __('In dark mode the sidebar uses sidebar-darkmode-type instead. Item size follows the Sidebar size preset (see Design tokens).') }}</p>
