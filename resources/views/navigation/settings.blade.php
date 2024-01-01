 @superadmin

        @php
            $group = 'Settings';
        @endphp

        <div wire:key="toggle-{{ $group }}" wire:click="toggleGroup('{{ $group }}')" class="cursor-pointer">
            <x-aura::navigation.heading>
                <div class="flex justify-between items-center">
                    <span>{{ __($group) }}</span>

                    @if ($this->isToggled($group))
                    @else
                        <span>+</span>
                    @endif
                </div>
            </x-aura::navigation.heading>
        </div>

        @if ($this->isToggled($group))
            @local
                <x-aura::navigation.item class="cursor-pointer" onclick="Livewire.emit('openModal', 'aura::create-posttype')" :tooltip="__('Create Resource')">
                    <div class="{{ $iconClass }}">
                        <x-aura::icon icon="collection" />
                    </div>
                    <div class="hide-collapsed">{{ __('Create Resource') }}</div>
                </x-aura::navigation.item>
                <x-aura::navigation.item class="cursor-pointer" onclick="Livewire.emit('openModal', 'aura::create-taxonomy')" :tooltip="__('Create Taxonomy')">
                    <div class="{{ $iconClass }}">
                        <x-aura::icon icon="collection" />
                    </div>
                    <div class="hide-collapsed">{{ __('Create Taxonomy') }}</div>
                </x-aura::navigation.item>
            @endlocal

            <x-aura::navigation.item route="{{ route('aura.team.settings') }}" :tooltip="__('Theme Options')">
                <div class="{{ $iconClass }}">
                    <x-aura::icon icon="brush" />
                </div>
                <div class="hide-collapsed">{{ __('Theme Options') }}</div>
            </x-aura::navigation.item>


            <x-aura::navigation.item route="{{ route('aura.config') }}" :tooltip="__('Global Config')">
                <div class="{{ $iconClass }}">
                    <x-aura::icon icon="adjustments" />
                </div>
                <div class="hide-collapsed">{{ __('Global Config') }}</div>
            </x-aura::navigation.item>
        @endif
    @endsuperadmin
