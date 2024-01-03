 @superadmin

        @php
            $group = 'Settings';
        @endphp

        <div wire:key="toggle-{{ $group }}" wire:click="toggleGroup('{{ $group }}')" class="cursor-pointer">
            <x-aura::navigation.heading :compact="$compact">
                {{ __($group) }}
            </x-aura::navigation.heading>
        </div>

        @if ($this->isToggled($group))
            @local
                <x-aura::navigation.item class="cursor-pointer" onclick="Livewire.emit('openModal', 'aura::create-posttype')" :tooltip="__('Create Resource')" :compact="$compact">
                    <div class="{{ $iconClass }}">
                        <x-aura::icon icon="collection" />
                    </div>
                    <div class="hide-collapsed">{{ __('Create Resource') }}</div>
                </x-aura::navigation.item>
                <x-aura::navigation.item class="cursor-pointer" onclick="Livewire.emit('openModal', 'aura::create-taxonomy')" :tooltip="__('Create Taxonomy')" :compact="$compact">
                    <div class="{{ $iconClass }}">
                        <x-aura::icon icon="collection" />
                    </div>
                    <div class="hide-collapsed">{{ __('Create Taxonomy') }}</div>
                </x-aura::navigation.item>
            @endlocal

            <x-aura::navigation.item route="{{ route('aura.team.settings') }}" :tooltip="__('Theme Options')" :compact="$compact">
                <div class="{{ $iconClass }}">
                    <x-aura::icon icon="brush" />
                </div>
                <div class="hide-collapsed">{{ __('Theme Options') }}</div>
            </x-aura::navigation.item>


            <x-aura::navigation.item route="{{ route('aura.config') }}" :tooltip="__('Global Config')" :compact="$compact">
                <div class="{{ $iconClass }}">
                    <x-aura::icon icon="adjustments" />
                </div>
                <div class="truncate hide-collapsed">{{ __('Global Config') }} Test Long Label</div>
            </x-aura::navigation.item>
        @endif
    @endsuperadmin
