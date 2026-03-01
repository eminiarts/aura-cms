<div class="flex items-center justify-between mt-6">
    <div>
         @if(optional(optional($this)->field)['name'])
                <h1 class="text-2xl font-semibold">{{ __($this->field['name']) }}</h1>
                @else
                <h1 class="text-2xl font-semibold">{{ __($this->model->pluralName()) }}</h1>
                @endif

                @if(optional(optional($this)->field)['description'])
                <span class="text-primary-500">{{ __($this->field['description']) }}</span>
                @endif
        </h3>
    </div>

    <div>
        <div>
            @if(config('aura.auth.user_invitations'))
                <x-aura::button.light wire:click.prevent="$toggle('showInviteUserModal')">
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>{{ __('Invite') }}</span>
                </x-aura::button.light>
            @endif

            @if($this->settings['create_in_modal'])
            <a href="#" wire:click.prevent="$dispatch('openModal', 'resource.create-modal', {{ json_encode(['type' => $this->model->getType(), 'params' => [
            'for' => $this->parent->getType(), 'id' => $this->parent->id
            ]]) }})">
                <x-aura::button>
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>{{ __('Create ' . $this->model->singularName()) }}</span>
                </x-aura::button>
            </a>
            @else
            <a href="{{ $this->createLink }}">
                <x-aura::button>
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>{{ __('Create ' . $this->model->singularName()) }}</span>
                </x-aura::button>
            </a>
            @endif
        </div>
    </div>
</div>

@if(config('aura.auth.user_invitations'))
    {{-- Backdrop --}}
    <div class="{{ $this->showInviteUserModal ? 'fixed inset-0' : 'hidden' }}" style="z-index: 99; background: rgba(17,24,39,0.5);" wire:click="$toggle('showInviteUserModal')"></div>

    {{-- Modal - always rendered, toggled via CSS class --}}
    <div class="{{ $this->showInviteUserModal ? 'fixed inset-0 flex items-center justify-center p-4' : 'hidden' }}" style="z-index: 100;">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg" wire:click.stop>
            @livewire('aura::invite-user')
        </div>
    </div>
@endif
