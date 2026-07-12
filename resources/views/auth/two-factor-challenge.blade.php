<x-aura::layout.guest>
    <div x-data="{ recovery: false }">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-white">{{ __('Two-factor authentication') }}</h1>

            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-show="! recovery">
                {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
            </p>

            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400" x-show="recovery">
                {{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}
            </p>
        </div>

        <x-aura::validation-errors class="mb-6" />

        <form method="POST" action="{{ route('two-factor.login') }}" class="space-y-5">
            @csrf

            <div x-show="! recovery">
                <x-aura::fields.label :label="__('Code')" />
                <x-aura::input id="code" class="block mt-1 w-full font-mono text-lg text-center tracking-[0.4em]" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code" />
            </div>

            <div x-show="recovery">
                <x-aura::fields.label :label="__('Recovery Code')" />
                <x-aura::input id="recovery_code" class="block mt-1 w-full font-mono tracking-widest text-center" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code" />
            </div>

            <div class="pt-1">
                <x-aura::button type="submit" block>
                    {{ __('Log in') }}
                </x-aura::button>
            </div>

            <div class="text-center">
                <button type="button" class="text-sm font-medium rounded-md cursor-pointer text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
                                x-show="! recovery"
                                x-on:click="
                                    recovery = true;
                                    $nextTick(() => { $refs.recovery_code.focus() })
                                ">
                    {{ __('Use a recovery code') }}
                </button>

                <button type="button" class="text-sm font-medium rounded-md cursor-pointer text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-800"
                                x-show="recovery"
                                x-on:click="
                                    recovery = false;
                                    $nextTick(() => { $refs.code.focus() })
                                ">
                    {{ __('Use an authentication code') }}
                </button>
            </div>
        </form>
    </div>
</x-aura::layout.guest>
