<x-aura::layout.guest>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
    </div>

    <form method="POST" action="{{ route('aura.password.confirm') }}">
        @csrf

        <!-- Password -->
        <div>
            <x-aura::input-label for="password" :value="__('Password')" />

            <x-aura::text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-aura::input-error :for="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end items-center mt-6">
            <x-aura::button type="submit" block>{{ __('Confirm') }}</x-aura::button>
        </div>

    </form>
</x-aura::layout.guest>
