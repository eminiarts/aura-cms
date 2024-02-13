@props([
  'id' => '',
  'size' => 'thumbnail'
])

@php
    $url = null;

    try {
        // Temporarily disable permission scope
        Aura\Base\Resources\Attachment::withoutGlobalScopes()->find($id);

        $attachment = Aura\Base\Resources\Attachment::withoutGlobalScopes()->find($id);
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
