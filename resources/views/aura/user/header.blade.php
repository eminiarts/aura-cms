@php
    // Same anatomy as the default table header (components/table/header): page title with the record
    // count, or a section title inside a record, then the actions. Invite is the secondary action.
    $isRelationTable = (bool) optional(optional($this)->field)['name'];
    $rowCount = isset($rows) && method_exists($rows, 'total') ? $rows->total() : null;
    $createLabel = __('New :resource', ['resource' => Str::lower(__($this->model->singularName()))]);
    $buttonSize = $isRelationTable ? 'sm' : 'base';
@endphp
<div class="flex gap-4 justify-between items-center {{ $isRelationTable ? 'mt-2 mb-3 min-h-8' : 'mt-1 min-h-9 sm:mt-6' }}" @if ($isRelationTable) data-relation-header @endif>
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

    <div class="flex gap-2 items-center shrink-0">
        @if (config('aura.auth.user_invitations'))
            <x-aura::button.border :size="$buttonSize" wire:click.prevent="$dispatch('openModal', { component: 'aura::invite-user'})">
                <x-slot:icon>
                    <x-aura::icon icon="plus" />
                </x-slot>
                <span>{{ __('Invite') }}</span>
            </x-aura::button.border>
        @endif

        @if ($this->settings['create_in_modal'])
            <a href="#" wire:click.prevent="$dispatch('openModal', 'resource.create-modal', {{ json_encode(['type' => $this->model->getType(), 'params' => [
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
                    <x-aura::button>
                        <x-slot:icon>
                            <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>{{ $createLabel }}</span>
                    </x-aura::button>
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
            <x-aura::button href="{{ $this->createLink }}">
                <x-slot:icon>
                    <x-aura::icon icon="plus" />
                </x-slot>
                <span>{{ $createLabel }}</span>
            </x-aura::button>
        @endif
    </div>
</div>
