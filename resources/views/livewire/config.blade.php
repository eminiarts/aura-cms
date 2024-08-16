<div>
    @section('title', 'Global Config ')
    <div class="mb-6">
        <x-aura::breadcrumbs>
            <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" iconClass="text-gray-500 w-7 h-7 mr-0" />
            <x-aura::breadcrumbs.li title="Global Config" />
        </x-aura::breadcrumbs>
    </div>

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="mb-2 text-3xl font-semibold">Configuration</h1>
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
            <x-dynamic-component :component="$field['field']->edit()" :field="$field" :form="$form" />
        </div>
    </x-aura::fields.conditions>
    @endforeach

</div>
