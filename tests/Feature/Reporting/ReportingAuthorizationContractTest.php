<?php

use Aura\Base\Aura;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Text;
use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Reporting\AggregateDefinition;
use Aura\Base\Reporting\AggregateOperation;
use Aura\Base\Reporting\ResourceAggregateEngine;
use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Fixtures\Reporting\AuthorizedReportingQueryProbe;
use Aura\Base\Tests\Fixtures\Reporting\ReportingGroupPoint;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

final class Core28AuthorizedReportingResource extends Resource
{
    use SoftDeletes;

    public static $customTable = true;

    public static array $physicalFields = ['amount', 'category', 'flag', 'stage', 'visible'];

    public static ?string $slug = 'core28-authorized-reporting-resource';

    public static string $type = 'Core28AuthorizedReportingResource';

    public static bool $usesMeta = false;

    protected $fillable = ['amount', 'category', 'flag', 'stage', 'visible'];

    protected $table = 'core28_authorized_reporting_resources';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Amount',
                'slug' => 'amount',
                'type' => Number::class,
                'number_type' => 'decimal',
                'precision' => 18,
                'scale' => 6,
            ],
            ['name' => 'Category', 'slug' => 'category', 'type' => Text::class],
            ['name' => 'Flag', 'slug' => 'flag', 'type' => Boolean::class],
            [
                'name' => 'Stage',
                'slug' => 'stage',
                'type' => Select::class,
                'options' => [
                    ['key' => 'closed', 'value' => 'Closed label'],
                    ['key' => 'open', 'value' => 'Open label'],
                ],
            ],
            ['name' => 'Visible', 'slug' => 'visible', 'type' => Boolean::class],
        ];
    }

    /** @param Builder<Core28AuthorizedReportingResource> $query */
    public function indexQuery(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('visible'), true);
    }
}

/**
 * @template TResult
 *
 * @param  Closure(AuthorizedReportingQueryProbe): TResult  $assertions
 * @return TResult
 */
function withCore28AuthorizedReportingQuery(Closure $assertions): mixed
{
    Schema::create('core28_authorized_reporting_resources', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->foreignId('team_id')->nullable();
        $table->decimal('amount', 18, 6)->nullable();
        $table->string('category')->nullable();
        $table->boolean('flag');
        $table->string('stage')->nullable();
        $table->boolean('visible');
        $table->timestamps();
        $table->softDeletes();
    });

    try {
        return $assertions(new AuthorizedReportingQueryProbe);
    } finally {
        Schema::dropIfExists('core28_authorized_reporting_resources');
    }
}

test('reporting starts from the authorized resource query and preserves every resource boundary', function (): void {
    withCore28AuthorizedReportingQuery(function (AuthorizedReportingQueryProbe $probe): void {
        $actor = User::factory()->create();
        $permissions = [
            'viewAny-core28-authorized-reporting-resource' => true,
            'view-core28-authorized-reporting-resource' => true,
            'scope-core28-authorized-reporting-resource' => true,
        ];
        $roleAttributes = [
            'type' => 'Role',
            'title' => 'CORE-28 Reporter',
            'slug' => 'core28-reporter',
            'name' => 'CORE-28 Reporter',
            'description' => 'Reporting authorization research role.',
            'super_admin' => false,
            'permissions' => $permissions,
        ];

        if (config('aura.teams')) {
            $team = Team::factory()->createQuietly(['user_id' => $actor->getKey()]);
            $actor->forceFill(['current_team_id' => $team->getKey()])->save();
            User::clearCurrentTeamCache($actor->getKey(), $actor->getConnection());
            $role = Role::createForTeamForSystem($team->getKey(), $roleAttributes);
            $actor->roles()->attach($role->getKey(), ['team_id' => $team->getKey()]);
        } else {
            $role = Role::create($roleAttributes);
            $actor->roles()->attach($role->getKey());
        }

        $other = User::factory()->create(config('aura.teams')
            ? ['current_team_id' => $actor->currentTeamIdForAuthorization()]
            : []);

        auth()->login($actor->refresh());
        ScopedScope::flushState();

        $visible = Core28AuthorizedReportingResource::createForOwnerForSystem($actor->getKey(), [
            'amount' => '10.000000',
            'category' => 'alpha',
            'flag' => true,
            'stage' => 'open',
            'visible' => true,
        ]);
        $beta = Core28AuthorizedReportingResource::createForOwnerForSystem($actor->getKey(), [
            'amount' => '5.000000',
            'category' => 'beta',
            'flag' => false,
            'stage' => 'closed',
            'visible' => true,
        ]);
        $empty = Core28AuthorizedReportingResource::createForOwnerForSystem($actor->getKey(), [
            'amount' => '3.000000',
            'category' => null,
            'flag' => false,
            'stage' => 'open',
            'visible' => true,
        ]);
        Core28AuthorizedReportingResource::createForOwnerForSystem($actor->getKey(), [
            'amount' => '20.000000',
            'category' => 'hidden',
            'flag' => true,
            'stage' => 'open',
            'visible' => false,
        ]);
        Core28AuthorizedReportingResource::createForOwnerForSystem($other->getKey(), [
            'amount' => '30.000000',
            'category' => 'foreign-owner',
            'flag' => true,
            'stage' => 'open',
            'visible' => true,
        ]);
        $deleted = Core28AuthorizedReportingResource::createForOwnerForSystem($actor->getKey(), [
            'amount' => '40.000000',
            'category' => 'deleted',
            'flag' => true,
            'stage' => 'open',
            'visible' => true,
        ]);
        $deleted->delete();

        if (config('aura.teams')) {
            $foreignTeam = Team::factory()->createQuietly(['user_id' => $other->getKey()]);
            Core28AuthorizedReportingResource::createForTeamForSystem($foreignTeam->getKey(), [
                'user_id' => $actor->getKey(),
                'amount' => '50.000000',
                'category' => 'foreign-team',
                'flag' => true,
                'stage' => 'open',
                'visible' => true,
            ]);
        }

        $prototype = new Core28AuthorizedReportingResource;
        app(Aura::class)->registerResources([Core28AuthorizedReportingResource::class]);
        $query = $probe->query($prototype);
        $engine = new ResourceAggregateEngine;
        $authorizedCount = $engine->run(new AggregateDefinition(
            Core28AuthorizedReportingResource::class,
            AggregateOperation::Count,
        ));
        $authorizedGroups = $engine->run(new AggregateDefinition(
            Core28AuthorizedReportingResource::class,
            AggregateOperation::Count,
            groupBy: 'category',
        ));

        expect($query->getModel()->getConnectionName())->toBe($prototype->getConnectionName())
            ->and($authorizedCount->value)->toBe(3)
            ->and(array_map(static fn ($point): ?string => $point->key, $authorizedGroups->points))->toBe(['alpha', 'beta', null])
            ->and($query->pluck('id')->all())->toBe([$visible->getKey(), $beta->getKey(), $empty->getKey()])
            ->and($probe->query($prototype)->count())->toBe(3)
            ->and($probe->groupedCount($prototype, 'category'))->toEqual([
                new ReportingGroupPoint('alpha', 'alpha', 1, 1),
                new ReportingGroupPoint('beta', 'beta', 1, 1),
                new ReportingGroupPoint(null, 'Empty', 1, 1),
            ])
            ->and($probe->groupedCount($prototype, 'amount'))->toEqual([
                new ReportingGroupPoint('3.000000', '3.000000', 1, 1),
                new ReportingGroupPoint('5.000000', '5.000000', 1, 1),
                new ReportingGroupPoint('10.000000', '10.000000', 1, 1),
            ])
            ->and($probe->groupedCount($prototype, 'flag'))->toEqual([
                new ReportingGroupPoint('0', '0', 2, 2),
                new ReportingGroupPoint('1', '1', 1, 1),
            ])
            ->and($probe->groupedCount($prototype, 'stage'))->toEqual([
                new ReportingGroupPoint('closed', 'Closed label', 1, 1),
                new ReportingGroupPoint('open', 'Open label', 2, 2),
            ])
            ->and((new ReflectionClass(ReportingGroupPoint::class))->isReadOnly())->toBeTrue()
            ->and(fn () => $probe->groupedCount($prototype, 'category', 2))
            ->toThrow(RuntimeException::class, 'exceed')
            ->and(fn () => $probe->groupedCount($prototype, 'not-declared'))
            ->toThrow(InvalidArgumentException::class, 'not an eligible physical scalar');

        auth()->logout();

        expect(fn () => $probe->query($prototype))->toThrow(AuthorizationException::class)
            ->and(fn () => $engine->run(new AggregateDefinition(Core28AuthorizedReportingResource::class, AggregateOperation::Count)))
            ->toThrow(AuthorizationException::class);
    });
})->group('reporting-research', 'authorization');

test('reporting resolves policy scopes schema and data on the resource alternate connection', function (): void {
    $connectionName = 'core28_reporting_alternate';
    config()->set("database.connections.{$connectionName}", [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);
    DB::purge($connectionName);
    $connection = DB::connection($connectionName);
    $schema = $connection->getSchemaBuilder();

    $schema->create('users', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('current_team_id')->nullable();
        $table->boolean('global_admin')->default(false);
    });
    $schema->create('roles', function (Blueprint $table): void {
        $table->id();
        $table->string('slug');
        $table->json('permissions');
        $table->boolean('super_admin')->default(false);

        if (config('aura.teams')) {
            $table->unsignedBigInteger('team_id')->nullable();
        }
    });
    $schema->create('user_role', function (Blueprint $table): void {
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('role_id');

        if (config('aura.teams')) {
            $table->unsignedBigInteger('team_id')->nullable();
        }
    });
    $schema->create('meta', function (Blueprint $table): void {
        $table->id();
        $table->string('metable_type');
        $table->unsignedBigInteger('metable_id');
        $table->string('key');
        $table->longText('value')->nullable();
        $table->timestamps();
    });
    $schema->create('core28_authorized_reporting_resources', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('team_id')->nullable();
        $table->decimal('amount', 18, 6)->nullable();
        $table->string('category')->nullable();
        $table->boolean('flag');
        $table->string('stage')->nullable();
        $table->boolean('visible');
        $table->timestamps();
        $table->softDeletes();
    });

    try {
        $teamId = config('aura.teams') ? 77 : null;
        $connection->table('users')->insert([
            'id' => 700,
            'current_team_id' => $teamId,
            'global_admin' => false,
        ]);
        $role = [
            'id' => 900,
            'slug' => 'core28-alternate-reporter',
            'permissions' => json_encode([
                'viewAny-core28-authorized-reporting-resource' => true,
                'view-core28-authorized-reporting-resource' => true,
                'scope-core28-authorized-reporting-resource' => true,
            ], JSON_THROW_ON_ERROR),
            'super_admin' => false,
        ];

        if (config('aura.teams')) {
            $role['team_id'] = $teamId;
        }

        $connection->table('roles')->insert($role);
        $membership = ['user_id' => 700, 'role_id' => 900];

        if (config('aura.teams')) {
            $membership['team_id'] = $teamId;
        }

        $connection->table('user_role')->insert($membership);
        $timestamp = now()->format('Y-m-d H:i:s');
        $connection->table('core28_authorized_reporting_resources')->insert([
            [
                'id' => 1100,
                'user_id' => 700,
                'team_id' => $teamId,
                'amount' => '12.000000',
                'category' => 'alternate-visible',
                'flag' => true,
                'stage' => 'open',
                'visible' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'deleted_at' => null,
            ],
            [
                'id' => 1101,
                'user_id' => 701,
                'team_id' => $teamId,
                'amount' => '13.000000',
                'category' => 'alternate-foreign-owner',
                'flag' => true,
                'stage' => 'open',
                'visible' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'deleted_at' => null,
            ],
            [
                'id' => 1102,
                'user_id' => 700,
                'team_id' => config('aura.teams') ? 78 : null,
                'amount' => '14.000000',
                'category' => 'alternate-foreign-team',
                'flag' => true,
                'stage' => 'open',
                'visible' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'deleted_at' => null,
            ],
        ]);

        $actor = User::on($connectionName)->withoutGlobalScopes()->findOrFail(700);
        auth()->setUser($actor);
        User::clearCurrentTeamCache($actor->getKey(), $connection);
        ScopedScope::flushState();
        $prototype = (new Core28AuthorizedReportingResource)->setConnection($connectionName);
        $query = (new AuthorizedReportingQueryProbe)->query($prototype);

        expect(Schema::connection('testing')->hasTable('core28_authorized_reporting_resources'))->toBeFalse()
            ->and($schema->hasTable('core28_authorized_reporting_resources'))->toBeTrue()
            ->and($query->getConnection())->toBe($connection)
            ->and($query->getModel()->getConnectionName())->toBe($connectionName)
            ->and($query->pluck('id')->all())->toBe(config('aura.teams') ? [1100] : [1100, 1102]);
    } finally {
        auth()->logout();
        DB::purge($connectionName);
    }
})->group('reporting-research', 'authorization', 'alternate-connection');
