@props(['cols' => 'full'])

<div {{ $attributes->merge(['class' => 'col-span-12 rounded-xl bg-white dark:bg-gray-800 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10']) }}>
    <div class="flex flex-col items-center px-6 py-14 text-center">
        <div class="p-3 rounded-xl bg-primary-50 dark:bg-primary-900/50">
            <svg class="size-7 text-primary-600 dark:text-primary-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
                <path d="M12 22C11.1818 22 10.4002 21.6646 8.83693 20.9939C4.94564 19.3243 3 18.4895 3 17.0853L3 7.7475M12 22C12.8182 22 13.5998 21.6646 15.1631 20.9939C19.0544 19.3243 21 18.4895 21 17.0853V7.7475M12 22L12 12.1707M21 7.7475C21 8.35125 20.1984 8.7325 18.5953 9.495L15.6741 10.8844C13.8712 11.7419 12.9697 12.1707 12 12.1707M21 7.7475C21 7.14376 20.1984 6.7625 18.5953 6M3 7.7475C3 8.35125 3.80157 8.7325 5.40472 9.495L8.32592 10.8844C10.1288 11.7419 11.0303 12.1707 12 12.1707M3 7.7475C3 7.14376 3.80157 6.7625 5.40472 6M6.33203 13.311L8.32591 14.2594" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
                <path d="M12 2V4M16 3L14.5 5M8 3L9.5 5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
            </svg>
        </div>

        <h2 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ __('Create your first resource') }}</h2>
        <p class="mt-1 max-w-md text-sm text-gray-500 dark:text-gray-400">
            {{ __('Resources are the content types of your site — posts, products, projects, anything. Define one and Aura generates the admin UI for it.') }}
        </p>

        <code class="px-3 py-1.5 mt-5 font-mono text-xs text-gray-600 bg-gray-50 rounded-lg ring-1 ring-gray-950/5 dark:bg-gray-900/50 dark:text-gray-300 dark:ring-white/10">
            php artisan aura:resource Article
        </code>

        <div class="flex gap-3 items-center mt-6">
            @if (config('aura.features.create_resource'))
                <button onclick="Livewire.dispatch('openModal', { component: 'aura::create-resource' })"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white rounded-lg shadow-sm transition bg-primary-600 hover:bg-primary-500">
                    {{ __('Create Resource') }}
                </button>
            @endif
            <a href="https://aura-cms.com/docs/resources" target="_blank"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white rounded-lg ring-1 shadow-sm transition dark:bg-gray-800 dark:text-gray-200 ring-gray-950/10 dark:ring-white/10 hover:bg-gray-50 dark:hover:bg-gray-700/50">
                {{ __('Read the docs') }}
            </a>
        </div>
    </div>
</div>
