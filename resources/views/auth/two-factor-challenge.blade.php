<x-aura::guest-layout>
    <div>
        <x-slot name="logo">
            logo here
        </x-slot>

        <div x-aura::data="{ recovery: false }">
            <div class="mb-4 text-sm text-gray-600" x-aura::show="! recovery">
                {{ __('Please confirm access to your account by entering the authentication code provided by your authenticator application.') }}
            </div>

            <div class="mb-4 text-sm text-gray-600" x-aura::show="recovery">
                {{ __('Please confirm access to your account by entering one of your emergency recovery codes.') }}
            </div>

            <x-aura::validation-errors class="mb-4" />

            <form method="POST" action="{{ route('two-factor.login') }}">
                @csrf

                <div class="mt-4" x-aura::show="! recovery">
                    <x-aura::fields.label :label="__('Code')" />
                    <x-aura::input id="code" class="block mt-1 w-full" type="text" inputmode="numeric" name="code" autofocus x-aura::ref="code" autocomplete="one-time-code" />
                </div>

                <div class="mt-4" x-aura::show="recovery">
                    <x-aura::fields.label :label="__('Recovery Code')" />
                    <x-aura::input id="recovery_code" class="block mt-1 w-full" type="text" name="recovery_code" x-aura::ref="recovery_code" autocomplete="one-time-code" />
                </div>

                <div class="flex items-center justify-end mt-4">
                    <button type="button" class="text-sm text-gray-600 hover:text-gray-900 underline cursor-pointer"
                                    x-aura::show="! recovery"
                                    x-aura::on:click="
                                        recovery = true;
                                        $nextTick(() => { $refs.recovery_code.focus() })
                                    ">
                        {{ __('Use a recovery code') }}
                    </button>

                    <button type="button" class="text-sm text-gray-600 hover:text-gray-900 underline cursor-pointer"
                                    x-aura::show="recovery"
                                    x-aura::on:click="
                                        recovery = false;
                                        $nextTick(() => { $refs.code.focus() })
                                    ">
                        {{ __('Use an authentication code') }}
                    </button>

                    <x-aura::button class="ml-4" type="submit">
                        {{ __('Log in') }}
                    </x-aura::button>
                </div>
            </form>
        </div>
    </div>
</x-aura::guest-layout>
