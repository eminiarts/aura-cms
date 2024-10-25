@if ($this->settings['header_before'])
    {{ app('aura')::injectView('header_before') }}
@endif

@if ($this->settings['header'])
    @if (View::exists($view = 'aura.' . Str::lower($model->getType()) . '.header'))
        @include($view)
    @elseif(View::exists('aura::' . $view))
        @include('aura::' . $view)
    @else
        <div class="flex justify-between items-center mt-6">

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
                </div>
            @endif


            @can('create', $model)
                @if ($this->settings['create'])
                    <div class="create-resource">
                        <div>
                            @if ($this->createInModal)
                                <a href="#"
                                    wire:click.prevent="$dispatch('openModal', { component: 'aura::resource-create-modal', arguments: {
                                type: '{{ $this->model->getType() }}',
                                params: {
                                    'for': '{{ $this->field['relation'] ?? $parent->getType() }}',
                                    'id': '{{ $parent->id }}'
                                }
                            }})">
                                    <x-aura::button>
                                        <x-slot:icon>
                                            <x-aura::icon icon="plus" />
                                        </x-slot>
                                        <span>Create {{ $model->getName() }}</span>
                                    </x-aura::button>
                                </a>
                            @else
                                <x-aura::button href="{{ $this->createLink }}">
                                    <x-slot:icon>
                                        <x-aura::icon icon="plus" />
                                    </x-slot>
                                    <span>{{ __('Create') }} {{ $model->getName() }}</span>
                                </x-aura::button>
                            @endif
                        </div>
                    </div>
                @endif
            @endcan

        </div>
    @endif
@endif
@if ($this->settings['header_after'])
    {{ app('aura')::injectView('header_after') }}
@endif
