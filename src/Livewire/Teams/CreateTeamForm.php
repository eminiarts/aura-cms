<?php

namespace Aura\Base\Livewire\Teams;

use Aura\Base\Resources\Team;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class CreateTeamForm extends Component
{
    use AuthorizesRequests;

    public $name = '';

    public function createTeam()
    {
        $this->authorize('create', [Team::class, new Team]);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $team = Team::create([
            'name' => $this->name,
            'user_id' => auth()->id(),
        ]);

        auth()->user()->switchTeam($team);

        $this->notify(__('Team created successfully.'));

        return redirect(config('aura.path').'/Team/'.$team->id.'/edit');
    }

    public function render()
    {
        return view('aura::teams.create-team-form');
    }
}
