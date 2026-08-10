<?php

use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Models\Scopes\TypeScope;
use Aura\Base\Resource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

class Core15ArticleResource extends Resource
{
    public static int $bootedCalls = 0;

    public static int $creatingCalls = 0;

    public static string $type = 'Core15Article';

    protected static function booted(): void
    {
        self::$bootedCalls++;

        static::creating(function (): void {
            self::$creatingCalls++;
        });
    }
}

class Core15NoteResource extends Resource
{
    public static string $type = 'Core15Note';
}

abstract class Core15CustomInheritanceResource extends Resource
{
    public static $customTable = true;

    public static ?string $inheritanceColumn = 'record_kind';

    public static string $scopeMode = self::SCOPE_GLOBAL;

    public static bool $usesMeta = false;

    protected $fillable = ['name'];

    protected $table = 'core15_inherited_records';
}

class Core15CustomAlphaResource extends Core15CustomInheritanceResource
{
    public static ?string $inheritanceValue = 'alpha';
}

class Core15CustomBetaResource extends Core15CustomInheritanceResource
{
    public static ?string $inheritanceValue = 'beta';
}

class Core15PlainCustomResource extends Resource
{
    public static $customTable = true;

    public static string $scopeMode = self::SCOPE_GLOBAL;

    public static bool $usesMeta = false;

    protected $fillable = ['name'];

    protected $table = 'core15_plain_records';
}

class Core15OwnerResource extends Core15PlainCustomResource
{
    public static ?string $ownerColumn = 'owner_id';

    public static string $scopeMode = self::SCOPE_OWNER;
}

class Core15TeamResource extends Core15PlainCustomResource
{
    public static string $scopeMode = self::SCOPE_TEAM;
}

class Core15InvalidColumnResource extends Core15PlainCustomResource
{
    public static ?string $inheritanceColumn = '   ';
}

class Core15InvalidValueResource extends Resource
{
    public static ?string $inheritanceValue = '   ';
}

class Core15OrphanValueResource extends Core15PlainCustomResource
{
    public static ?string $inheritanceValue = 'orphan';
}

beforeEach(function (): void {
    Core15ArticleResource::$bootedCalls = 0;
    Core15ArticleResource::$creatingCalls = 0;

    $this->actingAs($this->actor = createSuperAdmin());

    Schema::create('core15_inherited_records', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->string('record_kind');
        $table->timestamps();
    });

    Schema::create('core15_plain_records', function (Blueprint $table): void {
        $table->id();
        $table->string('name');
        $table->foreignId('owner_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('core15_inherited_records');
    Schema::dropIfExists('core15_plain_records');
});

test('subclass booted hooks do not need to call parent to retain Aura boot behavior', function (): void {
    $resource = Core15ArticleResource::create(['type' => 'forged']);
    new Core15ArticleResource;

    expect(Core15ArticleResource::$bootedCalls)->toBe(1)
        ->and(Core15ArticleResource::$creatingCalls)->toBe(1)
        ->and($resource->type)->toBe('Core15Article')
        ->and(array_keys($resource->getGlobalScopes()))->toBe([
            TypeScope::class,
            TeamScope::class,
            ScopedScope::class,
        ]);
});

test('post table siblings retain their type isolation and deliberate scope removal', function (): void {
    $article = Core15ArticleResource::create();
    $note = Core15NoteResource::create();

    expect(Core15ArticleResource::query()->pluck('id')->all())->toBe([$article->getKey()])
        ->and(Core15NoteResource::query()->pluck('id')->all())->toBe([$note->getKey()])
        ->and(Core15ArticleResource::withoutGlobalScope(TypeScope::class)->whereKey($note)->exists())->toBeTrue()
        ->and(Core15ArticleResource::withoutGlobalScopes()->whereKey($note)->exists())->toBeTrue();
});

test('custom table resources can declare a qualified discriminator and stamp it on create', function (): void {
    $alpha = Core15CustomAlphaResource::create(['name' => 'Alpha', 'record_kind' => 'forged']);
    $beta = Core15CustomBetaResource::create(['name' => 'Beta']);

    expect($alpha->record_kind)->toBe('alpha')
        ->and($beta->record_kind)->toBe('beta')
        ->and(Core15CustomAlphaResource::query()->pluck('name')->all())->toBe(['Alpha'])
        ->and(Core15CustomBetaResource::query()->pluck('name')->all())->toBe(['Beta'])
        ->and(Core15CustomAlphaResource::withoutGlobalScope(TypeScope::class)->count())->toBe(2)
        ->and(DB::table('core15_inherited_records')->pluck('record_kind')->all())->toBe(['alpha', 'beta']);
});

test('plain custom tables do not receive an unrelated type constraint', function (): void {
    Core15PlainCustomResource::create(['name' => 'Plain']);

    $resource = new Core15PlainCustomResource;

    expect($resource->getGlobalScopes())->not->toHaveKey(TypeScope::class)
        ->and(Core15PlainCustomResource::query()->pluck('name')->all())->toBe(['Plain']);
});

test('all ownership modes retain the applicable Aura scopes with teams on or off', function (): void {
    $owner = new Core15OwnerResource;
    $team = new Core15TeamResource;
    $global = new Core15PlainCustomResource;

    expect($owner->getGlobalScopes())->toHaveKeys([TeamScope::class, ScopedScope::class])
        ->and($team->getGlobalScopes())->toHaveKeys([TeamScope::class, ScopedScope::class])
        ->and($global->getGlobalScopes())->toHaveKeys([TeamScope::class, ScopedScope::class])
        ->and(Core15OwnerResource::usesOwnerScope())->toBeTrue()
        ->and(Core15TeamResource::usesOwnerScope())->toBeFalse()
        ->and(Core15TeamResource::usesTeamScope())->toBe((bool) config('aura.teams'))
        ->and(Core15PlainCustomResource::usesOwnerScope())->toBeFalse()
        ->and(Core15PlainCustomResource::usesTeamScope())->toBeFalse();
});

test('background queries remain fail closed unless a trusted team context is explicit', function (): void {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Tenant query contexts only apply when teams are enabled.');
    }

    $teamId = $this->actor->currentTeamIdForAuthorization();
    Core15TeamResource::create(['name' => 'Team row']);
    Auth::logout();

    expect(Core15TeamResource::query()->count())->toBe(0)
        ->and(TeamScope::forTeam($teamId, fn (): int => Core15TeamResource::query()->count()))->toBe(1);
});

test('invalid discriminator declarations fail with actionable messages', function (): void {
    expect(fn () => new Core15InvalidColumnResource)
        ->toThrow(LogicException::class, 'must declare a non-empty inheritance column')
        ->and(fn () => new Core15InvalidValueResource)
        ->toThrow(LogicException::class, 'must declare a non-empty inheritance value')
        ->and(fn () => new Core15OrphanValueResource)
        ->toThrow(LogicException::class, 'cannot declare an inheritance value without an inheritance column');
});
