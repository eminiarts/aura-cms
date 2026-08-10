<?php

use Aura\Base\Fields\Text;
use Aura\Base\Models\Meta;
use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Resource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

class Core14OwnedDocument extends Resource
{
    public static $customTable = true;

    public static ?string $ownerColumn = 'owner_id';

    public static ?string $ownerRelation = 'assignee';

    public static array $physicalFields = ['name', 'physical_secret', 'owner_id', 'team_id'];

    public static ?string $slug = 'core14-owned-document';

    public static string $type = 'Core14OwnedDocument';

    protected $fillable = ['name', 'owner_id', 'team_id'];

    protected $table = 'core14_owned_documents';

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => Text::class],
            ['name' => 'Physical secret', 'slug' => 'physical_secret', 'type' => Text::class, 'disabled' => true],
            ['name' => 'Notes', 'slug' => 'notes', 'type' => Text::class],
        ];
    }
}

class Core14TeamCatalog extends Resource
{
    public static $customTable = true;

    public static array $physicalFields = ['name', 'team_id'];

    public static string $scopeMode = self::SCOPE_TEAM;

    public static ?string $slug = 'core14-team-catalog';

    public static string $type = 'Core14TeamCatalog';

    public static bool $usesMeta = false;

    protected $fillable = ['name', 'team_id'];

    protected $table = 'core14_team_catalogs';

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => Text::class],
        ];
    }
}

class Core14GlobalCatalog extends Resource
{
    public static $customTable = true;

    public static array $physicalFields = ['name'];

    public static string $scopeMode = self::SCOPE_GLOBAL;

    public static ?string $slug = 'core14-global-catalog';

    public static string $type = 'Core14GlobalCatalog';

    public static bool $usesMeta = false;

    protected $fillable = ['name'];

    protected $table = 'core14_global_catalogs';

    public static function getFields(): array
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => Text::class],
        ];
    }
}

beforeEach(function (): void {
    $this->actingAs($this->actor = createSuperAdmin());

    Schema::create('core14_owned_documents', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('physical_secret')->nullable();
        $table->foreignId('owner_id');
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    Schema::create('core14_team_catalogs', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->foreignId('team_id');
        $table->timestamps();
    });

    Schema::create('core14_global_catalogs', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('core14_owned_documents');
    Schema::dropIfExists('core14_team_catalogs');
    Schema::dropIfExists('core14_global_catalogs');
});

test('owner resources use their declared column relation and physical storage contract', function (): void {
    $resource = Core14OwnedDocument::create([
        'name' => 'Contract',
        'notes' => 'Meta note',
        'physical_secret' => 'forged',
        'unknown_column' => 'forged',
        'unknown_meta' => 'forged',
    ]);

    expect(Core14OwnedDocument::getScopeMode())->toBe(Resource::SCOPE_OWNER)
        ->and(Core14OwnedDocument::getOwnerColumn())->toBe('owner_id')
        ->and(Core14OwnedDocument::getOwnerRelation())->toBe('assignee')
        ->and($resource->owner_id)->toBe($this->actor->getKey())
        ->and($resource->assignee->is($this->actor))->toBeTrue()
        ->and($resource->getFillable())->toBe(['name', 'owner_id', 'team_id'])
        ->and($resource->isTableField('physical_secret'))->toBeTrue()
        ->and($resource->physical_secret)->toBeNull()
        ->and($resource->getMeta('notes'))->toBe('Meta note')
        ->and($resource->getMeta('unknown_meta'))->toBeNull()
        ->and(Meta::query()->where('metable_type', Core14OwnedDocument::class)->pluck('key')->all())
        ->toBe(['notes']);
});

test('owner query scope and policy use the same declared owner column', function (): void {
    $owned = Core14OwnedDocument::createForOwnerForSystem($this->actor->getKey(), ['name' => 'Owned']);
    $other = createAdmin();
    $foreign = Core14OwnedDocument::createForOwnerForSystem($other->getKey(), ['name' => 'Foreign']);

    $role = $other->roles()->firstOrFail();
    $permissions = $role->permissions;
    $permissions['scope-core14-owned-document'] = true;
    $permissions['view-core14-owned-document'] = true;
    $permissions['viewAny-core14-owned-document'] = true;
    $role->forceFill(['permissions' => $permissions])->save();

    $this->actingAs($other->refresh());
    ScopedScope::flushState();

    expect(Core14OwnedDocument::query()->pluck('id')->all())->toBe([$foreign->getKey()])
        ->and((new ResourcePolicy)->view($other, $foreign))->toBeTrue()
        ->and((new ResourcePolicy)->view($other, $owned))->toBeFalse();
});

test('team-only and global resources never require missing owner or tenant columns', function (): void {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team-only persistence requires teams.');
    }

    $team = Core14TeamCatalog::create(['name' => 'Team catalog', 'user_id' => 999]);
    $global = Core14GlobalCatalog::create([
        'name' => 'Global catalog',
        'user_id' => 999,
        'team_id' => 999,
    ]);

    expect(Core14TeamCatalog::usesOwnerScope())->toBeFalse()
        ->and(Core14TeamCatalog::usesTeamScope())->toBeTrue()
        ->and($team->getTeamId())->toBe($this->actor->currentTeamIdForAuthorization())
        ->and(Core14GlobalCatalog::usesOwnerScope())->toBeFalse()
        ->and(Core14GlobalCatalog::usesTeamScope())->toBeFalse()
        ->and($global->name)->toBe('Global catalog')
        ->and(Core14GlobalCatalog::query()->count())->toBe(1);
});

test('team and owner named helpers reject resources without those capabilities', function (): void {
    expect(fn () => Core14GlobalCatalog::createForOwnerForSystem($this->actor->getKey(), ['name' => 'No']))
        ->toThrow(LogicException::class, 'Owner writes require')
        ->and(fn () => Core14GlobalCatalog::createForTeamForSystem(1, ['name' => 'No']))
        ->toThrow(LogicException::class, 'Team writes require');
});

test('scope declarations expose only the authorization attributes that exist', function (): void {
    $ownerAttributes = config('aura.teams') ? ['team_id', 'owner_id'] : ['owner_id'];
    $teamAttributes = config('aura.teams') ? ['team_id'] : [];

    expect((new Core14OwnedDocument)->embeddedAuthorizationAttributeNames())->toBe($ownerAttributes)
        ->and((new Core14TeamCatalog)->embeddedAuthorizationAttributeNames())->toBe($teamAttributes)
        ->and((new Core14GlobalCatalog)->embeddedAuthorizationAttributeNames())->toBe([]);
});

test('global resources remain queryable when the request has no team context', function (): void {
    Core14GlobalCatalog::create(['name' => 'Shared']);

    if (config('aura.teams')) {
        $this->actor->forceFill(['current_team_id' => null])->save();
        $this->actingAs($this->actor->refresh());
    }

    TeamScope::flushState();

    expect(Core14GlobalCatalog::query()->pluck('name')->all())->toBe(['Shared']);
});
