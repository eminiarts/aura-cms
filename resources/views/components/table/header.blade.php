@if ($this->settings['header_before'])
    {{ app('aura')::injectView('header_before') }}
@endif

{{-- if a view exists: aura.$model->pluralName().header, load it  --}}
@if (View::exists($view = 'aura.' . $model->getType() . '.header'))
    @include($view)
@elseif(View::exists('aura::' . $view))
    @include('aura::' . $view)
@else
    <div class="flex items-center justify-between mt-6">

        @if ($this->settings['title'])
            <div>
                @if (optional(optional($this)->field)['name'])
                    <h1 class="text-3xl font-semibold">{{ __($this->field['name']) }}</h1>
                @else
                    <h1 class="text-3xl font-semibold">{{ __($model->pluralName()) }}</h1>
                @endif

                @if (optional(optional($this)->field)['description'])
                    <span class="text-primary-500">{{ __($this->field['description']) }}</span>
                @endif
                </h3>
            </div>
        @endif


        @if ($this->settings['create'])
            <div>
                <div>
                    @if ($this->createInModal)
                        <a href="#"
                            wire:click.prevent="$emit('openModal', 'aura::post-create-modal', {{ json_encode([
                                'type' => $this->model->getType(),
                                'params' => [
                                    'for' => $this->field['relation'] ?? $parent->getType(),
                                    'id' => $parent->id,
                                ],
                            ]) }})">
                            <x-aura::button>
                                <x-slot:icon>
                                    <x-aura::icon icon="plus" />
                                </x-slot>
                                <span>Create {{ $model->getName() }}</span>
                            </x-aura::button>
                        </a>
                    @else
                        @can('create', $model)
                            <a href="{{ $this->createLink }}">
                                <x-aura::button>
                                    <x-slot:icon>
                                        <x-aura::icon icon="plus" />
                                    </x-slot>
                                    <span>{{ __('Create') }} {{ $model->getName() }}</span>
                                </x-aura::button>
                            </a>
                        @endcan
                    @endif
                </div>
            </div>
        @endif
    </div>
@endif
@if ($this->settings['header_after'])
    {{ app('aura')::injectView('header_after') }}
@endif
