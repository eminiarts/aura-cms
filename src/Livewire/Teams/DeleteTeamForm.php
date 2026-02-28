<?php

namespace Aura\Base\Livewire\Teams;

use Aura\Base\Resources\Team;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class DeleteTeamForm extends Component
{
    use AuthorizesRequests;

    public Team $team;

    public $confirmTeamName = '';

    public function mount(Team $team)
    {
        $this->team = $team;
    }

    public function deleteTeam()
    {
        $this->authorize('delete', $this->team);

        $this->validate([
            'confirmTeamName' => ['required', 'string'],
        ]);

        if ($this->confirmTeamName !== $this->team->name) {
            $this->addError('confirmTeamName', __('The team name does not match.'));
            return;
        }

        $this->team->delete();

        $this->notify(__('Team deleted successfully.'));

        return redirect(config('aura.path') . '/dashboard');
    }

    public function render()
    {
        return view('aura::teams.delete-team-form');
    }
}
