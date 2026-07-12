<x-dynamic-component :component="config('aura.views.login-layout')">

    <div class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Reset your password') }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Choose a new password for your account.') }}</p>
    </div>

    <form method="POST" action="{{ route('aura.password.store') }}" class="space-y-5">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-aura::input-label class="!mt-0" for="email" :value="__('Email')" />
            <x-aura::text-input id="email" class="block w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus />
            <x-aura::input-error :for="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-aura::input-label class="!mt-0" for="password" :value="__('Password')" />
            <x-aura::text-input id="password" class="block w-full" type="password" name="password" required />
            <x-aura::input-error :for="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-aura::input-label class="!mt-0" for="password_confirmation" :value="__('Confirm Password')" />

            <x-aura::text-input id="password_confirmation" class="block w-full"
                                type="password"
                                name="password_confirmation" required />

            <x-aura::input-error :for="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-1">
            <x-aura::button type="submit" block>{{ __('Reset Password') }}</x-aura::button>
        </div>
    </form>
</x-dynamic-component>
