<x-dynamic-component :component="config('aura.views.login-layout')">

    <div class="mb-6">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Forgot your password?') }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </p>
    </div>

    <!-- Session Status -->
    <x-aura::auth-session-status class="mb-6" :status="session('status')" />

    <form method="POST" action="{{ route('aura.password.email') }}" class="space-y-5">
        @csrf
        <!-- Email Address -->
        <div>
            <x-aura::input-label class="!mt-0" for="email" :value="__('Email')" />
            <x-aura::input.text id="email" class="block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-aura::input-error :for="$errors->get('email')" class="mt-2" />
        </div>

        <div class="pt-1">
            <x-aura::button type="submit" block>{{ __('Email Password Reset Link') }}</x-aura::button>
        </div>
    </form>

    <p class="mt-6 text-sm text-center text-gray-500 dark:text-gray-400">
        <a class="font-medium rounded-md text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-900" href="/login">{{ __('Back to login') }}</a>
    </p>
</x-dynamic-component>
