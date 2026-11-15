<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Tests\Resources\Post;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('a top-level list page has no breadcrumb trail and the bookmark beside its title', function () {
    $this->get(route('aura.role.index'))
        ->assertOk()
        ->assertDontSee('aria-label="Breadcrumb"', false)
        ->assertSee('data-page-bookmark', false);
});

test('a list under another page keeps its trail', function () {
    $this->get(route('aura.option.index'))
        ->assertOk()
        ->assertSee('aria-label="Breadcrumb"', false)
        ->assertSee(route('aura.settings'), false)
        ->assertDontSee('data-page-bookmark', false);
});

test('an empty list page holds its one create button in a centred block, without toolbar', function () {
    Aura::fake();
    Aura::setModel(new Post);

    $html = livewire(Table::class, ['model' => new Post, 'settings' => ['page_bookmark' => true]])->html();

    expect($html)->toContain('data-table-empty-page')
        ->and($html)->not->toContain('id="table-search"')
        ->and(substr_count($html, __('New :resource', ['resource' => 'post'])))->toBe(1);
});

test('the empty invitations page words its own Invite button once', function () {
    $html = livewire(Table::class, ['model' => new TeamInvitation])->html();

    expect($html)->toContain('Invite a user by email.')
        ->and(substr_count($html, 'Invite user'))->toBe(1);
})->skip(fn () => ! config('aura.teams'), 'Invitations need teams.');

test('the roles list counts users in one query and never treats the count as a field to save or filter', function () {
    $table = livewire(Table::class, ['model' => new Role]);

    expect($table->instance()->rowsQuery()->get()->every(fn ($role) => array_key_exists('users_count', $role->getAttributes())))->toBeTrue()
        ->and($table->instance()->fieldsForFilter->keys())->not->toContain('users_count');

    $table->call('sortBy', 'users_count')->assertOk();

    livewire(Create::class, ['slug' => 'role'])
        ->set('form.fields.name', 'Reviewer')
        ->set('form.fields.slug', 'reviewer')
        ->call('save')
        ->assertHasNoErrors();

    expect(Role::query()->where('slug', 'reviewer')->exists())->toBeTrue();
});
