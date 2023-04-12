@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-gray-500/30 focus:border-primary-500 focus:ring-primary-500 rounded-md shadow-sm']) !!}>
