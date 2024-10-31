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
        'flex items-center w-full bg-white rounded-lg appearance-none shadow-xs border-gray-500/30 tags focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700 disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed': true,
        'min-h-[42px] pl-3 pr-10 py-2': size === 'default',
        'min-h-[34px] pl-0 pr-4 py-0 text-xs': size === 'xs'
    }"
    placeholder='{{ $field['name'] }}'
    :disabled="disabled"
>

</div>
