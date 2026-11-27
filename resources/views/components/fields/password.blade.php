@php
    // An empty password field on a saved record keeps the stored password (Password::set skips it).
    // The account password itself says so unless the field names its own placeholder.
    $placeholder = \Aura\Base\Fields\Field::placeholderFor($field);

    if ($placeholder === null && optional($field)['slug'] === 'password'
        && isset($this->model) && $this->model instanceof \Illuminate\Database\Eloquent\Model && $this->model->exists) {
        $placeholder = __('Leave blank to keep current password');
    }
@endphp
<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text wire:model="form.fields.{{ optional($field)['slug'] }}" error="form.fields.{{ optional($field)['slug'] }}" :placeholder="$placeholder" id="resource-field-{{ optional($field)['slug'] }}" type="password" data-lpignore="true" autocomplete="{{ optional($field)['autocomplete'] ?? 'new-password' }}"></x-aura::input.text>
</x-aura::fields.wrapper>
