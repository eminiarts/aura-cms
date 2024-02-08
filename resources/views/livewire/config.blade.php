<div>
    @section('title', 'Gobal Config • ')

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-semibold">Configuration</h1>
            <h3>These Settings are applied globally.</h3>
        </div>

        <div class="">
            <x-aura::button.primary wire:click="save" wire:loading.attr="disabled">
                {{-- <x-aura::loading wire:loading wire:target="save" /> --}}
                Save
            </x-aura::button.primary>
        </div>

    </div>

    @foreach($this->fieldsForView as $key => $field)
    <style >
        #resource-field- {
                {
                optional($field)['slug']
            }
        }

        -wrapper {
            width: {
                    {
                    optional(optional($field)['style'])['width'] ?? '100'
                }
            }

            %;
        }

        @media screen and (max-width: 768px) {
            #resource-field- {
                    {
                    optional($field)['slug']
                }
            }

            -wrapper {
                width: 100%;
            }
        }

    </style>
    <x-aura::fields.conditions :field="$field" :model="$this">
        <div wire:key="resource-field-{{ $key }}" id="resource-field-{{ optional($field)['slug'] }}-wrapper">
            <x-dynamic-component :component="$field['field']->component" :field="$field" />
        </div>
    </x-aura::fields.conditions>
    @endforeach






</div>
