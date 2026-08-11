<?php

use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Text;
use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Fixtures\Reporting\AuthorizedReportingQueryProbe;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

final class Core28AuthorizedReportingResource extends Resource
{
    use SoftDeletes;

    public static $customTable = true;

    public static array $physicalFields = ['amount', 'category', 'reportable'];

    public static ?string $slug = 'core28-authorized-reporting-resource';

    public static string $type = 'Core28AuthorizedReportingResource';

    public static bool $usesMeta = false;

    protected $fillable = ['amount', 'category', 'reportable'];

    protected $table = 'core28_authorized_reporting_resources';

    public static function getFields(): array
    {
        return [
            ['name' => 'Amount', 'slug' => 'amount', 'type' => Number::class],
            ['name' => 'Category', 'slug' => 'category', 'type' => Text::class],
            ['name' => 'Reportable', 'slug' => 'reportable', 'type' => Boolean::class],
        ];
    }

    /** @param Builder<Core28AuthorizedReportingResource> $query */
    public function indexQuery(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('reportable'), true);
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
        $table->boolean('reportable');
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
            'reportable' => true,
        ]);
        $beta = Core28AuthorizedReportingResource::createForOwnerForSystem($actor->getKey(), [
            'amount' => '5.000000',
            'category' => 'beta',
            'reportable' => true,
        ]);
        $empty = Core28AuthorizedReportingResource::createForOwnerForSystem($actor->getKey(), [
            'amount' => '3.000000',
            'category' => null,
            'reportable' => true,
        ]);
        Core28AuthorizedReportingResource::createForOwnerForSystem($actor->getKey(), [
            'amount' => '20.000000',
            'category' => 'hidden',
            'reportable' => false,
        ]);
        Core28AuthorizedReportingResource::createForOwnerForSystem($other->getKey(), [
            'amount' => '30.000000',
            'category' => 'foreign-owner',
            'reportable' => true,
        ]);
        $deleted = Core28AuthorizedReportingResource::createForOwnerForSystem($actor->getKey(), [
            'amount' => '40.000000',
            'category' => 'deleted',
            'reportable' => true,
        ]);
        $deleted->delete();

        if (config('aura.teams')) {
            $foreignTeam = Team::factory()->createQuietly(['user_id' => $other->getKey()]);
            Core28AuthorizedReportingResource::createForTeamForSystem($foreignTeam->getKey(), [
                'user_id' => $actor->getKey(),
                'amount' => '50.000000',
                'category' => 'foreign-team',
                'reportable' => true,
            ]);
        }

        $prototype = new Core28AuthorizedReportingResource;
        $query = $probe->query($prototype);

        expect($query->getModel()->getConnectionName())->toBe($prototype->getConnectionName())
            ->and($query->pluck('id')->all())->toBe([$visible->getKey(), $beta->getKey(), $empty->getKey()])
            ->and($probe->query($prototype)->count())->toBe(3)
            ->and($probe->groupedCount($prototype, 'category'))->toBe([
                ['key' => 'alpha', 'label' => 'alpha', 'value' => 1, 'row_count' => 1],
                ['key' => 'beta', 'label' => 'beta', 'value' => 1, 'row_count' => 1],
                ['key' => null, 'label' => 'Empty', 'value' => 1, 'row_count' => 1],
            ])
            ->and(fn () => $probe->groupedCount($prototype, 'category', 2))
            ->toThrow(RuntimeException::class, 'exceed')
            ->and(fn () => $probe->groupedCount($prototype, 'not-declared'))
            ->toThrow(InvalidArgumentException::class, 'not an eligible physical scalar');

        auth()->logout();

        expect(fn () => $probe->query($prototype))->toThrow(AuthorizationException::class);
    });
})->group('reporting-research', 'authorization');
