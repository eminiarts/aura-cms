@props([
  'id' => '',
  'size' => 'thumbnail'
])

@php

$attachment = Eminiarts\Aura\Resources\Attachment::find($id);
$url = null;

if ($attachment) {
    $url = $attachment->path($size);
}

@endphp

@if ($url)
  <img src="{{ $url }}" alt="" {{ $attributes->merge(['class' => '']) }}>
@endif
