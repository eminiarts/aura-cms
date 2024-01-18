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
                <x-aura::navigation.item class="cursor-pointer" onclick="Livewire.emit('openModal', 'aura::create-posttype')">
                    <div class="{{ $iconClass }}">
                        <x-aura::icon icon="collection" />
                    </div>
                    <div>{{ __('Create Resource') }}</div>
                </x-aura::navigation.item>
            @endlocal

            <x-aura::navigation.item route="{{ route('aura.team.settings') }}">
                <div class="{{ $iconClass }}">
                    <x-aura::icon icon="brush" />
                </div>
                <div>{{ __('Theme Options') }}</div>
            </x-aura::navigation.item>


            <x-aura::navigation.item route="{{ route('aura.config') }}">
                <div class="{{ $iconClass }}">
                    <x-aura::icon icon="adjustments" />
                </div>
                <div>{{ __('Global Config') }}</div>
            </x-aura::navigation.item>
        @endif
    @endsuperadmin