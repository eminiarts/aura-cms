
<div>
    @foreach(app('aura')::navigation() as $group => $resources)

        @if ($group !== '')
            <div wire:key="toggle-{{$group}}" wire:click="toggleGroup('{{$group}}')" class="cursor-pointer">
                <x-aura::navigation.heading>
                    {{ $group }}
                </x-aura::navigation.heading>
            </div>
        @endif

        @if ($this->isToggled($group))
            <div class="flex flex-col space-y-1">
                @foreach($resources as $resource)

                    @if (isset($resource['dropdown']) && $resource['dropdown'] !== false)
                        <x-aura::navigation.dropdown>
                            <x-slot:title>
                                <div class="{{ $iconClass }}">

                                    @php
                                        $iconView = 'aura::aura.navigation.icons.' . Str::slug($resource['dropdown'] );
                                    @endphp

                                    @includeIf($iconView, ['class' => 'w-6 h-6'])

                                    @if(! View::exists($iconView))
                                        {!! $resource['items'][0]['icon'] !!}
                                    @endif
                                </div>
                                <div>{{ $resource['dropdown'] }}</div>
                            </x-slot:title>

                            @foreach($resource['items'] as $resource)
                                <x-aura::navigation.item route="aura.post.index" :id="$resource['type']" :strict="false">
                                    <div class="{{ $iconClass }}">
                                        {!! $resource['icon'] !!}
                                    </div>
                                    <div>{{ $resource['name'] }}</div>
                                </x-aura::navigation.item>
                            @endforeach
                        </x-aura::navigation.dropdown>

                    @else

                        <x-aura::navigation.item route="aura.post.index" :id="$resource['type']" :strict="false">
                            <div class="{{ $iconClass }}">
                                {!! $resource['icon'] !!}
                            </div>
                            <div>{{ $resource['name'] }}</div>
                        </x-aura::navigation.item>

                    @endif
                @endforeach
            </div>
        @endif
    @endforeach

    @php
        $group = 'Taxonomies';
    @endphp

    <div wire:key="toggle-{{$group}}" wire:click="toggleGroup('{{$group}}')" class="cursor-pointer">
        <x-aura::navigation.heading>
            Taxonomies
        </x-aura::navigation.heading>
    </div>

    @if ($this->isToggled($group))
        @foreach(\Aura::taxonomies() as $taxonomy)
        <x-aura::navigation.item route="aura.taxonomy.index" :id="app($taxonomy)->title" :strict="false">
            <div class="{{ $iconClass }}">
                <x-aura::icon icon="circle" />
            </div>
            <div>{{ app($taxonomy)->title }}</div>
        </x-aura::navigation.item>
        @endforeach
    @endif


    @php
        $group = 'Development';
    @endphp

    <div wire:key="toggle-{{$group}}" wire:click="toggleGroup('{{$group}}')" class="cursor-pointer">
        <x-aura::navigation.heading>
            Development
        </x-aura::navigation.heading>
    </div>

    @if ($this->isToggled($group))
        <x-aura::navigation.item route="aura.team.settings">
            <div class="{{ $iconClass }}">
                <x-aura::icon icon="adjustments" />
            </div>
            <div>Settings</div>
        </x-aura::navigation.item>

        <x-aura::navigation.item route="aura.posttypes">
            <div class="{{ $iconClass }}">
                <x-aura::icon icon="collection" />
            </div>
            <span>Posttypes</span>
        </x-aura::navigation.item>

        <x-aura::navigation.item class="cursor-pointer" onclick="Livewire.emit('openModal', 'aura::create-posttype')">
            <div class="{{ $iconClass }}">
                <x-aura::icon icon="collection" />
            </div>
            <div>Create Posttype</div>
        </x-aura::navigation.item>
    @endif

    @php
        $group = 'Aura';
    @endphp

    <div wire:key="toggle-{{$group}}" wire:click="toggleGroup('{{$group}}')" class="cursor-pointer">
        <x-aura::navigation.heading>
            Aura
        </x-aura::navigation.heading>
    </div>

    @if ($this->isToggled($group))
        <x-aura::navigation.item route="aura.config">
            <div class="{{ $iconClass }}">
                <x-aura::icon icon="adjustments" />
            </div>
            <div>Configuration</div>
        </x-aura::navigation.item>
    @endif

    <div class="mt-6">
        <x-aura::navigation.item route="aura.logout">
            <div>Logout</div>
        </x-aura::navigation.item>
    </div>

</div>
