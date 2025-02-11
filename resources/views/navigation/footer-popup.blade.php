<div class="w-64">
    @if(config('aura.teams'))
        @can('update', Auth::user()->currentTeam)
            <!-- Team Management -->
            <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-400">
                {{ __('Manage Team') }}
            </div>

            <!-- Team Settings -->
            <x-aura::dropdown-link href="{{ route('aura.team.edit', ['id' => Auth::user()->current_team_id]) }}">
                {{ __('Team Settings') }}
            </x-aura::dropdown-link>

             @can('create', app(config('aura.resources.team')))
            <x-aura::dropdown-link href="{{ route('aura.team.create') }}">
                {{ __('Create New Team') }}
            </x-aura::dropdown-link>
        @endcan

            <div class="my-2 border-t border-gray-100 dark:border-gray-600"></div>
        @endcan

        @if(Auth::user()->getTeams()->count() > 1)
            <!-- Team Switcher -->
            <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-400">
                {{ __('Switch Teams') }}
            </div>
            @foreach (Auth::user()->getTeams() as $team)
                <x-aura::switchable-team :team="$team" />
            @endforeach

            <div class="my-2 border-t border-gray-100 dark:border-gray-600"></div>
        @endif
    @endif

  @if(config('aura.features.profile'))
  <div class="block px-4 py-2 text-xs font-semibold text-gray-400 dark:text-gray-400">
      {{ __('Manage Profile') }}
  </div>

  <x-aura::dropdown-link href="{{ route('aura.profile') }}">
      {{ __('View Profile') }}
  </x-aura::dropdown-link>
  @endif

  <x-aura::dropdown-link href="{{ route('aura.logout') }}">
      {{ __('Logout') }}
  </x-aura::dropdown-link>
</div>
