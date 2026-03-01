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
    {{-- Simple inline modal - no x-dialog, no x-teleport --}}
    <div x-data="{ open: @entangle('showInviteUserModal') }" x-on:keydown.escape.window="open = false" x-on:close-invite-modal.window="open = false">
        {{-- Backdrop overlay --}}
        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-on:click="open = false" class="fixed inset-0 bg-gray-900/50 dark:bg-gray-900/70" style="z-index: 99;" x-cloak></div>

        {{-- Modal panel --}}
        <div x-show="open" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="fixed inset-0 flex items-center justify-center p-4" style="z-index: 100;" x-cloak>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-lg" x-on:click.stop>
                @livewire('aura::invite-user')
            </div>
        </div>
    </div>
@endif
