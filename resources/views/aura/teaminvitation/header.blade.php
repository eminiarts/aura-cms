<div class="flex justify-between items-center mt-6">

    @if ($this->settings['title'])
        <h1 class="text-3xl font-semibold">{{ __($model->pluralName()) }}</h1>
    @endif


        @if ($this->settings['create'])
              @if(config('aura.auth.user_invitations'))
                <x-aura::button.light wire:click.prevent="$dispatch('openModal', { component: 'aura::invite-user'})">
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>Invite</span>
                </x-aura::button.light>
            @endif
        @endif
</div>
