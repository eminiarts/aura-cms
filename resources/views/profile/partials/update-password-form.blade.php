<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('aura.password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-aura::input-label for="current_password" :value="__('Current Password')" />
            <x-aura::text-input id="current_password" name="current_password" type="password" class="block mt-1 w-full" autocomplete="current-password" />
            <x-aura::input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-aura::input-label for="password" :value="__('New Password')" />
            <x-aura::text-input id="password" name="password" type="password" class="block mt-1 w-full" autocomplete="new-password" />
            <x-aura::input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-aura::input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-aura::text-input id="password_confirmation" name="password_confirmation" type="password" class="block mt-1 w-full" autocomplete="new-password" />
            <x-aura::input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex gap-4 items-center">
            <x-aura::primary-button>{{ __('Save') }}</x-aura::primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
