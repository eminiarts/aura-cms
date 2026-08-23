@props([
  'id' => '',
  'size' => 'thumbnail'
])

@php
    $url = null;

    try {
        // Resolve WITH global scopes (TeamScope) and check the view policy:
        // this component is embedded in relation pickers and the sidebar logo,
        // so it must never surface an attachment the viewer may not see.
        $attachment = $id
            ? app(config('aura.resources.attachment'))->newQuery()->find($id)
            : null;

        if ($attachment && \Illuminate\Support\Facades\Gate::allows('view', $attachment)) {
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
