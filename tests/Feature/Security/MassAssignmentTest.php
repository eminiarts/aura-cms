<?php

namespace Aura\Base\Tests\Feature\Security;

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Tests\Resources\Post;

use function Pest\Livewire\livewire;

// A malicious authenticated user must not be able to mass-assign tenancy /
// ownership columns (team_id, user_id, type) by injecting them into the
// Livewire `form` property. These are always assigned server-side.

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new Post);
});

it('ignores a client-supplied team_id when creating a resource', function () {
    livewire(Create::class, ['slug' => 'post'])
        ->set('form.fields.title', 'Injected')
        ->set('form.fields.slug', 'injected')
        ->set('form.team_id', 99999)
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::withoutGlobalScope(TeamScope::class)
        ->where('title', 'Injected')->firstOrFail();

    expect($post->team_id)->toBe($this->user->current_team_id)
        ->and($post->team_id)->not->toBe(99999);
});

it('ignores a client-supplied user_id when creating a resource', function () {
    livewire(Create::class, ['slug' => 'post'])
        ->set('form.fields.title', 'OwnerSpoof')
        ->set('form.fields.slug', 'ownerspoof')
        ->set('form.user_id', 99999)
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::withoutGlobalScope(TeamScope::class)
        ->where('title', 'OwnerSpoof')->firstOrFail();

    // The client-supplied user_id is stripped before persistence, so ownership
    // can never be spoofed via the form. (It is not otherwise assigned in this
    // flow, so it stays null rather than becoming the injected value.)
    expect($post->user_id)->not->toBe(99999);
});

it('ignores a client-supplied type when creating a resource', function () {
    livewire(Create::class, ['slug' => 'post'])
        ->set('form.fields.title', 'TypeSpoof')
        ->set('form.fields.slug', 'typespoof')
        ->set('form.type', 'HackedType')
        ->call('save')
        ->assertHasNoErrors();

    $post = Post::withoutGlobalScope(TeamScope::class)
        ->where('title', 'TypeSpoof')->firstOrFail();

    expect($post->type)->toBe('Post');
});
