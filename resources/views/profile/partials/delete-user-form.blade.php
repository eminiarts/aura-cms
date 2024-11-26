<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <x-aura::danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', { component: 'confirm-user-deletion' })"
    >{{ __('Delete Account') }}</x-aura::danger-button>

    <x-aura::dialog wire:model="showDeleteModal">
        <x-aura::dialog.open>
            <div x-data="" x-on:click="$dispatch('open-modal', { component: 'confirm-user-deletion' })">
                {{ $slot }}
            </div>
        </x-aura::dialog.open>

        <x-aura::dialog.panel>
            <form method="post" action="{{ route('aura.profile.destroy') }}">
                @csrf
                @method('delete')

                <x-aura::dialog.title>
                    {{ __('Are you sure you want to delete your account?') }}
                </x-aura::dialog.title>

                <div class="mt-5 text-gray-600">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}

                    <div class="mt-6">
                        <x-aura::input.wrapper label="Password" error="password">
                            <x-aura::input.text
                                type="password"
                                name="password"
                                error="password"
                                placeholder="Password"
                            />
                        </x-aura::input.wrapper>
                    </div>
                </div>

                <x-aura::dialog.footer>
                    <x-aura::dialog.close>
                        <x-aura::button.transparent>
                            {{ __('Cancel') }}
                        </x-aura::button.transparent>
                    </x-aura::dialog.close>

                    <x-aura::button.danger type="submit">
                        {{ __('Delete Account') }}
                    </x-aura::button.danger>
                </x-aura::dialog.footer>
            </form>
        </x-aura::dialog.panel>
    </x-aura::dialog>
</section>
