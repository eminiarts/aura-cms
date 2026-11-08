@props(['cols' => null])

@php
    $colSpan = match($cols) {
        'full' => 'col-span-12',
        '6' => 'col-span-12 md:col-span-6',
        '4' => 'col-span-12 md:col-span-4',
        default => ''
    };
@endphp

<div {{ $attributes->merge(['class' => trim("{$colSpan} rounded-xl bg-white dark:bg-gray-800 shadow-xs ring-1 ring-gray-950/10 dark:ring-white/10")]) }}>
    <div class="p-6">
        <div>
            <h2 class="aura-section-title">{{ __('Quick Actions') }}</h2>
            <p class="mt-0.5 aura-meta">{{ __('Common tasks and actions') }}</p>
        </div>

        <div class="grid grid-cols-1 gap-1 mt-4 -mx-3 sm:grid-cols-2">
            @if (config('aura.features.create_resource'))
                <button onclick="Livewire.dispatch('openModal', { component: 'aura::create-resource' })"
                    class="flex gap-3 items-center px-3 py-2 text-left rounded-lg transition-colors duration-150 group hover:bg-gray-50 dark:hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <div class="flex justify-center items-center w-8 h-8 rounded-md shrink-0 bg-primary-50 text-primary-600 ring-1 ring-inset ring-primary-600/10 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-400/10">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" color="currentColor" fill="none">
                            <path d="M12 22C11.1818 22 10.4002 21.6646 8.83693 20.9939C4.94564 19.3243 3 18.4895 3 17.0853L3 7.7475M12 22C12.8182 22 13.5998 21.6646 15.1631 20.9939C19.0544 19.3243 21 18.4895 21 17.0853V7.7475M12 22L12 12.1707M21 7.7475C21 8.35125 20.1984 8.7325 18.5953 9.495L15.6741 10.8844C13.8712 11.7419 12.9697 12.1707 12 12.1707M21 7.7475C21 7.14376 20.1984 6.7625 18.5953 6M3 7.7475C3 8.35125 3.80157 8.7325 5.40472 9.495L8.32592 10.8844C10.1288 11.7419 11.0303 12.1707 12 12.1707M3 7.7475C3 7.14376 3.80157 6.7625 5.40472 6M6.33203 13.311L8.32591 14.2594" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M12 2V4M16 3L14.5 5M8 3L9.5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Create Resource') }}</span>
                        <span class="aura-meta truncate">{{ __('Add a new content type') }}</span>
                    </div>
                </button>
            @endif

            <a href="{{ route('aura.settings') }}" wire:navigate class="flex gap-3 items-center px-3 py-2 rounded-lg transition-colors duration-150 group hover:bg-gray-50 dark:hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                <div class="flex justify-center items-center w-8 h-8 rounded-md shrink-0 bg-primary-50 text-primary-600 ring-1 ring-inset ring-primary-600/10 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-400/10">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" color="currentColor" fill="none">
                        <path d="M2.5 12C2.5 7.52166 2.5 5.28249 3.89124 3.89124C5.28249 2.5 7.52166 2.5 12 2.5C16.4783 2.5 18.7175 2.5 20.1088 3.89124C21.5 5.28249 21.5 7.52166 21.5 12C21.5 16.4783 21.5 18.7175 20.1088 20.1088C18.7175 21.5 16.4783 21.5 12 21.5C7.52166 21.5 5.28249 21.5 3.89124 20.1088C2.5 18.7175 2.5 16.4783 2.5 12Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                        <path d="M8.5 10C7.67157 10 7 9.32843 7 8.5C7 7.67157 7.67157 7 8.5 7C9.32843 7 10 7.67157 10 8.5C10 9.32843 9.32843 10 8.5 10Z" stroke="currentColor" stroke-width="1.5" />
                        <path d="M15.5 17C16.3284 17 17 16.3284 17 15.5C17 14.6716 16.3284 14 15.5 14C14.6716 14 14 14.6716 14 15.5C14 16.3284 14.6716 17 15.5 17Z" stroke="currentColor" stroke-width="1.5" />
                        <path d="M10 8.5L17 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                        <path d="M14 15.5L7 15.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                    </svg>
                </div>
                <div class="flex flex-col min-w-0">
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Settings') }}</span>
                    <span class="aura-meta truncate">{{ __('Configure your site') }}</span>
                </div>
            </a>

            @if (config('aura.teams'))
                <a href="{{ route('aura.team.edit', ['id' => auth()->user()->current_team_id]) }}" wire:navigate class="flex gap-3 items-center px-3 py-2 rounded-lg transition-colors duration-150 group hover:bg-gray-50 dark:hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <div class="flex justify-center items-center w-8 h-8 rounded-md shrink-0 bg-primary-50 text-primary-600 ring-1 ring-inset ring-primary-600/10 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-400/10">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" color="currentColor" fill="none">
                            <path d="M13 7C13 9.20914 11.2091 11 9 11C6.79086 11 5 9.20914 5 7C5 4.79086 6.79086 3 9 3C11.2091 3 13 4.79086 13 7Z" stroke="currentColor" stroke-width="1.5" />
                            <path d="M15 11C17.2091 11 19 9.20914 19 7C19 4.79086 17.2091 3 15 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                            <path d="M11 14H7C4.23858 14 2 16.2386 2 19C2 20.1046 2.89543 21 4 21H14C15.1046 21 16 20.1046 16 19C16 16.2386 13.7614 14 11 14Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                            <path d="M17 14C19.7614 14 22 16.2386 22 19C22 20.1046 21.1046 21 20 21H18.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Edit Team') }}</span>
                        <span class="aura-meta truncate">{{ __('Manage team settings') }}</span>
                    </div>
                </a>
            @endif

            @if (config('aura.features.plugins'))
                <a href="{{ route('aura.plugins') }}" wire:navigate class="flex gap-3 items-center px-3 py-2 rounded-lg transition-colors duration-150 group hover:bg-gray-50 dark:hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500">
                    <div class="flex justify-center items-center w-8 h-8 rounded-md shrink-0 bg-primary-50 text-primary-600 ring-1 ring-inset ring-primary-600/10 dark:bg-primary-500/10 dark:text-primary-400 dark:ring-primary-400/10">
                        <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" color="currentColor" fill="none">
                            <path d="M12.828 6.00096C12.9388 5.68791 12.999 5.35099 12.999 5C12.999 3.34315 11.6559 2 9.99904 2C8.34219 2 6.99904 3.34315 6.99904 5C6.99904 5.35099 7.05932 5.68791 7.17008 6.00096C4.88532 6.0093 3.66601 6.09039 2.87772 6.87868C2.08951 7.66689 2.00836 8.88603 2 11.1704C2.31251 11.06 2.64876 11 2.99904 11C4.6559 11 5.99904 12.3431 5.99904 14C5.99904 15.6569 4.6559 17 2.99904 17C2.64876 17 2.31251 16.94 2 16.8296C2.00836 19.114 2.08951 20.3331 2.87772 21.1213C3.66593 21.9095 4.88508 21.9907 7.16941 21.999C7.05908 21.6865 6.99904 21.3503 6.99904 21C6.99904 19.3431 8.34219 18 9.99904 18C11.6559 18 12.999 19.3431 12.999 21C12.999 21.3503 12.939 21.6865 12.8287 21.999C15.113 21.9907 16.3322 21.9095 17.1204 21.1213C17.9086 20.333 17.9897 19.1137 17.9981 16.829C18.3111 16.9397 18.648 17 18.999 17C20.6559 17 21.999 15.6569 21.999 14C21.999 12.3431 20.6559 11 18.999 11C18.648 11 18.3111 11.0603 17.9981 11.171C17.9897 8.88627 17.9086 7.66697 17.1204 6.87868C16.3321 6.09039 15.1128 6.0093 12.828 6.00096Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" />
                        </svg>
                    </div>
                    <div class="flex flex-col min-w-0">
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ __('Plugins') }}</span>
                        <span class="aura-meta truncate">{{ __('Browse and manage plugins') }}</span>
                    </div>
                </a>
            @endif
        </div>
    </div>
</div>
