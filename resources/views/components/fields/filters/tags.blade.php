@php
$values = [];
$taxonomy = null;

if (key_exists('resource', $field) ) {
    $values = app($field['resource'])->get()->map(function ($tag) {
        return ['id' => $tag->id, 'value' => $tag->title()];
    })->toArray();
}

// dump($values);
// dump($this->form['fields']);
// dump($this->model->users);
@endphp

@assets
    @vite(['resources/js/tagify.js'], 'vendor/aura/libs')
@endassets

@aware(['model' => null, 'size' => 'xs'])

<div
wire:ignore
x-data="{
    multiple: true,

    value: @entangle($model),

    size: '{{ $size }}',

    options: @js($values),
    disabled: {{ isset($field['disabled']) && $field['disabled'] ? 'true' : 'false' }},
    init() {
        if (!this.value) {
            this.value = [];
        }

        this.$nextTick(() => {
            let tagValues = [];

            if(this.value.length > 0) {
                tagValues = this.value.map(id => {
                    let option = this.options.find(option => option.id === id);
                    return option ? {id: id, value: option.value} : {};
                });
            }

            this.$refs.tags.value = JSON.stringify(tagValues);

            var tagify = new window.Tagify(this.$refs.tags, {
                whitelist: this.options,
                readonly: this.disabled,

                @if(isset($field['max_tags']))
                    maxTags: {{ $field['max_tags'] }},
                @endif

                @if( optional($field)['create'] === false)
                enforceWhitelist: true,
                createInvalidTags: false,
                @endif
                value: tagValues,
                originalInputValueFormat: valuesArr => valuesArr.map(item => item.id),
                dropdown: {
                    maxItems: 20,
                    classname: 'tags-look',
                    enabled: 0,
                    closeOnSelect: false
                }
            });

            this.$refs.tags.addEventListener('change', (e) => {
                // Update the value to be an array of selected tag values
                this.value = tagify.value.map(item => item.id || item.value);
            });
        });
    }
}"
class=""
>

<input
    x-ref="tags"
    name='tags'
    :class="{
        'aura-field flex items-center w-full appearance-none tags': true,
        'aura-control-min-base pl-3 pr-10 py-0.5 text-sm': size === 'default',
        'aura-control-min-xs pl-0 pr-4 py-0 text-xs': size === 'xs'
    }"
    placeholder='{{ $field['name'] }}'
    :disabled="disabled"
>

</div>
