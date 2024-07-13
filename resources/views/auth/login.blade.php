<x-aura::layout.login>
    <!-- Session Status -->
    <x-aura::auth-session-status class="mb-4" :status="session('status')" />
    
    @local
    <a href="{{ route('aura.login-as', ['id' => app(config('aura.resources.user'))::first()->id]) }}">Admin</a>
    @endlocal

    <form method="POST" action="">
        @csrf

        <div class="mt-4 mb-6 text-center">
            <h1 class="mb-2 text-2xl font-semibold">Login</h1>
        </div>

        <!-- Email Address -->
        <div>
            <x-aura::input-label class="sr-only" for="email" :value="__('Email')" />
            <x-aura::input.text id="email" placeholder="{{ __('Enter your email') }}" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-aura::input-error :for="optional($errors)->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-0">
            <x-aura::input-label class="sr-only" for="password" :value="__('Password')" />

            <x-aura::input.text id="password" class="block mt-2 w-full"
                            type="password"
                            name="password"
                            placeholder="{{ __('Enter your password') }}"
                            required autocomplete="current-password" />

            <x-aura::input-error :for="optional($errors)->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-between items-start mt-4">
            <!-- Remember Me -->
            <div class="flex items-center h-6">
                <label for="remember_me" class="inline-flex items-center">
                    <input id="remember_me" type="checkbox" class="rounded shadow-sm text-primary-600 border-gray-500/30 focus:ring-primary-500" name="remember">
                    <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ __('Remember me') }}</span>
                </label>
            </div>

            <!-- Forgot Password? -->
            <div class="flex items-center h-6">
                @if (Route::has('aura.password.request'))
                    <a class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" href="{{ route('aura.password.request') }}">
                        {{ __('Forgot your password?') }}
                    </a>
                @endif
            </div>
        </div>

        <div class="flex justify-end items-center mt-4">
            <x-aura::button type="submit" block>{{ __('Log in') }}</x-aura::button>
        </div>

        @if(config('aura.auth.registration'))
        <div class="flex justify-center mt-6 text-sm">
            <span>You don't have an account yet? <a class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" href="/register">Register.</a></span>
        </div>
        @endif
       
        <div>

            </div>
    </form>

</x-aura::layout.login>
