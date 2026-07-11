<?php

namespace Aura\Base\Tests\Feature\Security;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\BelongsTo;
use Aura\Base\Resources\Role;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function Pest\Laravel\postJson;

// The /api/fields/values endpoint takes a client-controlled field and model
// class string. These tests lock down the hardening in FieldsController:
// only real Aura field/resource classes may be instantiated, and the caller
// must be authorized to view the target resource.

it('rejects an arbitrary (non-field) class for the field parameter', function () {
    $this->actingAs(createSuperAdmin());

    postJson(route('aura.api.fields.values'), [
        'model' => Role::class,
        'slug' => 'text',
        'field' => Str::class, // not an Aura field
    ])->assertStatus(400)->assertJson(['error' => 'Invalid field']);
});

it('rejects a class string that is not an Aura resource for the model parameter', function () {
    $this->actingAs(createSuperAdmin());

    postJson(route('aura.api.fields.values'), [
        'model' => Collection::class, // not a resource
        'slug' => 'text',
        'field' => BelongsTo::class,
    ])->assertStatus(400)->assertJson(['error' => 'Invalid model']);
});

it('forbids searching a resource the user is not allowed to view', function () {
    // Editor role: viewAny-post is explicitly false (see createAdmin()).
    $this->actingAs(createAdmin());

    Aura::fake();
    Aura::setModel(new Post);

    postJson(route('aura.api.fields.values'), [
        'model' => Post::class,
        'slug' => 'title',
        'field' => BelongsTo::class,
    ])->assertStatus(403)->assertJson(['error' => 'Unauthorized']);
});

it('still returns values for an authorized super admin request', function () {
    $this->actingAs(createSuperAdmin());

    postJson(route('aura.api.fields.values'), [
        'model' => Role::class,
        'slug' => 'text',
        'field' => BelongsTo::class,
    ])->assertStatus(200);
});
