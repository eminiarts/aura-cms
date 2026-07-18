<?php

use Aura\Base\Fields\BelongsToMany;
use Aura\Base\Fields\UserTeams;
use Aura\Base\Resources\User;

it('extends the parent-aware BelongsToMany field', function () {
    expect(new UserTeams)->toBeInstanceOf(BelongsToMany::class);
});

it('renders the dedicated Membership editor on the view page instead of the has-many table', function () {
    $field = new UserTeams;

    // The generic BelongsToMany still edits through the has-many table, but the
    // user View page (view()) renders the aura::user-teams Membership editor.
    expect($field->view())->toBe('aura::fields.user-teams')
        ->and($field->edit())->toBe('aura::fields.has-many');
});

it('is the type the User resource declares for its Teams tab', function () {
    $teamsField = collect(User::getFields())->firstWhere('slug', 'teams');

    expect($teamsField['type'])->toBe(UserTeams::class);
})->skip(fn () => ! config('aura.teams'), 'The Teams tab is a teams-on feature.');
