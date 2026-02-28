<?php

namespace Aura\Base\Livewire\Teams;

use Aura\Base\Resources\Team;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class UpdateTeamNameForm extends Component
{
    use AuthorizesRequests;

    public Team $team;

    public $name;

    public function mount(Team $team)
    {
        $this->team = $team;
        $this->name = $team->name;
    }

    public function updateTeamName()
    {
        $this->authorize('update', $this->team);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $this->team->update(['name' => $this->name]);

        $this->notify(__('Team name updated successfully.'));
    }

    public function render()
    {
        return view('aura::teams.update-team-name-form');
    }
}
