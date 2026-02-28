<?php

namespace Aura\Base\Livewire\Teams;

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class TeamMemberManager extends Component
{
    use AuthorizesRequests;

    public Team $team;

    public $showInviteModal = false;

    public $roleToUpdate;

    public $memberIdToUpdate;

    public function mount(Team $team)
    {
        $this->team = $team;
    }

    public function openInviteModal()
    {
        $this->authorize('invite-users', $this->team);

        $this->showInviteModal = true;
    }

    public function removeMember($userId)
    {
        $this->authorize('removeTeamMember', $this->team);

        if ($this->team->user_id === $userId) {
            $this->notify(__('Cannot remove the team owner.'), 'error');
            return;
        }

        $this->team->users()->detach($userId);

        $this->notify(__('Team member removed successfully.'));
    }

    public function updateMemberRole($userId, $roleId)
    {
        $this->authorize('updateTeamMember', $this->team);

        if ($this->team->user_id === $userId) {
            $this->notify(__('Cannot change the team owner role.'), 'error');
            return;
        }

        $this->team->users()->updateExistingPivot($userId, ['role_id' => $roleId]);

        $this->notify(__('Member role updated successfully.'));
    }

    public function cancelInvitation($invitationId)
    {
        $this->authorize('removeTeamMember', $this->team);

        $this->team->teamInvitations()->where('id', $invitationId)->delete();

        $this->notify(__('Invitation cancelled.'));
    }

    public function getRolesProperty()
    {
        return Role::where('team_id', $this->team->id)->get();
    }

    public function getMembersProperty()
    {
        return $this->team->users()->get();
    }

    public function getInvitationsProperty()
    {
        return $this->team->teamInvitations()->get();
    }

    public function render()
    {
        return view('aura::teams.team-member-manager');
    }
}
