<div class="grid gap-4 mt-6 sm:grid-cols-3">
    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Spinner') }}</span>
        <div class="flex gap-4 items-center mt-4 text-primary-600 dark:text-primary-400">
            <x-aura::icon icon="loading" size="sm" />
            <x-aura::icon icon="loading" size="base" />
            <x-aura::icon icon="loading" size="lg" />
        </div>
        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('icon="loading". Buttons use icon.loading (white, with a light track).') }}</p>
    </div>

    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Skeleton') }}</span>
        <div class="mt-4 space-y-3 animate-pulse">
            <div class="w-3/4 h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
            <div class="w-full h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
            <div class="w-1/2 h-4 bg-gray-200 rounded dark:bg-gray-700"></div>
        </div>
    </div>

    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Inline alert') }}</span>
        <div class="p-4 mt-4 text-sm text-gray-700 bg-gray-100 rounded-lg border border-gray-200 dark:bg-white/5 dark:text-gray-300 dark:border-white/10">
            {{ __('Plugins are managed via Composer.') }} <code class="px-2 py-1 text-xs font-semibold text-gray-800 bg-white rounded dark:bg-gray-900 dark:text-gray-200">composer require vendor/plugin</code>
        </div>
    </div>
</div>
