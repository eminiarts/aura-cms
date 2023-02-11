
@php
$values = app($field['model'])->pluck('name')->toArray();

$taxonomy = app($field['model']);

// dd($taxonomy::$slug, $values);
@endphp

{{-- @dump($this->post['terms']) --}}
{{-- @dump($taxonomy) --}}

<x-aura::fields.wrapper :field="$field">
<div
wire:ignore
x-data="{
    multiple: true,
    value: @entangle('post.terms.' . $taxonomy::$slug).defer,
    options: {{ Js::from($values) }},
    init() {
        if (!this.value) {
            this.value = [];
        }
        this.$nextTick(() => {

            this.$refs.tags.value = this.value;

            var tagify = new Tagify(this.$refs.tags, {
                whitelist: this.options,
                maxTags: 10,
                value: this.value,
                originalInputValueFormat: valuesArr => valuesArr.map(item => item.value),
                dropdown: {
                    maxItems: 20,
                    classname: 'tags-look',
                    enabled: 0,
                    closeOnSelect: false
                }
            })

            let refreshOptions = () => {
                let selection = this.multiple ? this.value : [this.value]
            }

            refreshOptions()

            this.$refs.tags.addEventListener('change', (e) => {
                this.value = tagify.value.map(item => item.value);
            })
        })
    }
}"
class=""
>

<input x-ref="tags" name='tags' class='w-full px-3 py-2 bg-white border-gray-500/30 rounded-lg shadow-xs appearance-none tags focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700' placeholder='{{ $taxonomy->pluralName() }}'>

@once
@push('scripts')
{{-- <script src="https://unpkg.com/@yaireo/tagify"></script>
<script src="https://unpkg.com/@yaireo/tagify/dist/tagify.polyfills.min.js"></script>
<link href="https://unpkg.com/@yaireo/tagify/dist/tagify.css" rel="stylesheet" type="text/css" /> --}}
@endpush
@endonce
</div>
</x-aura::fields.wrapper>
