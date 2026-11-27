{{-- "Edit resource" on a resource index page: a "more" button the size of the table controls opens a small menu
     with the labelled link, so a developer tool does not compete with the list controls. Desktop only: the
     resource editor is a builder, not a phone tool. --}}
@if (($this->settings['resource_editor'] ?? false) && config('aura.features.resource_editor') && $model->isAppResource())
    <div class="hidden sm:block shrink-0" data-resource-editor-button>
        <x-aura::dropdown align="right" width="w-44">
            <x-slot name="trigger">
                <x-aura::button.border type="button" class="w-9 !px-0 [&>span:first-child]:ml-0" title="{{ __('More actions') }}" aria-label="{{ __('More actions') }}" aria-haspopup="menu">
                    <x-slot:icon>
                        <x-aura::icon.dots />
                    </x-slot>
                </x-aura::button.border>
            </x-slot>

            <x-slot name="content">
                <x-aura::dropdown-link :href="route('aura.resource.editor', $model::getSlug())" class="flex gap-2 items-center" role="menuitem">
                    <x-aura::icon icon="cog" size="xs" class="text-gray-400 dark:text-gray-500" />
                    {{ __('Edit resource') }}
                </x-aura::dropdown-link>
            </x-slot>
        </x-aura::dropdown>
    </div>
@endif
