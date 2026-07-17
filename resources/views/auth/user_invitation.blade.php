<x-aura::layout.guest>

    <div class="mb-8">
        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Join') }} {{ $team->name }}</h1>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ __('You have been invited. Create your account below to get started.') }}</p>
    </div>

    {{-- current url action --}}
    <form method="POST" action="{{ url()->full() }}" class="space-y-5">
        @csrf

        <!-- Team -->
        <div>
            <x-aura::input-label class="!mt-0" for="team" :value="__('Team')" />
            <x-aura::input.text id="team" class="block w-full opacity-60 cursor-not-allowed" type="text" name="team" :value="$team->name" disabled />
            <x-aura::input-error :for="$errors->get('team')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-aura::input-label class="!mt-0" for="email" :value="__('Email')" />
            <x-aura::input.text id="email" class="block w-full opacity-60 cursor-not-allowed" type="email" name="email" :value="$teamInvitation->email" disabled />
            <x-aura::input-error :for="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Name -->
        <div>
            <x-aura::input-label class="!mt-0" for="name" :value="__('Name')" />
            <x-aura::input.text id="name" class="block w-full" type="text" name="name" :value="old('name')" required />
            <x-aura::input-error :for="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-aura::input-label class="!mt-0" for="password" :value="__('Password')" />

            <x-aura::input.text id="password" class="block w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-aura::input-error :for="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-aura::input-label class="!mt-0" for="password_confirmation" :value="__('Confirm Password')" />

            <x-aura::input.text id="password_confirmation" class="block w-full"
                            type="password"
                            name="password_confirmation" required />

            <x-aura::input-error :for="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="pt-1">
            <x-aura::button id="register-button" type="submit" block>{{ __('Register') }}</x-aura::button>
        </div>
    </form>

    <p class="mt-6 text-sm text-center text-gray-500 dark:text-gray-400">
        <a class="font-medium rounded-md text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-900" href="/login">{{ __('Already registered?') }}</a>
    </p>
</x-aura::layout.guest>
