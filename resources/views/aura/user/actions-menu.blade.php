{{-- Phones: "Invite user" leads the list toolbar's "more" menu (components/table/actions-menu-items), so the
     users page shows one "more" button; from sm it is an outline button in the title row (aura/user/header). --}}
@if (config('aura.auth.user_invitations') && ! optional(optional($this)->field)['name'])
    <div class="sm:hidden">
        <button type="button" wire:click.prevent="$dispatch('openModal', { component: 'aura::invite-user'})" class="w-full {{ $itemClass }}" role="menuitem" tabindex="-1">
            {{ __('Invite user') }}
        </button>
        <div class="-mx-1 my-1 h-px bg-gray-950/5 dark:bg-white/10" role="separator"></div>
    </div>
@endif
