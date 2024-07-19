
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

@once
    @assets
        @vite(['resources/js/tagify.js'], 'vendor/aura/libs')
    @endassets
@endonce

<x-aura::fields.wrapper :field="$field">

<div
wire:ignore
x-data="{
    multiple: true,
    value: @entangle('form.fields.' . $field['slug']),
    options: @js($values),
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
                maxTags: 10,
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

<input x-ref="tags" name='tags' class='px-3 py-2 w-full bg-white rounded-lg appearance-none shadow-xs border-gray-500/30 tags focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700' placeholder='{{ $field['name'] }}'>

</div>
</x-aura::fields.wrapper>
