@php
use Aura\Base\Resources\Team;
use Aura\Base\Facades\Aura;
$settings = app('aura')::getOption('team-settings');

$appSettings = app('aura')::options();

// dd($settings);

$sidebarToggled = auth()->check() ? auth()->user()->getOptionSidebarToggled() : true;

$darkmodeType = $settings['darkmode-type'] ?? 'auto';

$sidebarDarkmodeType = $settings['sidebar-darkmode-type'] ?? 'dark';

$compact = false;
@endphp

<div class="aura-sidebar aura-sidebar-type-{{ $sidebarType }} aura-darkmode-type-{{ $darkmodeType }}

        @if($darkmodeType == 'auto')
            aura-sidebar-darkmode-type-{{ $sidebarDarkmodeType }}
        @endif">

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
            {{-- console.log('init', this.sidebarToggled); --}}

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

        <div class="flex justify-between px-5 py-3 md:hidden">
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
                class="aura-sidebar-bg fixed top-0 left-0 z-10 flex flex-col flex-shrink-0 h-screen border-r shadow-xl {{ $sidebarToggled ? ($compact ? 'w-56' : 'w-72') : 'w-20' }}
            "
                    x-bind:class="{
                '{{ $compact ? 'w-56' : 'w-72' }}': sidebarToggled,
                'w-20': !sidebarToggled,
            }"
            >

                <div class="flex overflow-y-auto overflow-x-visible flex-col flex-1 px-0 pt-0 pb-5 space-y-1 aura-sidebar-scrollbar">

                    <div class="flex flex-col {{ $compact ? 'px-3' : 'px-5' }} space-y-1">
                        <div class="flex-shrink-0 h-[4.5rem] flex items-center justify-between">

                            <div class="hide-collapsed">
                                @include('aura::navigation.logo')
                            </div>

                            <div>
                                <button
                                    @click="toggleSidebar()"
                                    type="button"
                                    class="inline-flex relative justify-center items-center w-10 h-10 text-sm font-semibold rounded-lg border shadow-none select-none focus:outline-none focus:ring-2 aura-sidebar-toggle"
                                >
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
                                    class="hidden items-center py-1.5 pr-3 pl-2 w-full text-sm leading-6 rounded-md ring-1 shadow-sm aura-sidebar-search lg:flex">
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

                <div class=" flex-shrink-0 {{ $compact ? 'px-3' : 'px-5' }} min-h-[4.5rem] py-2 flex flex-wrap items-center border-t aura-sidebar-footer">
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
                                                <p class="text-sm font-medium aura-sidebar-user-name">{{ Auth::user()->name }}</p>
                                                <p class="text-xs font-medium aura-sidebar-team-name">{{ Auth::user()->currentTeam->name }}</p>
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
                                                <p class="text-sm font-medium aura-sidebar-user-name">{{ Auth::user()->name }}</p>
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
