@props(['open' => false])

<div
    x-data="{ dialogOpen: @js($open) }"
    x-modelable="dialogOpen"
    {{ $attributes }}
    tabindex="-1"
>
    {{ $slot }}
</div>
