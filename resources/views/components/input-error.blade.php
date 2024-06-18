@props(['for'])

@isset($for)
    @error($for)
        <p {{ $attributes->merge(['class' => 'text-sm text-red-600']) }}>{{ $for }}</p>
    @enderror
@endisset
