<div>
    @section('title', 'Edit Settings')

    {{-- Page header as on index pages: the trail (a back link on phones), then title, description and actions. --}}
    <x-aura::breadcrumbs>
        <x-aura::breadcrumbs.li :href="route('aura.dashboard')" title="" icon="dashboard" />
        @if($page === 'general')
            <x-aura::breadcrumbs.li :title="__('Settings')" />
        @else
            <x-aura::breadcrumbs.li :href="route('aura.settings')" :title="__('Settings')" />
            <x-aura::breadcrumbs.li :title="__($this->settingsPage()->title)" />
        @endif
    </x-aura::breadcrumbs>

    <div class="flex gap-4 justify-between items-center mt-1 mb-6 sm:mt-6">
        <div class="min-w-0">
            <h1 class="aura-page-title">{{ $page === 'general' ? __('Settings') : __($this->settingsPage()->title) }}</h1>
            @if(filled($this->settingsPage()->description))
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __($this->settingsPage()->description) }}</p>
            @endif
        </div>

        @if($canUpdate)
            <div class="flex gap-2 items-center shrink-0">
                @if($page === 'general' && ($form['fields']['appearance-expert-mode'] ?? false))
                    <x-aura::button.border size="sm" wire:click="restoreExpertDefaults">
                        {{ __('Restore expert defaults') }}
                    </x-aura::button.border>
                @endif
                @if($page === 'general' && $this->hasAppearanceChanges)
                    <span class="text-xs font-medium text-amber-700 dark:text-amber-300">
                        {{ __('Unsaved changes') }}
                    </span>
                    <x-aura::button.border size="sm" wire:click="discardAppearanceChanges">
                        {{ __('Discard changes') }}
                    </x-aura::button.border>
                @endif
                <x-aura::button wire:click="save">
                    <div wire:loading wire:target="save">
                        <x-aura::icon.loading  />
                    </div>
                    {{ __('Save') }}
                </x-aura::button>
            </div>
        @endif
    </div>

    @if($page === 'general')
        @php
            $previewSettings = $form['fields'];
            $appearanceState = [
                'darkmode' => $previewSettings['darkmode-type'] ?? 'auto',
                'sidebarType' => $previewSettings['sidebar-type'] ?? 'dark',
                'sidebarDarkmodeType' => $previewSettings['sidebar-darkmode-type'] ?? 'dark',
                'sidebarSize' => $previewSettings['sidebar-size'] ?? 'standard',
            ];
            $savedAppearanceState = [
                'darkmode' => $savedAppearance['darkmode-type'] ?? 'auto',
                'sidebarType' => $savedAppearance['sidebar-type'] ?? 'dark',
                'sidebarDarkmodeType' => $savedAppearance['sidebar-darkmode-type'] ?? 'dark',
                'sidebarSize' => $savedAppearance['sidebar-size'] ?? 'standard',
            ];
            $appearanceStateKey = sha1(json_encode([$appearanceState, $savedAppearanceState]));
        @endphp

        @include('aura::components.layout.colors', [
            'settings' => $previewSettings,
            'colorScope' => ':root',
            'darkColorScope' => '.dark',
            'includeColorHelpers' => false,
        ])

        <div
            wire:key="appearance-page-preview-{{ $appearanceStateKey }}"
            data-testid="appearance-page-preview"
            data-preview-sidebar-size="{{ $appearanceState['sidebarSize'] }}"
            data-preview-sidebar-darkmode-type="{{ $appearanceState['sidebarDarkmodeType'] }}"
            data-preview-interface-density="{{ $previewSettings['interface-density'] ?? 'comfortable' }}"
            data-preview-corner-radius="{{ $previewSettings['corner-radius'] ?? 'rounded' }}"
            data-preview-expert-mode="{{ ($previewSettings['appearance-expert-mode'] ?? false) ? 'true' : 'false' }}"
            data-preview-panel-radius="{{ $previewSettings['expert-panel-radius'] ?? '' }}"
            class="hidden"
            x-data="{
                preview: @js($appearanceState),
                saved: @js($savedAppearanceState),
                init() {
                    this.apply(this.preview)
                    // Navigation re-renders its root classes from saved settings; keep the preview on top.
                    this.unhook = Livewire.hook('morphed', ({ el }) => {
                        if (el.classList?.contains('aura-sidebar')) this.apply(this.preview)
                    })
                },
                destroy() {
                    this.unhook?.()
                    this.apply(this.saved)
                },
                apply(settings) {
                    const root = document.documentElement
                    const sidebar = document.querySelector('.aura-sidebar')
                    const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false
                    const dark = settings.darkmode === 'dark' || (settings.darkmode === 'auto' && prefersDark)

                    root.classList.toggle('dark', dark)
                    root.classList.toggle('light', !dark)

                    if (! sidebar) return

                    Array.from(sidebar.classList)
                        .filter(name => name.startsWith('aura-sidebar-size-')
                            || name.startsWith('aura-sidebar-type-')
                            || name.startsWith('aura-darkmode-type-')
                            || name.startsWith('aura-sidebar-darkmode-type-'))
                        .forEach(name => sidebar.classList.remove(name))

                    sidebar.classList.remove('light')
                    sidebar.classList.add(`aura-sidebar-size-${settings.sidebarSize}`)
                    sidebar.classList.add(`aura-sidebar-type-${settings.sidebarType}`)
                    sidebar.classList.add(`aura-darkmode-type-${settings.darkmode}`)
                    sidebar.classList.add(`aura-sidebar-darkmode-type-${settings.sidebarDarkmodeType}`)
                    sidebar.classList.toggle('light', settings.sidebarType === 'light')
                }
            }"
        ></div>
    @endif


    {{-- Each field view renders its own #resource-field-…-wrapper with its width; a second wrapper here would halve a 50% panel again.
         Panels bring px-2, so with -mx-2 their cards line up with the title. --}}
    <div class="flex flex-wrap items-start -mx-2">
        @foreach($this->fieldsForView as $key => $field)
            {{-- Flex items do not collapse margins, so the gap replaces the card's own mb-8 instead of adding to it. --}}
            @if(optional($field)['type'] === 'Aura\\Base\\Fields\\Panel')
                <style>
                    #resource-field-{{ optional($field)['slug'] }}-wrapper > .aura-card {
                        margin-bottom: var(--aura-layout-gap, 1rem);
                    }
                </style>
            @elseif(optional($field)['type'] === 'Aura\\Base\\Fields\\Group')
                <style>
                    #resource-field-{{ Str::slug(optional($field)['slug']) }}-wrapper {
                        margin-bottom: var(--aura-section-gap, 1rem);
                    }
                </style>
            @endif

            @checkCondition($model, $field, $form)
                @if(optional($field)['type'] === 'Aura\\Base\\Fields\\View')
                    {{-- A View field (a settings page's own block) renders no wrapper, so it gets a full row and the panels' px-2. --}}
                    <div class="w-full px-2" wire:key="resource-field-{{ $key }}">
                        <x-dynamic-component :component="$field['field']->edit()" mode="edit" :field="$field" :form="$form" />
                    </div>
                @else
                    <x-dynamic-component :component="$field['field']->edit()" mode="edit" :field="$field" :form="$form"
                        wire:key="resource-field-{{ $key }}" />
                @endif
            @endcheckCondition
        @endforeach
    </div>

</div>
