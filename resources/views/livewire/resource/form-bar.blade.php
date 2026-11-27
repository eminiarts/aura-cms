{{-- Phones only: the create and edit forms keep Cancel and Save in a bar on the bottom edge, so the
     header has no action row there. Expects $cancelUrl (nullable); Save calls the component's save(). --}}
<div class="aura-form-bar-spacer" aria-hidden="true"></div>
<div class="aura-form-bar" data-form-bar>
    @if ($cancelUrl)
        <x-aura::button.transparent size="lg" :href="$cancelUrl">
            {{ __('Cancel') }}
        </x-aura::button.transparent>
    @endif
    <div class="flex-1 min-w-0">
        <x-aura::button size="lg" block wire:click="save" wire:loading.attr="disabled">
            <div wire:loading.delay wire:target="save">
                <x-aura::icon.loading />
            </div>
            {{ __('Save') }}
        </x-aura::button>
    </div>
</div>
