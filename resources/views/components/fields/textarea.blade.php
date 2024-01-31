<x-aura::fields.wrapper :field="$field">
    <textarea wire:model="post.fields.{{ optional($field)['slug'] }}" error="post.fields.{{ optional($field)['slug'] }}" rows="{{ $field['rows'] ?? '4' }}" name="post_fields_{{ optional($field)['slug'] }}" id="post_fields_{{ optional($field)['slug'] }}" class="block w-full rounded-md shadow-sm border-gray-500/30 focus:border-primary-300 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 sm:text-sm dark:border-gray-700 dark:bg-gray-900 p-3" autocomplete="{{ optional($field)['autocomplete'] ?? '' }}"></textarea>
</x-aura::fields.wrapper>


{{-- border-gray-500/30 focus:border-primary-300 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 rounded-md shadow-sm --}}
