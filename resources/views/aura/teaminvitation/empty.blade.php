{{-- Empty invitations page: rendered inside list-view's empty block (title, one line, action), with Invite as its action. --}}
<h3 class="aura-section-title">{{ __('No invitations yet') }}</h3>
<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Invite a teammate by email.') }}</p>

@if ($this->settings['create'] && config('aura.auth.user_invitations'))
    <div class="mt-4">
        <x-aura::button wire:click.prevent="$dispatch('openModal', { component: 'aura::invite-user'})">
            <x-slot:icon>
                <x-aura::icon icon="plus" />
            </x-slot>
            <span>{{ __('Invite') }}</span>
        </x-aura::button>
    </div>
@endif
