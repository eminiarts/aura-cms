@props(['for'])

@if(isset($for) && is_string($for))
    @error($for)
        <p {{ $attributes->merge(['class' => 'text-sm text-red-600']) }}>{{ $message }}</p>
    @enderror
@elseif(isset($for) && is_array($for))
    @foreach($for as $error)
        <p {{ $attributes->merge(['class' => 'text-sm text-red-600']) }}>{{ $error }}</p>
    @endforeach
@endif
