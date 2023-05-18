@props([
  'value' => 'Label',
  'class' => 'hidden mr-2 sm:block font-semibold text-gray-700 dark:text-gray-200 text-sm'
])


<div class="{{ $class }}">
    <span>{{ $value }}</span>
</div>
