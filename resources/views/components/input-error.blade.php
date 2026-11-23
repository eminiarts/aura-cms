@props(['for'])

@if(isset($for) && is_string($for))
    @error($for)
        <p {{ $attributes->merge(['class' => 'aura-field-error text-sm']) }}>{{ $message }}</p>
    @enderror
@elseif(isset($for) && is_array($for))
    @foreach($for as $error)
        <p {{ $attributes->merge(['class' => 'aura-field-error text-sm']) }}>{{ $error }}</p>
    @endforeach
@endif
