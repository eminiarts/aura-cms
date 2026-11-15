@php
    // Same anatomy as the default table header (components/table/header): page title with the record
    // count and the bookmark of a top-level page, or a section title inside a record. An empty list page
    // leaves Invite to its empty block (aura/teaminvitation/empty).
    $isRelationTable = (bool) optional(optional($this)->field)['name'];
    $rowCount = isset($rows) && method_exists($rows, 'total') ? $rows->total() : null;
    $pageBookmark = ! $isRelationTable && ($this->settings['page_bookmark'] ?? false);
@endphp
<div class="flex gap-4 justify-between items-center {{ $isRelationTable ? 'mt-2 mb-3 min-h-8' : 'mt-1 min-h-9 sm:mb-8 sm:min-h-[3.375rem] sm:items-end'.($pageBookmark ? ' sm:mt-0' : ' sm:mt-8') }}" @if ($isRelationTable) data-relation-header @endif>
    @if ($this->settings['title'])
        @if ($isRelationTable)
            <h2 class="aura-section-title">
                {{ __($this->field['name']) }}
                @if ($rowCount !== null)
                    <span class="aura-section-count">{{ number_format($rowCount) }}</span>
                @endif
            </h2>
        @else
            <div class="flex gap-1 items-center min-w-0">
                <h1 class="aura-page-title">
                    {{ __($model->pluralName()) }}
                    @if ($rowCount !== null && ! ($collectionIsEmpty ?? false))
                        <span class="aura-page-count">{{ number_format($rowCount) }}</span>
                    @endif
                </h1>
                @if ($pageBookmark)
                    <x-aura::page-bookmark />
                @endif
            </div>
        @endif
    @endif

    @if ($this->settings['create'] && config('aura.auth.user_invitations') && ! ($emptyPage ?? false))
        @if ($isRelationTable)
            <x-aura::button.border size="sm" wire:click.prevent="$dispatch('openModal', { component: 'aura::invite-user'})">
                <x-slot:icon>
                    <x-aura::icon icon="plus" />
                </x-slot>
                <span>{{ __('Invite user') }}</span>
            </x-aura::button.border>
        @else
            <x-aura::button wire:click.prevent="$dispatch('openModal', { component: 'aura::invite-user'})">
                <x-slot:icon>
                    <x-aura::icon icon="plus" />
                </x-slot>
                <span>{{ __('Invite user') }}</span>
            </x-aura::button>
        @endif
    @endif
</div>
