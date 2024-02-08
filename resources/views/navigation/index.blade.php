<div>
    @foreach (app('aura')::navigation() as $group => $resources)
        @if ($group !== '')
            <div wire:key="toggle-{{ $group }}" wire:click="toggleGroup('{{ $group }}')"
                class="cursor-pointer">
                <x-aura::navigation.heading :toggled="$this->isToggled($group)" :compact="$compact">
                    {{ __($group) }}
                </x-aura::navigation.heading>
            </div>
        @endif

        @if ($this->isToggled($group))
            <div class="flex flex-col {{ $compact ? 'space-y-0.5' : 'space-y-1' }}">
                @foreach ($resources as $resource)
                    @if (isset($resource['dropdown']) && $resource['dropdown'] !== false)
                        <x-aura::navigation.dropdown :compact="$compact">
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

                            <x-slot:mobile>

                                @foreach($resource['items'] as $r)
                                    <x-aura::navigation.item-dropdown route="aura.resource.index" :id="$r['type']" :strict="false" :compact="$compact">
                                        <div class="{{ $iconClass }}">
                                            {!! $r['icon'] !!}
                                        </div>
                                        <div>{{ __($r['name']) }}</div>
                                    </x-aura::navigation.item-dropdown>
                                @endforeach

                            </x-slot:mobile>

                            @foreach ($resource['items'] as $r)
                                <x-aura::navigation.item :route="$r['route']" :strict="false" :compact="$compact">
                                    <div class="{{ $iconClass }}">
                                        {!! $r['icon'] !!}
                                    </div>
                                    <div class="hide-collapsed">{{ __($r['name']) }}</div>
                                </x-aura::navigation.item>
                            @endforeach
                        </x-aura::navigation.dropdown>
                    @else
                        {{-- @dd($resource) --}}
                        @if(isset($resource['resource']))
                            @can('viewAny', app($resource['resource']))
                                <x-aura::navigation.item :route="$resource['route']" :strict="false" :tooltip="__($resource['name'])" :compact="$compact" :badge="$resource['badge'] ?? null" :badgeColor="$resource['badgeColor'] ?? null">
                                    <div class="{{ $iconClass }}">
                                        {!! $resource['icon'] !!}
                                    </div>
                                    <div class="hide-collapsed">{{ __($resource['name']) }}</div>
                                </x-aura::navigation.item>
                            @endcan
                        @else

                            <x-aura::navigation.item :route="$resource['route']" :strict="false" :tooltip="__($resource['name'])" :compact="$compact" :badge="$resource['badge'] ?? null" :badgeColor="$resource['badgeColor'] ?? null" :onclick="$resource['onclick'] ?? ''"

                            >
                                @if($resource['icon'] !== '')
                                    <div class="{{ $iconClass }}">
                                        {!! Blade::render($resource['icon']) !!}
                                    </div>
                                    <div class="hide-collapsed">{{ __($resource['name']) }}</div>
                                @else
                                    <div>
                                        <div class="hidden show-collapsed text-xl text-center w-6 {{ $iconClass }}">
                                            {{ strtoupper(substr($resource['name'], 0, 1)) }}
                                        </div>
                                        <div class="hide-collapsed">{{ __($resource['name']) }}</div>
                                    </div>
                                @endif

                            </x-aura::navigation.item>
                        @endif
                    @endif

                @endforeach
            </div>
        @endif
    @endforeach

</div>
