<div class="p-8">
    <h2 class="text-3xl font-semibold">{{ __('Create Posttype') }}</h2>

    <div class="mb-4 -mx-4">
        @foreach($this->fields as $key => $field)
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
        <div wire:key="post-field-{{ $key }}"
        id="post-field-{{ optional($field)['slug'] }}-wrapper">
        <x-dynamic-component :component="$field['field']->component" :field="$field" />
        </div>
        @endforeach
    </div>

    <x-aura::button size="xl" wire:click="save">
        <div wire:loading>
            <x-aura::icon.loading  />
        </div>
        Save
    </x-aura::button>

</div>
