<div class="flex items-center justify-between mt-6">
    <div>
         @if(optional(optional($this)->field)['name'])
                <h1 class="text-3xl font-semibold">{{ $this->field['name'] }}</h1>
                @else
                <h1 class="text-3xl font-semibold">{{ $this->model->pluralName() }}</h1>
                @endif

                @if(optional(optional($this)->field)['description'])
                <span class="text-primary-500">{{ $this->field['description'] }}</span>
                @endif
        </h3>
    </div>

    <div>
        <div>
            @if(app('aura')::option('user_invitations'))
                <x-aura::button.light wire:click.prevent="$dispatch('openModal', { component: 'aura::invite-user'})">
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>Invite</span>
                </x-aura::button.light>
            @endif

            @if($this->createInModal)
            <a href="#" wire:click.prevent="$dispatch('openModal', 'resource.create-modal', {{ json_encode(['type' => $this->model->getType(), 'params' => [
            'for' => $this->parent->getType(), 'id' => $this->parent->id
            ]]) }})">
                <x-aura::button>
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>Create {{ $this->model->singularName() }}</span>
                </x-aura::button>
            </a>
            @else
            <a href="{{ $this->createLink }}">
                <x-aura::button>
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>Create {{ $this->model->singularName() }}</span>
                </x-aura::button>
            </a>
            @endif
        </div>
    </div>
</div>
