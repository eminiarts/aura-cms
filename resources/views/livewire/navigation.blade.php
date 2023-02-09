<div>
    @php
        use App\Aura\Resources\Team;
        $settings = Eminiarts\Aura\Aura::getOption('team-settings');
        if ($settings) {
            $sidebarType = $settings['sidebar-type'] ?? 'primary';
        } else {
            $sidebarType = 'primary';
        }

        if ($sidebarType == 'primary') {
            $iconClass = 'group-[.is-active]:text-white text-primary-300 dark:text-primary-500 group-hover:text-primary-200 dark:group-hover:text-primary-500';
        } else if ($sidebarType == 'light') {
            $iconClass = 'group-[.is-active]:text-primary-500 text-primary-500 dark:text-primary-500 group-hover:text-primary-500';
        } else if ($sidebarType == 'dark') {
            $iconClass = 'group-[.is-active]:text-primary-500 text-primary-500';
        }

        
    @endphp

    @foreach(\Eminiarts\Aura\Aura::navigation() as $group => $resources)

        @if ($group !== '')
            <div wire:key="toggle-{{$group}}" wire:click="toggleGroup('{{$group}}')" class="cursor-pointer">
                <x-navigation.heading>
                    {{ $group }}
                </x-navigation.heading>
            </div>
        @endif

        @if ($this->isToggled($group))
            <div class="flex flex-col space-y-1">
                @foreach($resources as $resource)

                    @if (isset($resource['dropdown']) && $resource['dropdown'] !== false)
                        <x-navigation.dropdown route="table">
                            <x-slot:title>
                                <div class="{{ $iconClass }}">

                                    @php
                                        $iconView = 'aura.navigation.icons.' . Str::slug($resource['dropdown'] );
                                    @endphp
                                    
                                    @includeIf($iconView, ['class' => 'w-6 h-6'])

                                    @if(! View::exists($iconView))
                                        {!! $resource['items'][0]['icon'] !!}
                                    @endif
                                </div>
                                <div>{{ $resource['dropdown'] }}</div>
                            </x-slot:title>

                            @foreach($resource['items'] as $resource)
                                <x-navigation.item route="post.index" :id="$resource['type']" :strict="false">
                                    <div class="{{ $iconClass }}">
                                        {!! $resource['icon'] !!}
                                    </div>
                                    <div>{{ $resource['name'] }}</div>
                                </x-navigation.item>
                            @endforeach
                        </x-navigation.dropdown>

                    @else

                        <x-navigation.item route="post.index" :id="$resource['type']" :strict="false">
                            <div class="{{ $iconClass }}">
                                {!! $resource['icon'] !!}
                            </div>
                            <div>{{ $resource['name'] }}</div>
                        </x-navigation.item>

                    @endif
                @endforeach
            </div>
        @endif
    @endforeach

    @php
        $group = 'Taxonomies';
    @endphp

    <div wire:key="toggle-{{$group}}" wire:click="toggleGroup('{{$group}}')" class="cursor-pointer">
        <x-navigation.heading>
            Taxonomies
        </x-navigation.heading>
    </div>

    @if ($this->isToggled($group))
        @foreach(\Eminiarts\Aura\Aura::taxonomies() as $taxonomy)
        <x-navigation.item route="taxonomy.index" :id="$taxonomy" :strict="false">
            <div class="{{ $iconClass }}">
                <x-icon icon="circle" />
            </div>
            <div>{{ $taxonomy }}</div>
        </x-navigation.item>
        @endforeach
    @endif


    @php
        $group = 'Development';
    @endphp

    <div wire:key="toggle-{{$group}}" wire:click="toggleGroup('{{$group}}')" class="cursor-pointer">
        <x-navigation.heading>
            Development
        </x-navigation.heading>
    </div>

    @if ($this->isToggled($group))
        <x-navigation.item route="team.settings">
            <div class="{{ $iconClass }}">
                <x-icon icon="adjustments" />
            </div>
            <div>Settings</div>
        </x-navigation.item>

        <x-navigation.item route="posttypes">
            <div class="{{ $iconClass }}">
                <x-icon icon="collection" />
            </div>
            <span>Posttypes</span>
        </x-navigation.item>

        <x-navigation.item class="cursor-pointer" onclick="Livewire.emit('openModal', 'create-posttype')">
            <div class="{{ $iconClass }}">
                <x-icon icon="collection" />
            </div>
            <div>Create Posttype</div>
        </x-navigation.item>
    @endif

    @php
        $group = 'Aura';
    @endphp

    <div wire:key="toggle-{{$group}}" wire:click="toggleGroup('{{$group}}')" class="cursor-pointer">
        <x-navigation.heading>
            Aura
        </x-navigation.heading>
    </div>

    @if ($this->isToggled($group))
        <x-navigation.item route="aura.config">
            <div class="{{ $iconClass }}">
                <x-icon icon="adjustments" />
            </div>
            <div>Configuration</div>
        </x-navigation.item>
    @endif

    <div class="mt-6">
        <x-navigation.item route="logout">
            <div>Logout</div>
        </x-navigation.item>
    </div>

</div>
