<div class="flex justify-center p-8 mt-6 bg-gray-50 rounded-xl ring-1 dark:bg-gray-900 ring-gray-950/10 dark:ring-white/10 sm:p-12">
    <div class="w-full max-w-md rounded-2xl bg-white/95 backdrop-blur-xl ring-1 ring-gray-950/10 shadow-xl shadow-gray-950/[0.08] dark:bg-gray-800/90 dark:ring-white/10 dark:shadow-black/40 p-8 sm:p-10">
        <h3 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Welcome back') }}</h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Login to your account to continue.') }}</p>

        <div class="mt-8 space-y-5">
            <x-aura::input.email label="Email" placeholder="you@example.com" />
            <x-aura::input.text label="Password" type="password" placeholder="••••••••" />

            <div class="flex justify-between items-center">
                <x-aura::input.checkbox label="{{ __('Remember me') }}" />
                <a href="#auth" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">{{ __('Forgot your password?') }}</a>
            </div>

            <div class="pt-1">
                <x-aura::button block>{{ __('Log in') }}</x-aura::button>
            </div>
        </div>
    </div>
</div>
