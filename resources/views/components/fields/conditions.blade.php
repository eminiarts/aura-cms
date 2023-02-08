@props([
'field',
'model'
])

  @php
  $show = \App\Aura::checkCondition($model, $field);
  @endphp

  @if ($show)
  {{ $slot }}
  @endif
