@props([
  'class' => ''
])

<x-aura::input
  {{$attributes->merge([
    'class' => $class . ' ',
  ])}}
  type="number"
/>