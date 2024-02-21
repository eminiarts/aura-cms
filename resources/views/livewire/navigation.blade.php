<div>
    @php
        use Aura\Base\Resources\Team;
        use Aura\Base\Facades\Aura;

        $settings = app('aura')::getOption('team-settings');

        $appSettings = app('aura')::options();

        $sidebarToggled = auth()->check() ? auth()->user()->getOptionSidebarToggled() : true;

        $darkmodeType = $settings['darkmode-type'] ?? 'auto';

        $sidebarDarkmodeType = $settings['sidebar-darkmode-type'] ?? 'dark';

        $compact = false;

        ray($settings, $darkmodeType, $sidebarDarkmodeType)

    @endphp

    <style>
        @media screen and (max-width: 768px) {
            .mobile-load-hidden {
                display: none !important;
            }
        }
    </style>

    <div
            x-data="{
        sidebarToggled: {{ $sidebarToggled ? 'true' : 'false' }},
        init() {
            console.log('init', this.sidebarToggled);

            this.$nextTick(() => {
                document.querySelectorAll('.mobile-load-hidden').forEach((el) => {
                    el.classList.remove('mobile-load-hidden');
                });

                if (window.innerWidth < 768) {
                    this.sidebarToggled = false;
                }
            });
        },
        toggleSidebar: async function() {
            if (window.innerWidth < 768) {
                console.log('toggleSidebar async mobile');
                this.sidebarToggled = !this.sidebarToggled;
            } else {
                this.sidebarToggled = !this.sidebarToggled;
                console.log('toggleSidebar async desktop1', this.sidebarToggled);
                await $wire.toggleSidebar();
                console.log('toggleSidebar async deskto2', this.sidebarToggled);
            }
        }
    }">

        <div class="flex md:hidden justify-between py-3 px-5
        @if ($sidebarType == 'primary')
            text-white border-white border-opacity-20 bg-sidebar-bg dark:bg-gray-800 dark:border-gray-700 shadow-gray-400 md:shadow-none
        @elseif ($sidebarType == 'light')
            text-gray-900 border-gray-500/30 border-opacity-20 bg-gray-50 dark:bg-gray-800 dark:text-white dark:border-gray-700 shadow-gray-400 md:shadow-none
        @elseif ($sidebarType == 'dark')
            text-white border-white border-opacity-20 bg-gray-800 dark:bg-gray-800 dark:text-white dark:border-gray-700 shadow-gray-400 md:shadow-none
        @endif
    ">
            <div>
                @include('aura::navigation.logo')
            </div>

            <!-- Button to toggle the sidebar -->
            <button x-on:click="toggleSidebar()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M3 12H15M3 6H21M3 18H21" stroke="currentcolor" stroke-width="2" stroke-linecap="round"
                          stroke-linejoin="round"/>
                </svg>
                <span class="sr-only">Toggle sidebar</span>
            </button>

        </div>

        <div
                class="mobile-load-hidden overflow-x-visible flex-shrink-0 aura-navigation {{ $sidebarToggled ? ($compact ? 'open-sidebar md:w-56' : 'open-sidebar md:w-72') : 'closed-sidebar w-20' }}"

                x-bind:class="{
            'open-sidebar {{ $compact ? 'md:w-56' : 'md:w-72' }}': sidebarToggled,
            'closed-sidebar w-20': !sidebarToggled,
        }"
        >
            <div
                    class="sidebar sidebar-type-{{ $sidebarType }} darkmode-type-{{ $darkmodeType }} sidebar-darkmode-type-{{ $sidebarDarkmodeType }} fixed top-0 left-0 z-10 flex flex-col flex-shrink-0 h-screen border-r shadow-xl {{ $sidebarToggled ? ($compact ? 'w-56' : 'w-72') : 'w-20' }}


                @if ($sidebarType == 'primary')
                    text-white border-white border-opacity-20 bg-sidebar-bg shadow-gray-400 md:shadow-none
                @elseif ($sidebarType == 'light')
                    text-gray-900 border-gray-500/30 border-opacity-20 bg-gray-50 shadow-gray-400 md:shadow-none
                @elseif ($sidebarType == 'dark')
                    text-white border-white border-opacity-20 bg-gray-800 shadow-gray-400 md:shadow-none
                @endif


                @if ($darkmodeType == 'auto')

                    @if ($sidebarDarkmodeType == 'primary')
                        dark:text-white dark:border-white dark:border-opacity-20 dark:bg-sidebar-bg dark:shadow-gray-400 md:dark:shadow-none
                    @elseif ($sidebarDarkmodeType == 'light')
                        dark:text-gray-900 dark:border-gray-500/30 dark:border-opacity-20 dark:bg-gray-50 dark:shadow-gray-400 md:dark:shadow-none
                    @elseif ($sidebarDarkmodeType == 'dark')
                        dark:text-white dark:border-white dark:border-opacity-20 dark:bg-gray-800 dark:shadow-gray-400 md:dark:shadow-none
                    @endif

                @endif
            "
                    x-bind:class="{
                '{{ $compact ? 'w-56' : 'w-72' }}': sidebarToggled,
                'w-20': !sidebarToggled,
            }"
            >

                <div class="flex flex-col flex-1 px-0 pt-0 pb-5 space-y-1 overflow-y-auto overflow-x-visible scrollbar-thin
                @if ($sidebarType == 'primary')
                    scrollbar-thumb-primary-500 scrollbar-track-primary-700 dark:scrollbar-thumb-gray-900 dark:scrollbar-track-gray-800
                @elseif ($sidebarType == 'light')
                    scrollbar-thumb-gray-300 scrollbar-track-gray-50 dark:scrollbar-thumb-gray-900 dark:scrollbar-track-gray-800
                @elseif ($sidebarType == 'dark')
                    scrollbar-thumb-gray-700 scrollbar-track-gray-800 dark:scrollbar-thumb-gray-900
                @endif
            ">

                    <div class="flex flex-col {{ $compact ? 'px-3' : 'px-5' }} space-y-1">
                        <div class="flex-shrink-0 h-[4.5rem] flex items-center justify-between">

                            <div class="hide-collapsed">
                                @include('aura::navigation.logo')
                            </div>

                            <div>
                                <button
                                        @click="toggleSidebar()"
                                        type="button"
                                        class="relative inline-flex items-center justify-center w-10 h-10 text-sm font-semibold border rounded-lg shadow-none select-none focus:outline-none focus:ring-2

                                @if ($sidebarType == 'primary')
                                focus:ring-primary-500 border-primary-600 dark:border-gray-700 text-primary-200 dark:text-gray-600 hover:text-white dark:hover:text-gray-200
                                @elseif ($sidebarType == 'light')
                                focus:ring-primary-500 border-gray-400/30 dark:border-gray-700 text-gray-600 dark:text-gray-500 hover:text-gray-300 dark:hover:text-gray-200
                                @elseif ($sidebarType == 'dark')
                                focus:ring-primary-500 border-gray-700 text-gray-600 hover:text-gray-200
                                @endif
                            ">
                                    <div class="hide-collapsed">
                                        <x-aura::icon icon="minus"/>
                                    </div>

                                    <div class="show-collapsed">
                                        <x-aura::icon icon="plus"/>
                                    </div>

                                </button>
                            </div>

                        </div>

                        @if(config('aura.features.search'))
                            <button type="button" @click="$dispatch('search')"
                                    class="
                                @if ($sidebarType == 'primary')
                                    text-primary-200/40 hover:text-primary-200/70
                                    ring-primary-200/40 hover:ring-primary-200/70
                                @elseif ($sidebarType == 'light')
                                    text-gray-400/60 hover:text-gray-400/90
                                    ring-gray-400/40 hover:ring-gray-400/70
                                @elseif ($sidebarType == 'dark')
                                    bg-gray-800 highlight-white/5
                                    text-white/40 hover:text-white/70
                                    ring-white/30 hover:ring-white/70
                                @endif

                                dark:bg-gray-800 dark:highlight-white/5
                                dark:text-white/40 dark:hover:text-white/70
                                dark:ring-white/30 dark:hover:ring-white/70

                                ring-1
                                hidden w-full lg:flex items-center text-sm leading-6
                                shadow-sm py-1.5 pl-2 pr-3
                                rounded-md
                        ">
                                <svg width="24" height="24" fill="none" aria-hidden="true" class="flex-none mr-3">
                                    <path d="m19 19-3.5-3.5" stroke="currentColor" stroke-width="2"
                                          stroke-linecap="round" stroke-linejoin="round"></path>
                                    <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round"></circle>
                                </svg>
                                {{ __('Search') }}
                                <span class="flex-none pl-3 ml-auto text-xs font-semibold">⌘K</span>
                            </button>
                        @endif

                    </div>

                    <div class="flex flex-col {{ $compact ? 'px-2' : 'px-4' }} space-y-1">

                        @includeIf('navigation.before')

                        @include('aura::navigation.index')

                        @includeIf('navigation.after')

                    </div>
                </div>

                <div class=" flex-shrink-0 {{ $compact ? 'px-3' : 'px-5' }} min-h-[4.5rem] py-2 flex flex-wrap items-center border-t
                @if ($sidebarType == 'primary')
                    border-white border-opacity-20 dark:border-gray-700
                @elseif ($sidebarType == 'light')
                    border-gray-500/30 dark:border-gray-700
                @elseif ($sidebarType == 'dark')
                    border-gray-700 border-opacity-20 dark:border-gray-700
                @endif
            ">

                    @impersonating($guard = null)
                    <div class="w-full">
                        <x-aura::button.primary :href="route('impersonate.leave')" class="my-2 w-full" size="xs">
                        <x-slot:icon>
                            <x-aura::icon icon="user-impersonate" size="xs"/>
                        </x-slot:icon>
                        <span>{{ __('Leave Impersonation') }}</span>
                    </x-aura::button.primary>
                    </div>
                    @endImpersonating

                    @if(config('aura.teams') && Auth::user()->currentTeam)
                        <div class="flex justify-between items-center pb-20 w-full md:pb-0">
                            <x-aura::navigation.team-switcher>
                                <x-slot:title>
                                    <div class="block flex-shrink w-full group">
                                        <div class="flex items-center">
                                            <div>
                                                <img class="inline-block w-9 h-9 rounded-full"
                                                     src="{{ auth()->user()->resource->avatarUrl }}" alt="">
                                            </div>
                                            <div class="ml-3 hide-collapsed">
                                                <p class="text-sm font-medium
                                                @if ($sidebarType == 'primary')
                                                    text-white
                                                @elseif ($sidebarType == 'light')
                                                    text-gray-900
                                                @elseif ($sidebarType == 'dark')
                                                    text-white
                                                @endif
                                            ">{{ Auth::user()->name }}</p>
                                                <p class="text-xs font-medium
                                                @if ($sidebarType == 'primary')
                                                    text-primary-200 dark:text-gray-500
                                                @elseif ($sidebarType == 'light')
                                                    text-gray-400 dark:text-gray-500
                                                @elseif ($sidebarType == 'dark')
                                                    text-gray-500
                                                @endif
                                            ">{{ Auth::user()->currentTeam->name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </x-slot:title>

                                @include('aura::navigation.footer-popup')

                            </x-aura::navigation.team-switcher>

                            @if(config('aura.features.notifications'))
                                <div class="ml-2">
                                    <x-aura::tippy text="{{ __('Notifications') }}">
                                        <x-aura::button.primary @click="Livewire.emit('openSlideOver', 'notifications')"
                                                                class="my-2 w-full" size="xs">
                                            <x-aura::icon icon="notifications" size="xs"/>
                                        </x-aura::button.primary>
                                    </x-aura::tippy>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="flex justify-between items-center w-full">

                            <x-aura::navigation.team-switcher>
                                <x-slot:title>
                                    <div class="block flex-shrink w-full group">
                                        <div class="flex items-center">
                                            <div>
                                                <img class="inline-block w-9 h-9 rounded-full"
                                                     src="{{ auth()->user()->resource->avatarUrl }}" alt="">
                                            </div>
                                            <div class="ml-3 hide-collapsed">
                                                <p class="text-sm font-medium
                                                @if ($sidebarType == 'primary')
                                                    text-white
                                                @elseif ($sidebarType == 'light')
                                                    text-gray-900
                                                @elseif ($sidebarType == 'dark')
                                                    text-white
                                                @endif
                                            ">{{ Auth::user()->name }}</p>

                                            </div>
                                        </div>
                                    </div>
                                </x-slot:title>

                                @include('aura::navigation.footer-popup')

                            </x-aura::navigation.team-switcher>

                            @if(config('aura.features.notifications'))
                                <div class="ml-2">
                                    <x-aura::tippy text="{{ __('Notifications') }}">
                                        <x-aura::button.primary @click="Livewire.emit('openSlideOver', 'notifications')"
                                                                class="my-2 w-full" size="xs">
                                            <x-aura::icon icon="notifications" size="xs"/>
                                        </x-aura::button.primary>
                                    </x-aura::tippy>
                                </div>
                            @endif
                        </div>
                    @endif

                </div>
            </div>
        </div>


    </div>
</div>
