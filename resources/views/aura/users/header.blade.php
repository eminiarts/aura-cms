<div class="flex items-center justify-between mt-6">
    <div>
        <h1 class="text-3xl font-semibold">Custom {{ $model->pluralName() }}</h1>

        @if($this->parent)
        <span class="text-primary-500">from {{ $this->parent->name }}</span>
        @endif
        </h3>
    </div>

    <div>
        <div>
            <a href="#">
                <x-aura::button.light>
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>Invite</span>
                </x-aura::button.light>
            </a>

            @if($this->createInModal)
            <a href="#" wire:click.prevent="$emit('openModal', 'post.create-modal', {{ json_encode(['type' => $this->model->getType(), 'params' => [
            'for' => $this->parent->getType(), 'id' => $this->parent->id
            ]]) }})">
                <x-aura::button>
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>Create {{ $model->singularName() }}</span>
                </x-aura::button>
            </a>
            @else
            <a href="{{ $this->createLink }}">
                <x-aura::button>
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>Create {{ $model->singularName() }}</span>
                </x-aura::button>
            </a>
            @endif
        </div>
    </div>
</div>
