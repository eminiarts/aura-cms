<div class="w-60 dark:bg-gray-700">

    @if(config('aura.teams'))
        @can('update', Team::class)
            <!-- Team Management -->
            <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
                {{ __('Manage Team') }}
            </div>

            <!-- Team Settings -->
            <x-aura::dropdown-link href="{{ route('aura.resource.edit', ['slug' => 'Team', 'id' => Auth::user()->current_team_id]) }}">
                {{ __('Team Settings') }}
            </x-aura::dropdown-link>

            <div class="my-2 border-t border-gray-100 dark:border-gray-600"></div>
        @endcan


        @can('create', Team::class)
            <x-aura::dropdown-link href="{{ route('aura.resource.create', ['slug' => 'Team']) }}">
                {{ __('Create New Team') }}
            </x-aura::dropdown-link>
        @endcan


        <!-- Team Switcher -->
        <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
            {{ __('Switch Teams') }}
        </div>


        @foreach (Auth::user()->getTeams() as $team)
            <x-aura::switchable-team :team="$team" />
        @endforeach
    @endif

  @if(config('aura.features.user_profile'))
  <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
      {{ __('Manage Profile') }}
  </div>

  <x-aura::dropdown-link href="{{ route('aura.profile') }}">
      {{ __('View Profile') }}
  </x-aura::dropdown-link>
  @endif

  <x-aura::dropdown-link href="{{ route('logout') }}">
      {{ __('Logout') }}
  </x-aura::dropdown-link>

</div>
