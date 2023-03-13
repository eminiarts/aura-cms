<x-aura::layout.guest>
    <!-- Session Status -->
    <x-aura::auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="">
        @csrf

        <div class="mt-6 mb-8 text-center">
            <h1 class="mb-2 text-2xl font-semibold">Login</h1>
            <h2 class="text-gray-600">Please enter your details.</h2>
        </div>

        <!-- Email Address -->
        <div>
            <x-aura::input-label class="sr-only" for="email" :value="__('Email')" />
            <x-aura::input.text id="email" placeholder="Enter your email" class="block w-full mt-1" type="email" name="email" :value="old('email')" required autofocus />
            <x-aura::input-error :messages="optional($errors)->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-0">
            <x-aura::input-label class="sr-only" for="password" :value="__('Password')" />

            <x-aura::input.text id="password" class="block w-full mt-1"
                            type="password"
                            name="password"
                            placeholder="Enter your password"
                            required autocomplete="current-password" />

            <x-aura::input-error :messages="optional($errors)->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-between">
            <!-- Remember Me -->
            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="text-indigo-600 rounded shadow-sm border-gray-500/30 focus:ring-indigo-500" name="remember">
                    <span class="ml-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                </label>
            </div>

            <!-- Forgot Password? -->
            <div class="block mt-4">
                @if (Route::has('password.request'))
                    <a class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-aura::button type="submit" block>{{ __('Log in') }}</x-aura::button>
        </div>
        {{-- <div class="flex items-center justify-end mt-4">
            <x-aura::button.border type="submit" block>{{ __('Log in with Google') }}</x-aura::button.border>
        </div> --}}
        <div>

            </div>
    </form>
</x-aura::layout.guest>
