<x-aura::layout.guest>

    <div class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Create your account') }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('Enter your details below to get started.') }}</p>
    </div>

    <form method="POST" action="{{ route('aura.register') }}" onsubmit="document.getElementById('register-button').disabled = true;" class="space-y-5">
        @csrf

        @if(config('aura.teams'))
        <!-- Team -->
        <div>
            <x-aura::input-label class="!mt-0" for="team" :value="__('Team')" />
            <x-aura::input.text id="team" placeholder="{{ __('Your team name') }}" class="block w-full" type="text" name="team" :value="old('team')" required autofocus autocomplete="organization" />
            <x-aura::input-error :for="$errors->get('team')" class="mt-2" />
        </div>
        @endif

        <!-- Name -->
        <div>
            <x-aura::input-label class="!mt-0" for="name" :value="__('Name')" />
            <x-aura::input.text id="name" placeholder="{{ __('Enter your name') }}" class="block w-full" type="text" name="name" :value="old('name')" required :autofocus="!config('aura.teams')" autocomplete="name" />
            <x-aura::input-error :for="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-aura::input-label class="!mt-0" for="email" :value="__('Email')" />
            <x-aura::input.text id="email" placeholder="{{ __('Enter your email') }}" class="block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-aura::input-error :for="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-aura::input-label class="!mt-0" for="password" :value="__('Password')" />

            <x-aura::input.text id="password" class="block w-full"
                            type="password"
                            name="password"
                            placeholder="{{ __('Create a password') }}"
                            required autocomplete="new-password" />

            <x-aura::input-error :for="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-aura::input-label class="!mt-0" for="password_confirmation" :value="__('Confirm Password')" />

            <x-aura::input.text id="password_confirmation" class="block w-full"
                            type="password"
                            name="password_confirmation"
                            placeholder="{{ __('Repeat your password') }}"
                            required autocomplete="new-password" />

            <x-aura::input-error :for="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-1">
            <x-aura::button id="register-button" type="submit" block>{{ __('Register') }}</x-aura::button>
        </div>
    </form>

    <p class="mt-6 text-sm text-center text-gray-500 dark:text-gray-400">
        {{ __('Already have an account?') }} <a class="font-medium rounded-md text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-900" href="/login">{{ __('Log in') }}</a>
    </p>

</x-aura::layout.guest>
