@props([
  'class' => ''
])

<x-input
  {{$attributes->merge([
    'class' => $class . ' ',
  ])}}
  type="text"
/>