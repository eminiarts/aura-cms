{{--
    Column-type mark for a table heading: a 12px outline glyph in front of the label that says what
    kind of value the column holds (text, email, number, date, status, relation ...), the way a CRM
    header does. Always leads the label, also on right-aligned number columns. Pass the field class
    (`type`); unknown types render nothing.
--}}
@props(['type' => null])

@php
    $path = null;

    if (is_string($type) && class_exists($type)) {
        $path = match (true) {
            is_a($type, \Aura\Base\Fields\Email::class, true) => 'M2.5 4.5h11v7h-11zM2.5 5l5.5 4 5.5-4',
            is_a($type, \Aura\Base\Fields\Phone::class, true) => 'M5.5 2.5h5a1 1 0 0 1 1 1v9a1 1 0 0 1-1 1h-5a1 1 0 0 1-1-1v-9a1 1 0 0 1 1-1zM7 11.5h2',
            is_a($type, \Aura\Base\Fields\Number::class, true), is_a($type, \Aura\Base\Fields\ID::class, true) => 'M6.5 2.5 5 13.5M11 2.5 9.5 13.5M3 6h10.5M2.5 10H13',
            is_a($type, \Aura\Base\Fields\Date::class, true), is_a($type, \Aura\Base\Fields\Datetime::class, true), is_a($type, \Aura\Base\Fields\Time::class, true) => 'M3 4.5h10v9H3zM3 7.5h10M5.5 2.5v3M10.5 2.5v3',
            is_a($type, \Aura\Base\Fields\Status::class, true) => 'M8 2.5a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11zM8 6.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z',
            is_a($type, \Aura\Base\Fields\Boolean::class, true) => 'M8 2.5a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11zM5.5 8l2 2 3-3.5',
            is_a($type, \Aura\Base\Fields\Image::class, true), is_a($type, \Aura\Base\Fields\File::class, true) => 'M2.5 3.5h11v9h-11zM2.5 10.5l3-3 2.5 2.5 2-2 3.5 3.5M10.5 6a.5.5 0 1 1 0 1 .5.5 0 0 1 0-1z',
            is_a($type, \Aura\Base\Fields\Select::class, true), is_a($type, \Aura\Base\Fields\Radio::class, true), is_a($type, \Aura\Base\Fields\Checkbox::class, true), is_a($type, \Aura\Base\Fields\Tags::class, true) => 'M2.5 3.5h11v9h-11zM6 7.5l2 2 2-2',
            // Relation: an arrow out to the related record, not a chain link.
            is_a($type, \Aura\Base\Fields\BelongsTo::class, true), is_a($type, \Aura\Base\Fields\HasMany::class, true), is_a($type, \Aura\Base\Fields\HasOne::class, true), is_a($type, \Aura\Base\Fields\BelongsToMany::class, true), is_a($type, \Aura\Base\Fields\AdvancedSelect::class, true) => 'M4 12 12 4M5.5 4H12v6.5',
            // Text: a T, which reads as "text" at 12px where three lines read as a drag handle.
            is_a($type, \Aura\Base\Fields\Text::class, true), is_a($type, \Aura\Base\Fields\Textarea::class, true), is_a($type, \Aura\Base\Fields\Slug::class, true), is_a($type, \Aura\Base\Fields\Wysiwyg::class, true) => 'M3.5 4h9M8 4v8.5',
            default => null,
        };
    }
@endphp

@if ($path)
    <svg {{ $attributes->merge(['class' => 'w-3 h-3 shrink-0 text-gray-400 dark:text-gray-500']) }} viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-column-icon>
        <path d="{{ $path }}" />
    </svg>
@endif
