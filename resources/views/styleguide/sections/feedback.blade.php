<div class="grid gap-4 mt-6 sm:grid-cols-3">
    <div class="aura-card">
        <span class="text-xs font-medium tracking-wide text-gray-400 uppercase">{{ __('Spinner') }}</span>
        <div class="flex gap-4 items-center mt-4 text-primary-600 dark:text-primary-400">
            <x-aura::icon icon="loading" size="sm" />
            <x-aura::icon icon="loading" size="base" />
            <x-aura::icon icon="loading" size="lg" />
        </div>
        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('icon="loading". Buttons use icon.loading, drawn in currentColor like this one.') }}</p>
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
        <div class="mt-4 space-y-2">
            <div class="aura-alert aura-radius-control">
                {{ __('Plugins are managed via Composer.') }} <code class="px-1.5 py-0.5 text-xs font-semibold aura-chip aura-radius-control">composer require vendor/plugin</code>
            </div>
            <div class="aura-alert aura-alert-success aura-radius-control" role="alert">{{ __('You have enabled two factor authentication.') }}</div>
            <div class="aura-alert aura-alert-warning aura-radius-control" role="alert">{{ __('You have not enabled two factor authentication.') }}</div>
            <div class="aura-alert aura-alert-danger aura-radius-control" role="alert">{{ __('The connection failed.') }}</div>
        </div>
        <p class="mt-4 text-xs text-gray-400 dark:text-gray-500">{{ __('.aura-alert (+ -success, -warning, -danger): a tinted fill with a ring, so the box stays visible inside a card in dark mode.') }}</p>
    </div>
</div>
