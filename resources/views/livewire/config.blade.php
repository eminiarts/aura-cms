<div>
    @section('title', 'Global Config ')

    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-semibold">Configuration</h1>
            <h3>These Settings are applied globally.</h3>
        </div>

        <div class="">
            <x-aura::button size="xl" wire:click="save">
                <div wire:loading>
                    <x-aura::icon.loading  />
                </div>
                {{ __('Save') }}
            </x-aura::button>
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
            <x-dynamic-component :component="$field['field']->component" :field="$field" :form="$form" />
        </div>
    </x-aura::fields.conditions>
    @endforeach

</div>
