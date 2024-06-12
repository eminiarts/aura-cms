<!-- resources/views/livewire/time.blade.php -->
<x-aura::datetime-picker
    :field="$field"
    type="time"
    :enableTime="true"
    :noCalendar="true"
    :format="optional($field)['format'] ?? 'H:i'"
    :displayFormat="optional($field)['display_format'] ?? 'H:i'"
    :minTime="optional($field)['minTime']"
    :maxTime="optional($field)['maxTime']"
    :weekStartsOn="optional($field)['weekStartsOn'] ?? 1"
    :enableInput="optional($field)['enable_input'] ?? true"
    :native="optional($field)['options']['native'] ?? false"
    :live="optional($field)['live'] ?? false"
/>
