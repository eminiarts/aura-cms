<x-aura::fields.wrapper :field="$field">
    <textarea wire:model="form.fields.{{ optional($field)['slug'] }}" @error('form.fields.' . optional($field)['slug']) aria-invalid="true" @enderror rows="{{ $field['rows'] ?? '4' }}" name="post_fields_{{ optional($field)['slug'] }}" id="post_fields_{{ optional($field)['slug'] }}" class="aura-field block px-3 py-2 w-full text-sm" autocomplete="{{ optional($field)['autocomplete'] ?? '' }}" @disabled($field['field']->isDisabled($form, $field))></textarea>
</x-aura::fields.wrapper>


{{-- border-gray-500/30 focus:border-primary-300 focus:ring focus:ring-primary-300 focus:ring-opacity-50 dark:focus:ring-primary-500 dark:focus:ring-opacity-50 rounded-md shadow-sm --}}
