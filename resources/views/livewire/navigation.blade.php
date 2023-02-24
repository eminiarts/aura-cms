
<div>
@php
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Facades\Aura;

$settings = Aura::getOption('team-settings');
@endphp


@php
    if ($settings) {
        $sidebarType = $settings['sidebar-type'] ?? 'primary';
    } else {
        $sidebarType = 'primary';
    }

    if ($sidebarType == 'primary') {
        $iconClass = 'group-[.is-active]:text-white text-primary-300 dark:text-primary-500 group-hover:text-primary-200 dark:group-hover:text-primary-500';
    } else if ($sidebarType == 'light') {
        $iconClass = 'group-[.is-active]:text-primary-500 text-primary-500 dark:text-primary-500 group-hover:text-primary-500';
    } else if ($sidebarType == 'dark') {
        $iconClass = 'group-[.is-active]:text-primary-500 text-primary-500';
    }
@endphp

<div class="flex md:hidden justify-between py-5 px-5
    @if ($sidebarType == 'primary')
        text-white border-white border-opacity-20 bg-primary-700 dark:bg-gray-800 dark:border-gray-700 shadow-gray-400 md:shadow-none
    @elseif ($sidebarType == 'light')
        text-gray-900 border-gray-500/30 border-opacity-20 bg-gray-50 dark:bg-gray-800 dark:text-white dark:border-gray-700 shadow-gray-400 md:shadow-none
    @elseif ($sidebarType == 'dark')
        text-white border-white border-opacity-20 bg-gray-800 dark:bg-gray-800 dark:text-white dark:border-gray-700 shadow-gray-400 md:shadow-none
    @endif
">
    <div>
        <svg width="123" height="24" viewBox="0 0 123 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M44.5089 24C50.1237 24 54.1979 19.6519 54.1979 14.174V0H48.6173V14.174C48.6173 16.3994 47.0767 18.2825 44.5089 18.2825C41.9069 18.2825 40.4005 16.3994 40.4005 14.174V0H34.7857V14.174C34.7857 19.6519 38.8941 24 44.5089 24Z" fill="currentColor"/>
            <path d="M72.6897 23.8117H66.9379V0.188278H77.654C83.6112 0.188278 86.5556 4.53635 86.5556 8.81595C86.5556 12.1027 84.9464 15.1498 81.8651 16.5192L86.9664 23.8117H80.2902L75.8052 17.3752V12.4108H77.2774C79.2631 12.4108 80.9065 10.8017 80.9065 8.81595C80.9065 6.69327 79.2631 5.18685 77.2774 5.18685H72.6897V23.8117Z" fill="currentColor"/>
            <path d="M122.214 23.7945H115.914L111.327 15.7146L108.827 11.1611L106.328 15.7146L103.418 20.7817L101.74 23.8287L95.4406 23.7945L108.827 0.17111L122.214 23.7945Z" fill="currentColor"/>
            <path d="M26.7732 23.7945H20.4736L15.8859 15.7146L13.3866 11.1611L10.8873 15.7146L7.97717 20.7817L6.29957 23.8287L0 23.7945L13.3866 0.17111L26.7732 23.7945Z" fill="currentColor"/>
        </svg>
    </div>
  <!-- Button to toggle the sidebar -->
  <button x-on:click="$store.leftSidebar.toggle()">
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M3 12H15M3 6H21M3 18H21" stroke="currentcolor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </button>
</div>

<div
    x-cloak
    class="flex-shrink-0 hidden w-0 md:block md:w-72"

    x-bind:class="{
        'hidden md:hidden': !$store.leftSidebar.on,
        'block md:block': $store.leftSidebar.on,
    }"
>
  <div class="fixed top-0 left-0 z-10 flex flex-col flex-shrink-0 h-screen overflow-y-auto border-r shadow-xl w-72
    @if ($sidebarType == 'primary')
        text-white border-white border-opacity-20 bg-primary-700 dark:bg-gray-800 dark:border-gray-700 shadow-gray-400 md:shadow-none
    @elseif ($sidebarType == 'light')
        text-gray-900 border-gray-500/30 border-opacity-20 bg-gray-50 dark:bg-gray-800 dark:text-white dark:border-gray-700 shadow-gray-400 md:shadow-none
    @elseif ($sidebarType == 'dark')
        text-white border-white border-opacity-20 bg-gray-800 dark:bg-gray-800 dark:text-white dark:border-gray-700 shadow-gray-400 md:shadow-none
    @endif
  ">

    <div class="flex flex-col flex-1 px-0 pt-0 pb-5 space-y-1 overflow-y-auto scrollbar-thin
        @if ($sidebarType == 'primary')
            scrollbar-thumb-primary-500 scrollbar-track-primary-700 dark:scrollbar-thumb-gray-900 dark:scrollbar-track-gray-800
        @elseif ($sidebarType == 'light')
            scrollbar-thumb-gray-300 scrollbar-track-gray-50 dark:scrollbar-thumb-gray-900 dark:scrollbar-track-gray-800
        @elseif ($sidebarType == 'dark')
            scrollbar-thumb-gray-700 scrollbar-track-gray-800 dark:scrollbar-thumb-gray-900
        @endif
    ">

        <div class="flex flex-col px-5 space-y-1">
            <div class="flex-shrink-0 h-[4.5rem] flex items-center justify-between">
            {{-- <h1 class="text-2xl font-semibold">{{ config('app.name') }}</h1> --}}

                @include('aura::navigation.logo')


                <div>
                    <button
                        @click="$store.leftSidebar.toggle()"
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
                        <x-aura::icon icon="minus" />
                    </button>
                </div>

            </div>

            <x-aura::input.text placeholder="Search" @click="$dispatch('search')" class="cursor-pointer"></x-aura::input>
        </div>

        <div class="flex flex-col px-4 space-y-1">

            {{-- @includeIf('navigation.before') --}}


            {{-- <x-aura::navigation /> --}}
            @include('aura::navigation.index')

            {{-- @includeIf('navigation.after') --}}

        </div>
    </div>

    <div class="flex-shrink-0 px-5 min-h-[4.5rem] py-2 flex items-center border-t
        @if ($sidebarType == 'primary')
            border-white border-opacity-20 dark:border-gray-700
        @elseif ($sidebarType == 'light')
            border-gray-500/30 dark:border-gray-700
        @elseif ($sidebarType == 'dark')
            border-gray-700 border-opacity-20 dark:border-gray-700
        @endif
    ">

        @impersonating($guard = null)
            <x-aura::button.primary route="impersonate.leave" class="w-full my-2" size="xs">
                <x-slot:icon>
                    <x-aura::icon icon="user-impersonate" size="xs" />
                </x-slot:icon>
                <span>Leave Impersonation</span>
            </x-aura::button.primary>
        @endImpersonating

        @if(Auth::user()->currentTeam)
        <div class="flex items-center justify-between w-full">
            <x-aura::navigation.team-switcher>
                <x-slot:title>
                    <div class="flex-shrink block w-full group">
                        <div class="flex items-center">
                            <div>
                                <img class="inline-block rounded-full h-9 w-9" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
                            </div>
                            <div class="ml-3">
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
                                        group-hover:text-white text-primary-200 dark:text-gray-500
                                    @elseif ($sidebarType == 'light')
                                        group-hover:text-gray-500 text-gray-400 dark:text-gray-500
                                    @elseif ($sidebarType == 'dark')
                                        group-hover:text-white text-gray-500
                                    @endif
                                ">{{ Auth::user()->currentTeam->name }}</p>
                            </div>
                        </div>
                    </div>
                </x-slot:title>

                @include('aura::navigation.footer-popup')

            </x-aura::navigation.team-switcher>

            <div class="ml-2">
                <x-aura::tippy text="Notifications">
                    <x-aura::button.primary @click="Livewire.emit('openSlideOver', 'notifications')" class="w-full my-2" size="xs">
                        <x-aura::icon icon="notifications" size="xs" />
                    </x-aura::button.primary>
                </x-aura::tippy>
            </div>
        </div>
        @endif

    </div>
  </div>
</div>

<div
    x-cloak
    class="flex-shrink-0 overflow-x-visible md:w-20"
    x-bind:class="{
        'hidden md:block': !$store.leftSidebar.on,
        'hidden': $store.leftSidebar.on,
    }"
>
  <div class="
    fixed top-0 left-0 z-10 flex flex-col flex-shrink-0 w-20 h-screen overflow-x-visible  border-r  shadow-xl

    @if ($sidebarType == 'primary')
        text-white border-white bg-primary-700 dark:bg-gray-800 border-opacity-20 dark:border-gray-700 shadow-gray-400 md:shadow-none
    @elseif ($sidebarType == 'light')
        text-gray-900 border-gray-500/30 border-opacity-20 bg-gray-50 dark:bg-gray-800 dark:text-white dark:border-gray-700 shadow-gray-400 md:shadow-none
    @elseif ($sidebarType == 'dark')
        text-white border-white border-opacity-20 bg-gray-800 dark:bg-gray-800 dark:text-white dark:border-gray-700 shadow-gray-400 md:shadow-none
    @endif

    ">
    <div class="flex-shrink-0 px-5 h-[4.5rem] w-full overflow-x-visible flex items-center">
        {{-- <h1 class="text-2xl font-semibold">{{ config('app.name') }}</h1> --}}

        <div>
            <button
                @click="$store.leftSidebar.toggle()"
                type="button"
                class="relative inline-flex items-center justify-center w-10 h-10 text-sm font-semibold border rounded-lg shadow-none select-none focus:outline-none focus:ring-2 focus:ring-offset-0
                @if ($sidebarType == 'primary')
                focus:ring-primary-500 border-primary-600 dark:border-gray-700 text-primary-200 dark:text-gray-500 hover:text-white dark:hover:text-white
                @elseif ($sidebarType == 'light')
                focus:ring-primary-500 border-gray-400/30 dark:border-gray-700 text-gray-600 dark:text-gray-500 hover:text-gray-300 dark:hover:text-white
                @elseif ($sidebarType == 'dark')
                focus:ring-primary-500 border-gray-700 text-gray-200 hover:text-white
                @endif
            ">
                <x-aura::icon icon="plus" />
            </button>
        </div>

    </div>

    @include('aura::navigation.collapsed')

    <div class="flex-shrink-0 px-5 h-[4.5rem] flex items-center border-t border-white border-opacity-20 dark:border-gray-700">
        <x-aura::tippy-area text="{{ Auth::user()->name }}" position="top">
            <x-slot name="title">
            <img class="inline-block rounded-full h-9 w-9" src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80" alt="">
            </x-slot::title>

            @include('aura::navigation.footer-popup')
        </x-aura::tippy-rea>
    </div>
  </div>
</div>
</div>
