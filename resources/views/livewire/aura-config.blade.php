<div>
    <div class="p-2 bg-white dark:bg-gray-800 md:p-8 rounded-xl shadow-card dark:shadow-none">

        <div>
            <h2 class="text-3xl font-semibold">Aura Configuration</h2>

            <div class="my-8">
                <h3 class="text-lg font-semibold">Aura CMS Features</h3>
                <span class=""> Enable or disable Features from Aura.</span>
            </div>
        </div>

        {{-- @dump($post['fields']) --}}


    {{-- save button --}}
    <div class="flex justify-end">
        <x-aura::button.primary wire:click="save" wire:loading.attr="disabled">
            {{-- <x-aura::loading wire:loading wire:target="save" /> --}}
            Save
        </x-aura::button.primary>
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
