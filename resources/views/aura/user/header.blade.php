@php
    // Same anatomy as the default table header (components/table/header): page title with the record
    // count, or a section title inside a record, then the actions.
    $isRelationTable = (bool) optional(optional($this)->field)['name'];
    $rowCount = isset($rows) && method_exists($rows, 'total') ? $rows->total() : null;
    $createLabel = __('New :resource', ['resource' => Str::lower(__($this->model->singularName()))]);
    $buttonSize = $isRelationTable ? 'sm' : 'base';
@endphp
<div class="flex gap-4 justify-between items-center {{ $isRelationTable ? 'mt-2 mb-3 min-h-8' : 'mt-1 min-h-9 sm:mt-8 sm:mb-8 sm:min-h-[3.375rem] sm:items-end' }}" @if ($isRelationTable) data-relation-header @endif>
    <div class="min-w-0">
        @if ($isRelationTable)
            <h2 class="aura-section-title">
                {{ __($this->field['name']) }}
                @if ($rowCount !== null)
                    <span class="aura-section-count">{{ number_format($rowCount) }}</span>
                @endif
            </h2>
        @else
            <h1 class="aura-page-title">
                {{ __($this->model->pluralName()) }}
                @if ($rowCount !== null && $rowCount > 0)
                    <span class="aura-page-count">{{ number_format($rowCount) }}</span>
                @endif
            </h1>
        @endif

        @if (optional(optional($this)->field)['description'])
            <span class="aura-meta">{{ __($this->field['description']) }}</span>
        @endif
    </div>

    {{-- On the page the actions take the width the title leaves, so the create button can decide whether
         "New user" fits (table/create-button); in a record section they keep their size. "Invite user" is
         the secondary action: an outline button right before "New user" from sm; on phones it leads the
         toolbar's actions menu (aura/user/actions-menu), so the page shows one "more" button. Without that
         menu (no bulk actions, no records yet) the title row keeps a "more" menu of its own after New user. --}}
    @php
        $canInvite = config('aura.auth.user_invitations');
        // The toolbar has a phone "more" menu when it renders the actions menu or, without bulk actions, the
        // Columns menu (components/table/settings, phoneMenu 'more'); both lead with aura/user/actions-menu.
        $inviteInActionsMenu = ! ($collectionIsEmpty ?? false) && (
            ($this->settings['bulk_actions'] && $this->bulkActions
                && ($this->settings['views']['bulk_actions'] ?? null) === 'aura::components.table.bulk-actions')
            || ($this->settings['settings'] && $this->currentView === 'list' && $this->model->showTableSettings())
        );
    @endphp
    <div class="flex gap-2 items-center {{ $isRelationTable ? 'shrink-0' : 'flex-1 justify-end' }}">
        @if ($canInvite)
            <div @class(['shrink-0', 'hidden sm:block' => ! $isRelationTable]) data-invite-user>
                <x-aura::button.border :size="$buttonSize" wire:click.prevent="$dispatch('openModal', { component: 'aura::invite-user'})">
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                    </x-slot>
                    <span>{{ __('Invite user') }}</span>
                </x-aura::button.border>
            </div>
        @endif

        @if ($this->settings['create_in_modal'])
            <a href="#" @class(['flex-1 block sm:flex-none' => ! $isRelationTable]) wire:click.prevent="$dispatch('openModal', 'resource.create-modal', {{ json_encode(['type' => $this->model->getType(), 'params' => [
            'for' => $this->parent->getType(), 'id' => $this->parent->id
            ]]) }})">
                @if ($isRelationTable)
                    <x-aura::button.border size="sm">
                        <x-slot:icon>
                            <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>{{ $createLabel }}</span>
                    </x-aura::button.border>
                @else
                    <div class="sm:hidden">
                        <x-aura::table.create-button :label="$createLabel" />
                    </div>
                    <div class="hidden sm:block">
                        <x-aura::button>
                            <x-slot:icon>
                                <x-aura::icon icon="plus" />
                            </x-slot>
                            <span>{{ $createLabel }}</span>
                        </x-aura::button>
                    </div>
                @endif
            </a>
        @elseif ($isRelationTable)
            <x-aura::button.border size="sm" href="{{ $this->createLink }}">
                <x-slot:icon>
                    <x-aura::icon icon="plus" />
                </x-slot>
                <span>{{ $createLabel }}</span>
            </x-aura::button.border>
        @else
            {{-- Phones: the fitting create button (full label or "New"); from sm there is room for the full label. --}}
            <div class="flex-1 sm:hidden">
                <x-aura::table.create-button :href="$this->createLink" :label="$createLabel" />
            </div>
            <div class="hidden shrink-0 sm:block">
                <x-aura::button :href="$this->createLink">
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                    </x-slot>
                    <span>{{ $createLabel }}</span>
                </x-aura::button>
            </div>
        @endif

        @if ($canInvite && ! $isRelationTable && ! $inviteInActionsMenu)
            <div class="shrink-0 sm:hidden" data-invite-user-menu>
                <x-aura::dropdown align="right" width="w-44">
                    <x-slot name="trigger">
                        <x-aura::button.border type="button" class="w-9 !px-0 [&>span:first-child]:ml-0" title="{{ __('More actions') }}" aria-label="{{ __('More actions') }}" aria-haspopup="menu">
                            <x-slot:icon>
                                <x-aura::icon.dots />
                            </x-slot>
                        </x-aura::button.border>
                    </x-slot>

                    <x-slot name="content">
                        <x-aura::dropdown-link href="#" wire:click.prevent="$dispatch('openModal', { component: 'aura::invite-user'})" role="menuitem">
                            {{ __('Invite user') }}
                        </x-aura::dropdown-link>
                    </x-slot>
                </x-aura::dropdown>
            </div>
        @endif
    </div>
</div>
