@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'aura-field aura-control-base text-sm appearance-none px-3 w-full z-[1]']) !!}>
