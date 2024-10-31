@php
$values = [];
$taxonomy = null;

if (key_exists('resource', $field) ) {
    $values = app($field['resource'])->get()->map(function ($tag) {
        return ['id' => $tag->id, 'value' => $tag->title()];
    })->toArray();
}
@endphp

<script>
    // Dynamically load Tagify if it hasn't been loaded yet
    console.log('tagify', window.tagifyLoaded);
    if (!window.tagifyLoaded) {
        window.tagifyLoaded = true;
        const script = document.createElement('script');
        script.src = '{{ Vite::asset('resources/js/tagify.js', 'vendor/aura/libs') }}';
        document.head.appendChild(script);
    }
</script>

<x-aura::fields.wrapper :field="$field">
<div
wire:ignore
x-data="{
    multiple: true,
    value: @entangle('form.fields.' . $field['slug']),
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
    class='flex items-center min-h-[42px] w-full bg-white rounded-lg appearance-none shadow-xs border-gray-500/30 tags focus:border-primary-300 focus:outline-none ring-gray-900/10 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 dark:bg-gray-900 dark:border-gray-700 disabled:bg-gray-100 disabled:text-gray-500 disabled:cursor-not-allowed'
    placeholder='{{ $field['name'] }}'
    :disabled="disabled"
>

</div>
</x-aura::fields.wrapper>
