<x-dynamic-component :component="config('aura.views.login-layout')">

    @php
        // Same condition as the aura.login-as route in routes/auth.php: that route
        // 404s outside local + .test, so the button must not render there either.
        $localLoginAs = app()->environment('local')
            && \Illuminate\Support\Str::endsWith(request()->getHost(), '.test');

        // Bypass TeamScope: guests have no team, and fail-closed scoping returns no users.
        $localAdmin = $localLoginAs
            ? app(config('aura.resources.user'))::withoutGlobalScopes()->orderBy('id')->first()
            : null;
    @endphp

    <div class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Welcome back') }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Log in to your account to continue.') }}</p>
    </div>

    <!-- Session Status -->
    <x-aura::auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-aura::input-label class="!mt-0" for="email" :value="__('Email')" />
            <x-aura::input.text id="email" placeholder="{{ __('Enter your email') }}" class="block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-aura::input-error :for="optional($errors)->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-aura::input-label class="!mt-0" for="password" :value="__('Password')" />

            <x-aura::input.text id="password" class="block w-full"
                            type="password"
                            name="password"
                            placeholder="{{ __('Enter your password') }}"
                            required autocomplete="current-password" />

            <x-aura::input-error :for="optional($errors)->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-between items-center">
            <!-- Remember Me: the whole label is the tap target, a 44px row on phones. -->
            <label for="remember_me" class="inline-flex gap-2 items-center pr-3 -my-1 min-h-11 cursor-pointer sm:my-0 sm:min-h-0">
                <input id="remember_me" type="checkbox" class="w-4 h-4 rounded border-gray-300 shadow-xs cursor-pointer text-primary-600 focus:ring-primary-500 dark:border-gray-500 dark:bg-gray-950/40 dark:checked:bg-primary-600 dark:focus:ring-offset-gray-900" name="remember">
                <span class="text-sm text-gray-600 select-none dark:text-gray-300">{{ __('Remember me') }}</span>
            </label>

            <!-- Forgot Password? -->
            @if (Route::has('aura.password.request'))
                <a class="inline-flex items-center -my-1 min-h-11 text-sm font-medium rounded-md sm:my-0 sm:min-h-0 text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-900" href="{{ route('aura.password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <div class="pt-1">
            <x-aura::button type="submit" block>{{ __('Log in') }}</x-aura::button>
        </div>
    </form>

    @if(config('aura.auth.registration'))
        <p class="mt-6 text-sm text-center text-gray-500 dark:text-gray-400">
            {{ __("Don't have an account yet?") }} <a class="font-medium rounded-md text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-900" href="/register">{{ __('Register') }}</a>
        </p>
    @endif

    @if($localAdmin)
        {{-- A developer shortcut, not part of the sign-in: muted, smaller (32px) and set apart by its divider. --}}
        <div class="flex gap-3 items-center mt-10" aria-hidden="true">
            <div class="flex-1 h-px bg-gray-950/5 dark:bg-white/10"></div>
            <span class="text-xs text-gray-400 dark:text-gray-500">{{ __('Local development') }}</span>
            <div class="flex-1 h-px bg-gray-950/5 dark:bg-white/10"></div>
        </div>

        <div class="mt-3">
            <x-aura::button.border size="sm" href="{{ route('aura.login-as', ['id' => $localAdmin->id]) }}" class="justify-center w-full !text-gray-600 !shadow-none dark:!text-gray-300 login-as-admin">
                <x-slot:icon>
                    <x-aura::icon.user />
                </x-slot>
                {{ __('Log in as admin') }}
            </x-aura::button.border>
        </div>
    @endif

</x-dynamic-component>
