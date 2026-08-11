<?php

use Aura\Base\Aura;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Text;
use Aura\Base\Reporting\AggregateDefinition;
use Aura\Base\Reporting\AggregateOperation;
use Aura\Base\Reporting\DateBucket;
use Aura\Base\Reporting\DateRange;
use Aura\Base\Reporting\ReportingProjection;
use Aura\Base\Reporting\ResourceAggregateEngine;
use Aura\Base\Resource;
use Aura\Base\Tests\Fixtures\Reporting\ReportingDatabase;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\SkippedWithMessageException;

final class Core29NativeAggregateResource extends Resource
{
    public static $customTable = true;

    public static ?string $ownerColumn = null;

    public static array $physicalFields = ['amount', 'category'];

    public static string $reportingConnection = 'testing';

    public static string $scopeMode = self::SCOPE_GLOBAL;

    public static ?string $slug = 'core29-native-aggregate-resource';

    public static ?string $teamColumn = null;

    public static string $type = 'Core29NativeAggregateResource';

    protected $fillable = ['amount', 'category'];

    protected $table = 'core29_native_aggregate_resources';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection(self::$reportingConnection);
    }

    public static function getFields(): array
    {
        return [
            ['name' => 'Amount', 'slug' => 'amount', 'type' => Number::class, 'number_type' => 'decimal', 'precision' => 18, 'scale' => 6],
            ['name' => 'Projected amount', 'slug' => 'projected_amount', 'type' => Number::class, 'number_type' => 'decimal', 'precision' => 18, 'scale' => 6],
            ['name' => 'Category', 'slug' => 'category', 'type' => Text::class],
        ];
    }
}

/** @return array{0: Connection, 1: string} */
function core29NativeConnection(string $driver): array
{
    $connection = ReportingDatabase::connect($driver);

    if ($connection === null) {
        $prefix = match ($driver) {
            'mysql' => 'MYSQL',
            'mariadb' => 'MARIADB',
            'pgsql' => 'POSTGRES',
            default => null,
        };

        throw new SkippedWithMessageException($prefix === null
            ? 'SQLite is supplied by the package test harness.'
            : "Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} reporting engine contract.");
    }

    return [$connection, (string) $connection->getName()];
}

test('the production aggregate engine is portable across every claimed database', function (string $driver): void {
    $this->actingAs(createSuperAdmin());
    $teamsEnabled = config('aura.teams');
    [$connection, $connectionName] = core29NativeConnection($driver);
    config()->set('aura.teams', false);
    Core29NativeAggregateResource::$reportingConnection = $connectionName;
    $schema = $connection->getSchemaBuilder();

    $schema->dropIfExists(ReportingProjection::VALUES_TABLE);
    $schema->dropIfExists('core29_native_aggregate_resources');
    $schema->create('core29_native_aggregate_resources', function (Blueprint $table) use ($driver): void {
        $table->id();

        if ($driver === 'sqlite') {
            $table->text('amount')->nullable();
        } else {
            $table->decimal('amount', 18, 6)->nullable();
        }

        $table->string('category')->nullable();
        $table->timestamps();
    });
    $schema->create(ReportingProjection::VALUES_TABLE, function (Blueprint $table): void {
        $table->id();
        $table->string('resource_type');
        $table->unsignedBigInteger('resource_id');
        $table->string('field_key');
        $table->bigInteger('value_scaled')->nullable();
        $table->unsignedTinyInteger('contract_version');
        $table->timestamps();
        $table->unique(['resource_type', 'resource_id', 'field_key'], 'core29_native_projection_identity');
    });

    try {
        $connection->table('core29_native_aggregate_resources')->insert([
            ['id' => 1, 'amount' => '1.250000', 'category' => 'alpha', 'created_at' => '2024-03-31 00:30:00', 'updated_at' => '2024-03-31 00:30:00'],
            ['id' => 2, 'amount' => '2.500000', 'category' => 'beta', 'created_at' => '2024-03-31 01:30:00', 'updated_at' => '2024-03-31 01:30:00'],
            ['id' => 3, 'amount' => null, 'category' => null, 'created_at' => '2024-04-01 00:00:00', 'updated_at' => '2024-04-01 00:00:00'],
        ]);
        $timestamp = now();
        $connection->table(ReportingProjection::VALUES_TABLE)->insert([
            ['resource_type' => Core29NativeAggregateResource::class, 'resource_id' => 1, 'field_key' => 'projected_amount', 'value_scaled' => 3_250_000, 'contract_version' => ReportingProjection::CONTRACT_VERSION, 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['resource_type' => Core29NativeAggregateResource::class, 'resource_id' => 2, 'field_key' => 'projected_amount', 'value_scaled' => 4_750_000, 'contract_version' => ReportingProjection::CONTRACT_VERSION, 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ]);
        app(Aura::class)->registerResources([Core29NativeAggregateResource::class]);
        Gate::before(static fn (): bool => true);
        config()->set('aura.reporting.projection.reads_enabled', true);
        $engine = new ResourceAggregateEngine;
        $connection->flushQueryLog();
        $connection->enableQueryLog();
        $physicalSum = $engine->run(new AggregateDefinition(Core29NativeAggregateResource::class, AggregateOperation::Sum, 'amount'))->value;
        $physicalQueryCount = count($connection->getQueryLog());
        $connection->disableQueryLog();

        expect($physicalSum)
            ->toBe('3.750000')
            ->and($physicalQueryCount)->toBe(1)
            ->and($engine->run(new AggregateDefinition(Core29NativeAggregateResource::class, AggregateOperation::Average, 'amount'))->value)
            ->toBe('1.875000')
            ->and($engine->run(new AggregateDefinition(Core29NativeAggregateResource::class, AggregateOperation::Sum, 'projected_amount'))->value)
            ->toBe('8.000000')
            ->and($engine->run(new AggregateDefinition(Core29NativeAggregateResource::class, AggregateOperation::Count, groupBy: 'category'))->points)
            ->toHaveCount(3);

        $buckets = $engine->run(new AggregateDefinition(
            Core29NativeAggregateResource::class,
            AggregateOperation::Count,
            range: new DateRange(new DateTimeImmutable('2024-03-30 Europe/Zurich'), new DateTimeImmutable('2024-04-02 Europe/Zurich')),
            bucket: DateBucket::Day,
            timezone: 'Europe/Zurich',
        ));

        expect(array_column($buckets->points, 'value', 'key'))->toBe(['2024-03-31' => 2, '2024-04-01' => 1]);
    } finally {
        $schema->dropIfExists(ReportingProjection::VALUES_TABLE);
        $schema->dropIfExists('core29_native_aggregate_resources');
        config()->set('aura.teams', $teamsEnabled);
        ReportingDatabase::disconnect($driver);
    }
})->with(['sqlite', 'mysql', 'mariadb', 'pgsql'])->group('database-guards');
