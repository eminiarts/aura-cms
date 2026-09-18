<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Livewire\Exceptions\MethodNotFoundException;

use function Pest\Livewire\livewire;

/**
 * The Livewire `form` property round-trips through the browser, so every key in
 * it is attacker controlled. Create/Edit must rebuild the persisted payload from
 * the resource's declared input fields instead of handing the raw array to
 * update()/create() — otherwise a custom-table resource such as User leaks its
 * ownership columns (current_team_id is `$fillable`) and the actor can move a
 * user into a tenant they do not administer.
 */
test('the edit form ignores a client-supplied current_team_id on a custom-table resource', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $foreign = foreignTeam();
    $target = soleMemberOf($actor->currentTeam);

    $this->actingAs($actor);

    livewire(Edit::class, ['slug' => 'user', 'id' => $target->id])
        ->set('form.fields.name', 'Renamed')
        ->set('form.fields.current_team_id', $foreign->id)
        ->call('save');

    $fresh = User::withoutGlobalScopes()->findOrFail($target->id);

    expect((int) $fresh->current_team_id)->toBe((int) $actor->current_team_id)
        ->and((int) $fresh->current_team_id)->not->toBe((int) $foreign->id)
        ->and($fresh->name)->toBe('Renamed');
})->skip(fn () => ! config('aura.teams'), 'Requires teams');

test('the edit form ignores client-supplied ownership columns on a posts-table resource', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    Aura::fake();
    Aura::setModel(new Post);

    $post = Post::create(['title' => 'Original', 'type' => 'Post']);
    $originalTeamId = $post->team_id;

    livewire(Edit::class, ['slug' => 'post', 'id' => $post->id])
        ->set('form.fields.title', 'Updated')
        ->set('form.fields.team_id', 99999)
        ->set('form.fields.user_id', 99999)
        ->set('form.team_id', 99999)
        ->set('form.user_id', 99999)
        ->set('form.type', 'HackedType')
        ->call('save');

    $fresh = Post::withoutGlobalScope(TeamScope::class)->findOrFail($post->id);

    expect($fresh->title)->toBe('Updated')
        ->and($fresh->team_id)->toBe($originalTeamId)
        ->and($fresh->user_id)->not->toBe(99999)
        ->and($fresh->type)->toBe('Post');
});

/**
 * Both components used to override `callMethod()` and delegate to
 * `parent::callMethod()`, which does not exist in Livewire 4. Because the
 * override is a public, non-static method it was itself exposed as a callable
 * Livewire action — an arbitrary-method-dispatch gadget onto field classes.
 */
test('the edit component exposes no generic method dispatcher', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    Aura::fake();
    Aura::setModel(new Post);

    $post = Post::create(['title' => 'Original', 'type' => 'Post']);

    expect(fn () => livewire(Edit::class, ['slug' => 'post', 'id' => $post->id])
        ->call('callMethod', 'saved', 'title'))
        ->toThrow(MethodNotFoundException::class);
});

test('the create component exposes no generic method dispatcher', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    Aura::fake();
    Aura::setModel(new Post);

    expect(fn () => livewire(Create::class, ['slug' => 'post'])
        ->call('callMethod', 'saved', 'title'))
        ->toThrow(MethodNotFoundException::class);
});
