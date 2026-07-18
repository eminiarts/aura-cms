<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\BelongsTo;
use Aura\Base\Fields\Wysiwyg;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\postJson;
use function Pest\Livewire\livewire;

class SecurityGapResource extends Resource
{
    public static $singularName = 'Security Gap';

    public static ?string $slug = 'security-gap';

    public static string $type = 'SecurityGap';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'searchable' => true,
            ],
        ];
    }

    public function title()
    {
        return $this->title;
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

it('does not allow logout through a GET request', function () {
    $this->get(route('aura.logout'))->assertMethodNotAllowed();

    expect(auth()->check())->toBeTrue();
});

it('escapes stored filter names', function () {
    Aura::fake();
    Aura::registerResources([SecurityGapResource::class]);
    Aura::setModel(new SecurityGapResource);

    $payload = '<img src=x onerror=alert(1)>';

    $this->user->updateOption('SecurityGap.filters.malicious', [
        'name' => $payload,
        'icon' => $payload,
        'slug' => 'malicious',
        'custom' => [],
        'global' => false,
        'public' => false,
    ]);

    livewire(Table::class, ['query' => null, 'model' => new SecurityGapResource])
        ->assertDontSee($payload, false)
        ->assertSee(e($payload), false);
});

it('sanitizes unsafe rich text while preserving safe formatting', function () {
    $value = '<p onclick="alert(1)">Hello <strong>World</strong></p><script>alert(2)</script>';

    $display = (new Wysiwyg)->display(['slug' => 'body'], $value, new SecurityGapResource);

    expect($display)
        ->toContain('<p>Hello <strong>World</strong></p>')
        ->not->toContain('onclick')
        ->not->toContain('<script');
});

it('prevents a delegated user manager from assigning a super admin role', function () {
    $team = $this->user->currentTeam;
    // Attach-don't-mint: the Super Admin role is the shared global admin
    // (team_id = null), assignable within the team as a Global Role.
    $superAdminRole = Role::withoutGlobalScope(TeamScope::class)
        ->whereNull('team_id')
        ->where('super_admin', true)
        ->firstOrFail();

    $managerRole = Role::create([
        'team_id' => $team->id,
        'type' => 'Role',
        'name' => 'User manager',
        'slug' => 'user-manager',
        'super_admin' => false,
        'permissions' => [
            'viewAny-user' => true,
            'view-user' => true,
            'update-user' => true,
        ],
    ]);

    $manager = User::factory()->create(['current_team_id' => $team->id]);
    $manager->roles()->attach($managerRole->id, ['team_id' => $team->id]);

    $target = User::factory()->create(['current_team_id' => $team->id]);
    $target->roles()->attach($managerRole->id, ['team_id' => $team->id]);

    $this->actingAs($manager);

    Aura::fake();
    Aura::setModel(new User);

    livewire(Edit::class, ['slug' => 'user', 'id' => $target->id])
        ->set('form.fields.roles', [$superAdminRole->id])
        ->call('save')
        ->assertStatus(403);

    expect($target->fresh()->roles()->pluck('roles.id')->all())
        ->toBe([$managerRole->id]);
})->skip(fn () => ! config('aura.teams'), 'Role delegation across teams requires teams enabled.');

it('does not serve an attachment owned by another team', function () {
    Storage::fake('public');
    config(['aura.media.restrict_to_dimensions' => false]);

    $otherTeam = Team::factory()->createQuietly();
    $attachment = Attachment::withoutGlobalScope(TeamScope::class)->create([
        'team_id' => $otherTeam->id,
        'type' => 'Attachment',
        'name' => 'Other team image',
        'title' => 'Other team image',
        'url' => 'media/other-team.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 10,
    ]);

    Storage::disk('public')->put($attachment->url, base64_decode(
        '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIQAxAAAAF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABBQJ//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAwEBPwF//8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAgBAgEBPwF//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQAGPwJ//8QAFBABAAAAAAAAAAAAAAAAAAAAAP/aAAgBAQABPyF//9oADAMBAAIAAwAAABD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAEDAQE/EB//xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oACAECAQE/EB//xAAUEAEAAAAAAAAAAAAAAAAAAAAA/9oACAEBAAE/EB//2Q=='
    ));

    $this->get(route('aura.image', ['path' => $attachment->url, 'width' => 20]))
        ->assertNotFound();
})->skip(fn () => ! config('aura.teams'), 'Cross-team media isolation requires teams enabled.');

it('isolates table reads and edit writes by team', function () {
    $currentTeamPost = SecurityGapResource::create([
        'team_id' => $this->user->current_team_id,
        'type' => SecurityGapResource::$type,
        'title' => 'Current team record',
    ]);
    $otherTeamPost = SecurityGapResource::withoutGlobalScope(TeamScope::class)->create([
        'team_id' => Team::factory()->createQuietly()->id,
        'type' => SecurityGapResource::$type,
        'title' => 'Other team record',
    ]);

    Aura::fake();
    Aura::registerResources([SecurityGapResource::class]);
    Aura::setModel(new SecurityGapResource);

    livewire(Table::class, ['query' => null, 'model' => new SecurityGapResource])
        ->assertViewHas('rows', function ($rows) use ($currentTeamPost, $otherTeamPost): bool {
            $ids = collect($rows->items())->pluck('id');

            return $ids->contains($currentTeamPost->id) && ! $ids->contains($otherTeamPost->id);
        });

    livewire(Edit::class, ['slug' => 'security-gap', 'id' => $otherTeamPost->id])
        ->assertForbidden();
})->skip(fn () => ! config('aura.teams'), 'Cross-team resource isolation requires teams enabled.');

it('isolates global search results by team', function () {
    config(['aura.features.global_search' => true]);

    SecurityGapResource::withoutGlobalScope(TeamScope::class)->create([
        'team_id' => Team::factory()->createQuietly()->id,
        'type' => SecurityGapResource::$type,
        'title' => 'Other team global search needle',
    ]);

    Aura::fake();
    Aura::registerResources([SecurityGapResource::class]);
    Aura::setModel(new SecurityGapResource);

    livewire(GlobalSearch::class)
        ->set('search', 'Other team global search needle')
        ->assertDontSee('Other team global search needle');
})->skip(fn () => ! config('aura.teams'), 'Cross-team search isolation requires teams enabled.');

it('isolates field API results by team', function () {
    SecurityGapResource::withoutGlobalScope(TeamScope::class)->create([
        'team_id' => Team::factory()->createQuietly()->id,
        'type' => SecurityGapResource::$type,
        'title' => 'Other team field API needle',
    ]);

    postJson(route('aura.api.fields.values'), [
        'model' => SecurityGapResource::class,
        'slug' => 'title',
        'field' => BelongsTo::class,
        'search' => 'Other team field API needle',
    ])->assertOk()->assertJsonMissing([
        'title' => 'Other team field API needle',
    ]);
})->skip(fn () => ! config('aura.teams'), 'Cross-team field API isolation requires teams enabled.');
