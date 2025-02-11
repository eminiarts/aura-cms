<x-aura::layout.guest>
    {{-- current url action --}}
    <form method="POST" action="{{ url()->full() }}">
        @csrf

        <!-- Team -->
        <div>
            <x-aura::input-label for="team" :value="__('Team')" />
            <x-aura::input.text id="team" class="block mt-1 w-full text-black bg-gray-200 opacity-50" type="text" name="team" :value="$team->name" disabled />
            <x-aura::input-error :for="$errors->get('team')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-aura::input-label for="email" :value="__('Email')" />
            <x-aura::input.text id="email" class="block mt-1 w-full text-black bg-gray-200 opacity-50" type="email" name="email" :value="$teamInvitation->email" disabled />
            <x-aura::input-error :for="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Name -->
        <div class="mt-4">
            <x-aura::input-label for="name" :value="__('Name')" />
            <x-aura::input.text id="name" class="block mt-1 w-full bg-gray-200" type="text" name="name" :value="old('name')" required />
            <x-aura::input-error :for="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-aura::input-label for="password" :value="__('Password')" />

            <x-aura::input.text id="password" class="block mt-1 w-full bg-gray-200"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-aura::input-error :for="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-aura::input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-aura::input.text id="password_confirmation" class="block mt-1 w-full bg-gray-200"
                            type="password"
                            name="password_confirmation" required />

            <x-aura::input-error :for="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex justify-end items-center mt-6">
            <x-aura::button id="register-button" type="submit" block>{{ __('Register') }}</x-aura::button>
        </div>

        <div class="flex justify-center mt-6 text-sm">
            <span><a class="text-sm text-gray-600 underline rounded-md hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" href="/login">{{ __('Already registered?') }}</a></span>
        </div>
    </form>
</x-aura::layout.guest>
