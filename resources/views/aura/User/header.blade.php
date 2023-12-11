<div class="flex justify-between items-center mt-6">
    <div>
         @if(optional(optional($this)->field)['name'])
                <h1 class="text-3xl font-semibold">{{ __( $this->field['name']) }}</h1>
                @else
                <h1 class="text-3xl font-semibold">{{ __($model->pluralName()) }}</h1>
                @endif

                @if(optional(optional($this)->field)['description'])
                <span class="text-primary-500">{{ $this->field['description'] }}</span>
                @endif
        </h3>
    </div>

    <div>
        <div>
            @if(app('aura')::option('user_invitations'))
            <a href="#" wire:click.prevent="$emit('openModal', 'aura::invite-user')">
                <x-aura::button.light>
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>{{ __('Invite') }}</span>
                </x-aura::button.light>
            </a>
            @endif

            @if($this->createInModal)
            <a href="#" wire:click.prevent="$emit('openModal', 'post.create-modal', {{ json_encode(['type' => $this->model->getType(), 'params' => [
            'for' => $this->parent->getType(), 'id' => $this->parent->id
            ]]) }})">
                <x-aura::button>
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>{{ __('Create :modelName', ['modelName' => $model->singularName()]) }}</span>
                </x-aura::button>
            </a>
            @else
            @can('create', $model)
            <a href="{{ $this->createLink }}">
                <x-aura::button>
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        {{-- <span>{{ __('Create :modelName', ['modelName' => $model->singularName()]) }}</span> --}}
                        <span>{{ __('Create') }} {{ $model->getName() }}</span>
                </x-aura::button>

            </a>
            @endcan
            @endif
        </div>
    </div>
</div>
