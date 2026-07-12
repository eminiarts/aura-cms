<x-aura::layout.guest>

    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Confirm your password') }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('aura.password.confirm') }}" class="space-y-5">
        @csrf

        <!-- Password -->
        <div>
            <x-aura::input-label class="!mt-0" for="password" :value="__('Password')" />

            <x-aura::text-input id="password" class="block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-aura::input-error :for="$errors->get('password')" class="mt-2" />
        </div>

        <div class="pt-1">
            <x-aura::button type="submit" block>{{ __('Confirm') }}</x-aura::button>
        </div>

    </form>
</x-aura::layout.guest>
