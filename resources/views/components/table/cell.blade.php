@props(['key' => null])

@if(optional($this->columns)[$key])
    <td {{ $attributes->merge(['class' => 'px-4 py-3 text-sm text-gray-600 dark:text-gray-300']) }}>
        {{ $slot }}
    </td>
@endif
