
@php

$values = [];
$taxonomy = null;

if (key_exists('resource', $field) ) {
    $values = app($field['resource'])->get()->map(function ($tag) {
        return ['id' => $tag->id, 'value' => $tag->title];
    })->toArray();

    $taxonomy = app($field['resource']);

}

@endphp

{{-- @dump($this->post['fields'])
@dump($values) --}}

<x-aura::fields.wrapper :field="$field">
<div
wire:ignore
x-data="{
    multiple: true,
    value: @entangle('post.fields.' . $field['slug']),
    options: @js($values),
    init() {
        if (!this.value) {
            this.value = [];
        }

        this.$nextTick(() => {

            let tagValues = [];

            if(this.value.length > 0) {
                tagValues = JSON.parse(this.value).map(id => {
                    let option = this.options.find(option => option.id === id);
                    return option ? {id: id, value: option.value} : {};
                });
            } 
           
            this.$refs.tags.value = JSON.stringify(tagValues);

            var tagify = new window.Tagify(this.$refs.tags, {
                whitelist: this.options,
                maxTags: 10,
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
                this.value = tagify.value.map(item => item.value);
            });
        });
    }
}"
class=""
>

<input x-ref="tags" name='tags' class='w-full px-3 py-2 bg-white rounded-lg shadow-xs appearance-none border-gray-500/30 tags focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700' placeholder='{{ $field['name'] }}'>

@once
@push('scripts')
{{-- <script src="https://unpkg.com/@yaireo/tagify"></script>
<script src="https://unpkg.com/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
<link href="https://unpkg.com/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" /> --}}
@endpush
@endonce
</div>
</x-aura::fields.wrapper>
