<div>
    <div class="p-2 bg-white dark:bg-gray-800 md:p-8 rounded-xl shadow-card dark:shadow-none">

        <div>
            <h2 class="text-3xl font-semibold">Aura Configuration</h2>

            <div class="my-8">
                <h3 class="text-lg font-semibold">Aura CMS Features</h3>
                <span class="font-light text-gray-400"> Enable or disable Features from Aura.</span>
            </div>
        </div>



    @foreach($this->fieldsForView as $key => $field)
    <style>
        #post-field-{{ optional($field)['slug'] }}-wrapper {
            width: {{ optional(optional($field)['style'])['width'] ?? '100' }}%;
        }
        
        @media screen and (max-width: 768px) {
            #post-field-{{ optional($field)['slug'] }}-wrapper {
                width: 100%;
            }
        }
    </style>
    <x-aura::fields.conditions :field="$field" :model="$this">
        <div wire:key="post-field-{{ $key }}"
        id="post-field-{{ optional($field)['slug'] }}-wrapper">
        <x-dynamic-component :component="$field['field']->component" :field="$field" />
        </div>
    </x-aura::fields.conditions>
    @endforeach

        
            

    </div>


</div>
