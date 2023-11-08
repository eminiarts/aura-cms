@php
  $label = is_array($label) ? 'Array' : $label;
  $label = $label ?? 'Label';
@endphp
<label class="inline-block mt-3 mb-2 text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $label }}</label>
