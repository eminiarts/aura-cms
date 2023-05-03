<div class="flex flex-col items-center flex-1 px-1 pb-5 space-y-1 overflow-x-visible overflow-y-auto scrollbar-thin
  @if ($sidebarType == 'primary')
    scrollbar-thumb-primary-500 scrollbar-track-primary-700 dark:scrollbar-thumb-gray-900 dark:scrollbar-track-gray-800
  @elseif ($sidebarType == 'light')
    scrollbar-thumb-gray-300 scrollbar-track-gray-50 dark:scrollbar-thumb-gray-900 dark:scrollbar-track-gray-800
  @elseif ($sidebarType == 'dark')
    scrollbar-thumb-gray-700 scrollbar-track-gray-800 dark:scrollbar-thumb-gray-900
  @endif
">

    <x-aura::navigation.item-icon @click="$dispatch('search')" class="cursor-pointer" tooltip="Search">
        <x-aura::icon icon="search" />
    </x-aura::navigation.item-icon>


    <x-aura::navigation.item-icon route="aura.dashboard" tooltip="aura.dashboard">
        <x-aura::icon icon="dashboard2" />
    </x-aura::navigation.item-icon>

    @foreach(app('aura')::navigation() as $group => $resources)
      @foreach($resources as $resource)

          @if (isset($resource['dropdown']) && $resource['dropdown'] !== false)
              <x-aura::navigation.dropdown-icon>
                  <x-slot:title>
                      <div>

                          @php
                              $iconView = 'aura::aura.navigation.icons.' . Str::slug($resource['dropdown'] );
                          @endphp

                          @includeIf($iconView, ['class' => 'w-6 h-6'])

                          @if(! View::exists($iconView))
                              {!! $resource['items'][0]['icon'] !!}
                          @endif
                      </div>
                  </x-slot:title>

                  @foreach($resource['items'] as $resource)
                      <x-aura::navigation.item-dropdown route="aura.post.index" :id="$resource['type']" :strict="false">
                          <div class="{{ $iconClass }}">
                              {!! $resource['icon'] !!}
                          </div>
                          <div>{{ __($resource['name']) }}</div>
                      </x-aura::navigation.item-dropdown>
                  @endforeach
              </x-aura::navigation.dropdown-icon>

          @else

              <x-aura::navigation.item-icon route="aura.post.index" :id="$resource['type']" :tooltip="$resource['name']" :strict="false">
                <div class="{{ $iconClass }}">
                  {!! $resource['icon'] !!}
                </div>
              </x-aura::navigation.item-icon>

          @endif
      @endforeach
      <div class="w-full px-2 py-2">
          @if ($sidebarType == 'primary')
          <hr class="w-full border-primary-500 dark:border-gray-700">
          @elseif ($sidebarType == 'light')
          <hr class="w-full border-gray-500/30 dark:border-gray-700">
          @elseif ($sidebarType == 'dark')
          <hr class="w-full border-gray-700">
          @endif
      </div>
    @endforeach


    @foreach(app('aura')::getTaxonomies() as $taxonomy)
        <x-aura::navigation.item-icon route="aura.taxonomy.index" :id="$taxonomy" :tooltip="$taxonomy" :strict="false">
            <x-aura::icon icon="circle" />
        </x-aura::navigation.item-icon>
    @endforeach


    {{-- <x-aura::navigation.heading>
        Admin
    </x-aura::navigation.heading> --}}
    <div class="w-full px-2 py-2">
        @if ($sidebarType == 'primary')
        <hr class="w-full border-primary-500 dark:border-gray-700">
        @elseif ($sidebarType == 'light')
        <hr class="w-full border-gray-500/30 dark:border-gray-700">
        @elseif ($sidebarType == 'dark')
        <hr class="w-full border-gray-700">
        @endif
    </div>

    <x-aura::navigation.dropdown-icon>

        <x-slot:title>

        <x-aura::icon icon="circle" />

        </x-slot>

        <x-aura::navigation.item-dropdown route="aura.dashboard" compact>
            <div>All users</div>
        </x-aura::navigation.item-dropdown>
        <x-aura::navigation.item-dropdown route="aura.dashboard" compact>
            <div>Teams</div>
        </x-aura::navigation.item-dropdown>
        <x-aura::navigation.item-dropdown route="aura.dashboard" compact>
            <div>Roles</div>
        </x-aura::navigation.item-dropdown>
        <x-aura::navigation.item-dropdown route="aura.dashboard" compact>
            <div>Permissions</div>
        </x-aura::navigation.item-dropdown>

    </x-aura::navigation.dropdown-icon>



    {{-- <x-aura::navigation.heading>
        Development
    </x-aura::navigation.heading> --}}
    <div class="w-full px-2 py-2">
        @if ($sidebarType == 'primary')
        <hr class="w-full border-primary-500 dark:border-gray-700">
        @elseif ($sidebarType == 'light')
        <hr class="w-full border-gray-500/30 dark:border-gray-700">
        @elseif ($sidebarType == 'dark')
        <hr class="w-full border-gray-700">
        @endif
    </div>

    {{-- <x-aura::navigation.item-icon route="aura.components" tooltip="Components" :strict="false">
        <x-aura::icon icon="color-swatch" />
    </x-aura::navigation.item-icon> --}}

    {{-- <x-aura::navigation.item-icon route="posttypes" tooltip="Posttypes" :strict="false">
        <x-aura::icon icon="collection" />
    </x-aura::navigation.item-icon> --}}

    @local
    <x-aura::navigation.item-icon onclick="Livewire.emit('openModal', 'create-posttype')" tooltip="{{ __('Create Posttype') }}" :strict="false">
        <x-aura::icon icon="collection" />
    </x-aura::navigation.item-icon>
    @endlocal

    {{-- <x-aura::navigation.item-icon route="charts" tooltip="Charts" :strict="false">
        <x-aura::icon icon="adjustments" />
    </x-aura::navigation.item-icon> --}}

    <x-aura::navigation.item-icon route="aura.team.settings" tooltip="Team Settings" :strict="false">
        <x-aura::icon icon="adjustments" />
    </x-aura::navigation.item-icon>

</div>
