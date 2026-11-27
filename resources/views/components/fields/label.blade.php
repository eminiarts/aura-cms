@props([
  'label' => 'Label',
  'for' => '',
  'required' => false,
])
@php
  $label = is_array($label) ? 'Array' : $label;
  $label = $label ?? 'Label';
@endphp
<label {{ $attributes->merge(['class' => 'inline-block mt-3 mb-1.5 text-sm font-medium text-gray-700 dark:text-gray-200']) }} @if($for) for="{{ $for }}" @endif>{{ $label }}@if($required)<span class="ml-0.5 text-gray-400 dark:text-gray-500" aria-hidden="true">*</span>@endif</label>
