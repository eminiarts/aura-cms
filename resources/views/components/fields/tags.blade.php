
@php
$values = app($field['resource'])->pluck('title', 'id')->toArray();

$taxonomy = app($field['resource']);

@endphp

{{-- @dump($this->post['terms']) --}}
{{-- @dump($taxonomy) --}}

<x-aura::fields.wrapper :field="$field">
<div
wire:ignore
x-data="{
    multiple: true,
    value: @entangle('post.fields.' . $field['slug']).defer,
    options: {{ Js::from($values) }},
    init() {
        if (!this.value) {
            this.value = [];
        }
        this.$nextTick(() => {
            // Transform the value to an array of tag objects with id and value properties
            const tagValues = JSON.parse(this.value).map(id => ({id: id, value: this.options[id]}));
            this.$refs.tags.value = JSON.stringify(tagValues);

            var tagify = new window.Tagify(this.$refs.tags, {
                whitelist: Object.entries(this.options).map(([id, value]) => ({id: id, value: value})),
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

<input x-ref="tags" name='tags' class='w-full px-3 py-2 bg-white rounded-lg shadow-xs appearance-none border-gray-500/30 tags focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700' placeholder='{{ $taxonomy->pluralName() }}'>

@once
@push('scripts')
{{-- <script src="https://unpkg.com/@yaireo/tagify"></script>
<script src="https://unpkg.com/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
<link href="https://unpkg.com/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" /> --}}
@endpush
@endonce
</div>
</x-aura::fields.wrapper>
