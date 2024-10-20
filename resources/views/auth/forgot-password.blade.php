<x-dynamic-component :component="config('aura.views.login-layout')">
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Session Status -->
    <x-aura::auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('aura.password.email') }}">
        @csrf
        <!-- Email Address -->
        <div>
            <x-aura::input-label for="email" :value="__('Email')" />
            <x-aura::input.text id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-aura::input-error :for="$errors->get('email')" class="mt-2" />
        </div>


        <div class="flex justify-end items-center mt-6">
            <x-aura::button type="submit" block>{{ __('Email Password Reset Link') }}</x-aura::button>
        </div>

        <div class="flex justify-center mt-6 text-sm">
            <span><a class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" href="/login">{{ __('Back to login') }}</a></span>
        </div>
    </form>
</x-dynamic-component>
