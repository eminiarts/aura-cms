{{-- Empty invitations page: rendered inside list-view's centred empty block (icon tile, title, one line, the
     action). The page header leaves Invite out while the list is empty, so this is the one Invite button. --}}
<h3 class="aura-section-title">{{ __('No invitations yet') }}</h3>
<p class="mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ __('Invite a user by email.') }}</p>
@if ($this->settings['create'] && config('aura.auth.user_invitations'))
    <div class="mt-5">
        <x-aura::button wire:click.prevent="$dispatch('openModal', { component: 'aura::invite-user'})">
            <x-slot:icon>
                <x-aura::icon icon="plus" />
            </x-slot>
            <span>{{ __('Invite user') }}</span>
        </x-aura::button>
    </div>
@endif
