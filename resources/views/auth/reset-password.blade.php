<x-aura::layout.login>
    <form method="POST" action="{{ route('aura.password.store') }}">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-aura::input-label for="email" :value="__('Email')" />
            <x-aura::text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus />
            <x-aura::input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-aura::input-label for="password" :value="__('Password')" />
            <x-aura::text-input id="password" class="block mt-1 w-full" type="password" name="password" required />
            <x-aura::input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-aura::input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-aura::text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required />

            <x-aura::input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex justify-end items-center mt-6">
            <x-aura::button type="submit" block>{{ __('Reset Password') }}</x-aura::button>
        </div>
    </form>
</x-aura::layout.login>
