{{--
    A list page's primary create button. It keeps its object ("New company") while the label fits beside
    the page title and falls back to "New" only when the full label would push the title onto a second line
    (phones, long titles). Pure CSS (.aura-create-fit in app.css): the full button sits on a one-line
    wrapping row and wraps out of sight when it does not fit; the short copy under it then shows through.
    The short copy is hidden from assistive tech and the tab order, so the full label stays the button's
    name. Put it last in a flex row after the title; it takes the remaining width.
    Pass `href` for a link, or a wire:click (etc.) for a button.
--}}
@props(['label', 'href' => null])

<div class="aura-create-fit" data-create-fit>
    <x-aura::button :href="$href" {{ $attributes }} aria-hidden="true" tabindex="-1" data-create-fit-short>
        <x-slot:icon>
            <x-aura::icon icon="plus" />
        </x-slot>
        <span>{{ __('New') }}</span>
    </x-aura::button>
    <div class="aura-create-fit-full">
        <x-aura::button :href="$href" {{ $attributes }}>
            <x-slot:icon>
                <x-aura::icon icon="plus" />
            </x-slot>
            <span>{{ $label }}</span>
        </x-aura::button>
    </div>
</div>
