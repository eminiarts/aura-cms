{{ app('aura')::injectView('header_before') }}

{{-- if a view exists: aura.$model->pluralName().header, load it  --}}
@if(View::exists($view = 'aura.' . $model->getType() . '.header'))
@include($view)
@elseif(View::exists('aura::' . $view))
@include('aura::' . $view)
@else
<div class="flex items-center justify-between mt-6">
    <div>
        @if(optional(optional($this)->field)['name'])
        <h1 class="text-3xl font-semibold">{{ __($this->field['name']) }}</h1>
        @else
        <h1 class="text-3xl font-semibold">{{ __($model->pluralName()) }}</h1>
        @endif

        @if(optional(optional($this)->field)['description'])
        <span class="text-primary-500">{{ __($this->field['description']) }}</span>
        @endif
        </h3>
    </div>

    <div>
        <div>
            @if($this->createInModal)
            <a href="#" wire:click.prevent="$dispatch('openModal', 'aura::post-create-modal', {{ json_encode(['type' => $this->model->getType(), 'params' => [
                'for' => $this->field['relation'] ?? $parent->getType(), 'id' => $parent->id
                ]]) }})">
                <x-aura::button>
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>Create {{ $model->getName() }}</span>
                </x-aura::button>
            </a>
            @else
            <a href="{{ $this->createLink }}">
                <x-aura::button>
                    <x-slot:icon>
                        <x-aura::icon icon="plus" />
                        </x-slot>
                        <span>{{ __('Create') }} {{ $model->getName() }}</span>
                </x-aura::button>
            </a>
            @endif
        </div>
    </div>
</div>
@endif

{{ app('aura')::injectView('header_after') }}