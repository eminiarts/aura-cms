@props(['open' => false])

<div
    x-data="{ dialogOpen: @js($open) }"
    x-modelable="dialogOpen"
    x-init="$watch('dialogOpen', value => {
        if (value) {
            document.body.classList.add('overflow-hidden')
        } else {
            document.body.classList.remove('overflow-hidden')
        }
    })"
    {{ $attributes }}
    tabindex="-1"
>
    {{ $slot }}
</div>
