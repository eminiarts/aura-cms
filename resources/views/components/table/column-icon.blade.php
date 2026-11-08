{{--
    Column-type mark for a table heading: a 14px outline icon that says what kind of value the
    column holds (text, email, number, date, status, relation ...), the way a CRM header does.
    Pass the field class (`type`); unknown types render nothing.
--}}
@props(['type' => null])

@php
    $path = null;

    if (is_string($type) && class_exists($type)) {
        $path = match (true) {
            is_a($type, \Aura\Base\Fields\Email::class, true) => 'M2.5 4.5h11v7h-11zM2.5 5l5.5 4 5.5-4',
            is_a($type, \Aura\Base\Fields\Phone::class, true) => 'M4 2.5h2.2l1.1 2.9-1.5 1.1a8.5 8.5 0 0 0 3.7 3.7l1.1-1.5 2.9 1.1V12a1.5 1.5 0 0 1-1.5 1.5A10 10 0 0 1 2.5 4 1.5 1.5 0 0 1 4 2.5z',
            is_a($type, \Aura\Base\Fields\Number::class, true), is_a($type, \Aura\Base\Fields\ID::class, true) => 'M6.5 2.5 5 13.5M11 2.5 9.5 13.5M3 6h10.5M2.5 10H13',
            is_a($type, \Aura\Base\Fields\Date::class, true), is_a($type, \Aura\Base\Fields\Datetime::class, true), is_a($type, \Aura\Base\Fields\Time::class, true) => 'M3 4.5h10v9H3zM3 7.5h10M5.5 2.5v3M10.5 2.5v3',
            is_a($type, \Aura\Base\Fields\Status::class, true) => 'M8 2.5a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11zM8 6.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z',
            is_a($type, \Aura\Base\Fields\Boolean::class, true) => 'M8 2.5a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11zM5.5 8l2 2 3-3.5',
            is_a($type, \Aura\Base\Fields\Image::class, true), is_a($type, \Aura\Base\Fields\File::class, true) => 'M2.5 3.5h11v9h-11zM2.5 10.5l3-3 2.5 2.5 2-2 3.5 3.5M10.5 6a.5.5 0 1 1 0 1 .5.5 0 0 1 0-1z',
            is_a($type, \Aura\Base\Fields\Select::class, true), is_a($type, \Aura\Base\Fields\Radio::class, true), is_a($type, \Aura\Base\Fields\Checkbox::class, true), is_a($type, \Aura\Base\Fields\Tags::class, true) => 'M2.5 3.5h11v9h-11zM6 7.5l2 2 2-2',
            is_a($type, \Aura\Base\Fields\BelongsTo::class, true), is_a($type, \Aura\Base\Fields\HasMany::class, true), is_a($type, \Aura\Base\Fields\HasOne::class, true), is_a($type, \Aura\Base\Fields\BelongsToMany::class, true), is_a($type, \Aura\Base\Fields\AdvancedSelect::class, true) => 'M6.5 9.5l3-3M4.75 7.25l-.5.5a2.3 2.3 0 0 0 3.25 3.25l.5-.5M11.25 8.75l.5-.5A2.3 2.3 0 0 0 8.5 5l-.5.5',
            is_a($type, \Aura\Base\Fields\Text::class, true), is_a($type, \Aura\Base\Fields\Textarea::class, true), is_a($type, \Aura\Base\Fields\Slug::class, true), is_a($type, \Aura\Base\Fields\Wysiwyg::class, true) => 'M3 4.5h10M3 8h10M3 11.5h6',
            default => null,
        };
    }
@endphp

@if ($path)
    <svg {{ $attributes->merge(['class' => 'w-3.5 h-3.5 shrink-0 text-gray-400 dark:text-gray-500']) }} viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" data-column-icon>
        <path d="{{ $path }}" />
    </svg>
@endif
