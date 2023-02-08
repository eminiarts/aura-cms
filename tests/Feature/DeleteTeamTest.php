<?php

namespace Tests\Feature;

use Eminiarts\Aura\Aura\Resources\Team;
use Eminiarts\Aura\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Http\Livewire\DeleteTeamForm;
use Livewire\Livewire;
use Eminiarts\Aura\Tests\TestCase;

class DeleteTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_personal_teams_cant_be_deleted()
    {
        return $this->markTestSkipped('Jetstream is removed.');

        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $component = Livewire::test(DeleteTeamForm::class, ['team' => $user->currentTeam])
                                ->call('deleteTeam')
                                ->assertHasErrors(['team']);

        $this->assertNotNull($user->currentTeam->fresh());
    }

    public function test_teams_can_be_deleted()
    {
        return $this->markTestSkipped('Jetstream is removed.');

        $this->actingAs($user = User::factory()->withPersonalTeam()->create());

        $user->ownedTeams()->save($team = Team::factory()->make([
            'personal_team' => false,
        ]));

        $team->users()->attach(
            $otherUser = User::factory()->create(),
            ['role' => 'test-role']
        );

        $component = Livewire::test(DeleteTeamForm::class, ['team' => $team->fresh()])
                                ->call('deleteTeam');

        $this->assertNull($team->fresh());
        $this->assertCount(0, $otherUser->fresh()->teams);
    }
}
