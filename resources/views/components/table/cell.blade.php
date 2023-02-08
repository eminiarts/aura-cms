@props(['key' => null])

@if(optional($this->columns)[$key])
    <td {{ $attributes->merge(['class' => 'px-aura::6 py-4 whitespace-no-wrap text-sm leading-5
    text-gray-900']) }}>
        {{ $slot }}
    </td>
@endif
