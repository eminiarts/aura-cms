<div class="w-60 dark:bg-gray-700">
  <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
      {{ __('Manage Profile') }}
  </div>
  <x-aura::dropdown-link href="{{ route('aura.profile') }}">
      {{ __('View Profile') }}
  </x-aura::dropdown-link>

  <!-- Team Management -->
  <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
      {{ __('Manage Team') }}
  </div>

  @if(config('aura.teams'))      
  <!-- Team Settings -->
  <x-aura::dropdown-link href="{{ route('aura.post.edit', ['slug' => 'Team', 'id' => Auth::user()->current_team_id]) }}">
      {{ __('Team Settings') }}
  </x-aura::dropdown-link>
  @endif

  @can('create', Team::class)
      <x-aura::dropdown-link href="{{ route('aura.post.create', ['slug' => 'Team']) }}">
          {{ __('Create New Team') }}
      </x-aura::dropdown-link>
  @endcan

  <div class="my-2 border-t border-gray-100 dark:border-gray-600"></div>

  <!-- Team Switcher -->
  <div class="block px-4 py-2 text-xs text-gray-400 dark:text-gray-500">
      {{ __('Switch Teams') }}
  </div>

  @if(config('aura.teams'))      
  @foreach (Auth::user()->getTeams() as $team)
      <x-aura::switchable-team :team="$team" />
  @endforeach
  @endif
</div>
