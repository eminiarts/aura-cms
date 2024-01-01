<div>

    @foreach (app('aura')::navigation() as $group => $resources)
        @if ($group !== '')
            <div wire:key="toggle-{{ $group }}" wire:click="toggleGroup('{{ $group }}')"
                class="cursor-pointer">
                <x-aura::navigation.heading :toggled="$this->isToggled($group)">
                    {{ __($group) }}
                </x-aura::navigation.heading>
            </div>
        @endif

        @if ($this->isToggled($group))
            <div class="flex flex-col space-y-1">
                @foreach ($resources as $resource)
                    @if (isset($resource['dropdown']) && $resource['dropdown'] !== false)
                        <x-aura::navigation.dropdown>
                            <x-slot:title>
                                <div class="{{ $iconClass }}">

                                    @php
                                        $iconView = 'aura::aura.navigation.icons.' . Str::slug($resource['dropdown']);
                                    @endphp

                                    @includeIf($iconView, ['class' => 'w-6 h-6'])

                                    @if (!View::exists($iconView))
                                        {!! $resource['items'][0]['icon'] !!}
                                    @endif
                                </div>
                                <div class="hide-collapsed">{{ __($resource['dropdown']) }}</div>
                            </x-slot:title>

                            @foreach ($resource['items'] as $resource)
                                <x-aura::navigation.item :route="$resource['route']" :strict="false">
                                    <div class="{{ $iconClass }}">
                                        {!! $resource['icon'] !!}
                                    </div>
                                    <div class="hide-collapsed">{{ __($resource['name']) }}</div>
                                </x-aura::navigation.item>
                            @endforeach
                        </x-aura::navigation.dropdown>
                    @else
                        {{-- @dd($resource) --}}
                        @can('viewAny', app($resource['resource']))
                            <x-aura::navigation.item :route="$resource['route']" :strict="false" :tooltip="__($resource['name'])">
                                <div class="{{ $iconClass }}">
                                    {!! $resource['icon'] !!}
                                </div>
                                <div class="hide-collapsed">{{ __($resource['name']) }}</div>
                            </x-aura::navigation.item>
                        @endcan
                    @endif
                @endforeach
            </div>
        @endif
    @endforeach

   @include('aura::navigation.settings')

</div>
