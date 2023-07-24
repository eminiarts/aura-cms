@props(['options' => []])

@php
$options = array_merge([
    'dateFormat' => 'Y-m-d',
    'enableTime' => false,
    'altFormat' =>  'd.m.Y',
    'altInput' => true,

    ], $options);
@endphp

<div wire:ignore>
    <input
        x-data="{value: @entangle($attributes->wire('model')).live, instance: undefined}"
        x-init="() => {
                $watch('value', value => instance.setDate(value, true));
                instance = flatpickr($refs.input, {{ json_encode((object)$options) }});
            }"
        x-ref="input"
        x-bind:value="value"
        type="text"
        {{ $attributes->merge(['class' => 'w-full border-gray-500/30 focus:border-cyan-600 focus:ring focus:ring-cyan-600 focus:ring-opacity-50 rounded-md shadow-sm']) }}
    />
</div>
