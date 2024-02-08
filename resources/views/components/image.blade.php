@props([
  'id' => '',
  'size' => 'thumbnail'
])

@php
    $url = null;

    try {
        $attachment = Aura\Base\Resources\Attachment::find($id);

        if ($attachment) {
            $url = $attachment->path($size);
        }
    } catch (\Exception $e) {
        // Handle the exception or log error
        // error_log($e->getMessage());
    }
@endphp

@if ($url)
    <img src="{{ $url }}" alt="" {{ $attributes->merge(['class' => '']) }}>
@endif
