<!-- resources/views/livewire/date.blade.php -->
<x-aura::datetime-picker
    :field="$field"
    type="date"
    :format="optional($field)['format'] ?? 'd.m.Y'"
    :displayFormat="optional($field)['display_format'] ?? 'd.m.Y'"
    :maxDate="optional($field)['maxDate']"
    :minDate="optional($field)['minDate']"
    :placeholder="optional($field)['placeholder'] ?? 'Select Date'"
    :weekStartsOn="optional($field)['weekStartsOn'] ?? 1"
    :enableInput="optional($field)['enable_input'] ?? true"
    :native="optional($field)['options']['native'] ?? false"
    :live="optional($field)['live'] ?? false"
/>
