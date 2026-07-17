<div class="w-64">
    @if(config('aura.teams'))
        @can('update', Auth::user()->currentTeam)
            <!-- Team Management -->
            <div class="block px-4 pt-2 pb-1 text-2xs font-medium tracking-wider uppercase text-gray-400 dark:text-gray-400 select-none">
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

            <div class="my-2 border-t border-gray-100 dark:border-white/10"></div>
        @endcan

        @if(Auth::user()->getTeams()->count() > 1)
            <!-- Team Switcher -->
            <div class="block px-4 pt-2 pb-1 text-2xs font-medium tracking-wider uppercase text-gray-400 dark:text-gray-400 select-none">
                {{ __('Switch Teams') }}
            </div>
            @foreach (Auth::user()->getTeams() as $team)
                <x-aura::switchable-team :team="$team" />
            @endforeach

            <div class="my-2 border-t border-gray-100 dark:border-white/10"></div>
        @endif
    @endif

  @if(config('aura.features.profile'))
  <div class="block px-4 pt-2 pb-1 text-2xs font-medium tracking-wider uppercase text-gray-400 dark:text-gray-400 select-none">
      {{ __('Manage Profile') }}
  </div>

  <x-aura::dropdown-link href="{{ route('aura.profile') }}">
      {{ __('View Profile') }}
  </x-aura::dropdown-link>
  @endif

  <form method="POST" action="{{ route('aura.logout') }}">
      @csrf
      <button type="submit" class="block px-4 py-2 w-full text-sm text-left text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800">
          {{ __('Logout') }}
      </button>
  </form>
</div>
