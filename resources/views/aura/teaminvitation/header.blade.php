@php
    // Same anatomy as the default table header (components/table/header): page title with the record
    // count, or a section title inside a record. Without invitations the page's empty state holds the
    // Invite button (aura/teaminvitation/empty), so it is not shown twice.
    $isRelationTable = (bool) optional(optional($this)->field)['name'];
    $rowCount = isset($rows) && method_exists($rows, 'total') ? $rows->total() : null;
    $inviteInEmptyState = ! $isRelationTable && ($collectionIsEmpty ?? false) && ($currentView ?? null) === 'list';
@endphp
<div class="flex gap-4 justify-between items-center {{ $isRelationTable ? 'mt-2 mb-3 min-h-8' : 'mt-1 min-h-9 sm:mt-6' }}" @if ($isRelationTable) data-relation-header @endif>
    @if ($this->settings['title'])
        @if ($isRelationTable)
            <h2 class="aura-section-title">
                {{ __($this->field['name']) }}
                @if ($rowCount !== null)
                    <span class="aura-section-count">{{ number_format($rowCount) }}</span>
                @endif
            </h2>
        @else
            <h1 class="aura-page-title">
                {{ __($model->pluralName()) }}
                @if ($rowCount !== null && ! ($collectionIsEmpty ?? false))
                    <span class="aura-page-count">{{ number_format($rowCount) }}</span>
                @endif
            </h1>
        @endif
    @endif

    @if ($this->settings['create'] && config('aura.auth.user_invitations') && ! $inviteInEmptyState)
        @if ($isRelationTable)
            <x-aura::button.border size="sm" wire:click.prevent="$dispatch('openModal', { component: 'aura::invite-user'})">
                <x-slot:icon>
                    <x-aura::icon icon="plus" />
                </x-slot>
                <span>{{ __('Invite') }}</span>
            </x-aura::button.border>
        @else
            <x-aura::button wire:click.prevent="$dispatch('openModal', { component: 'aura::invite-user'})">
                <x-slot:icon>
                    <x-aura::icon icon="plus" />
                </x-slot>
                <span>{{ __('Invite') }}</span>
            </x-aura::button>
        @endif
    @endif
</div>
