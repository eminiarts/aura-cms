{{-- "Edit resource" on a resource index page: an icon button the size of the table controls, the name in the
     tooltip and for screen readers. Desktop only: the resource editor is a builder, not a phone tool. --}}
@if (($this->settings['resource_editor'] ?? false) && config('aura.features.resource_editor') && $model->isAppResource())
    <div class="hidden sm:block shrink-0" data-resource-editor-button>
        <x-aura::tippy :text="__('Edit resource')" position="bottom">
            <x-aura::button.border :href="route('aura.resource.editor', $model::getSlug())" class="w-9 !px-0 [&>span:first-child]:ml-0" aria-label="{{ __('Edit resource') }}">
                <x-slot:icon>
                    <x-aura::icon icon="cog" />
                </x-slot>
            </x-aura::button.border>
        </x-aura::tippy>
    </div>
@endif
