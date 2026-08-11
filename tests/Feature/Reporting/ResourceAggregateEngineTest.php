<?php

use Aura\Base\Aura;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Text;
use Aura\Base\Reporting\AggregateDefinition;
use Aura\Base\Reporting\AggregateOperation;
use Aura\Base\Reporting\DateRange;
use Aura\Base\Reporting\ResourceAggregateEngine;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

final class Core29AggregateResource extends Resource
{
    public static $customTable = true;

    public static array $physicalFields = ['amount', 'stage', 'visible'];

    public static ?string $slug = 'core29-aggregate-resource';

    public static string $type = 'Core29AggregateResource';

    public static bool $usesMeta = false;

    protected $fillable = ['amount', 'stage', 'visible'];

    protected $table = 'core29_aggregate_resources';

    public static function getFields(): array
    {
        return [
            ['name' => 'Amount', 'slug' => 'amount', 'type' => Number::class, 'number_type' => 'decimal', 'precision' => 18, 'scale' => 6],
            ['name' => 'Stage', 'slug' => 'stage', 'type' => Select::class, 'options' => [['key' => 'open', 'value' => 'Open'], ['key' => 'won', 'value' => 'Won']]],
            ['name' => 'Visible', 'slug' => 'visible', 'type' => Boolean::class],
            ['name' => 'Ignored', 'slug' => 'ignored', 'type' => Text::class],
        ];
    }

    /** @param Builder<Core29AggregateResource> $query */
    public function indexQuery(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('visible'), true);
    }
}

beforeEach(function (): void {
    Schema::create('core29_aggregate_resources', function (Blueprint $table): void {
        $table->id();
        $table->decimal('amount', 18, 6)->nullable();
        $table->string('stage')->nullable();
        $table->boolean('visible');
        $table->timestamps();
    });
    app(Aura::class)->registerResources([Core29AggregateResource::class]);
    Gate::before(static fn (): bool => true);
});

afterEach(function (): void {
    Schema::dropIfExists('core29_aggregate_resources');
});

test('aggregates exact physical values through the authorized resource query', function (): void {
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '1.250000', 'stage' => 'open', 'visible' => true]);
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '2.500000', 'stage' => 'won', 'visible' => true]);
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '99.000000', 'stage' => 'won', 'visible' => false]);

    $result = (new ResourceAggregateEngine)->run(new AggregateDefinition(
        Core29AggregateResource::class,
        AggregateOperation::Average,
        'amount',
    ));

    expect($result->value)->toBe('1.875000');
});

test('returns immutable null-last formatted group points and rejects unregistered resources', function (): void {
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '2.000000', 'stage' => 'won', 'visible' => true]);
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '1.000000', 'stage' => null, 'visible' => true]);
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '1.000000', 'stage' => 'open', 'visible' => true]);

    $result = (new ResourceAggregateEngine)->run(new AggregateDefinition(
        Core29AggregateResource::class,
        AggregateOperation::Sum,
        'amount',
        'stage',
    ));

    expect($result->points)->toHaveCount(3)
        ->and($result->points[0]->key)->toBe('open')
        ->and($result->points[0]->label)->toBe('Open')
        ->and($result->points[2]->key)->toBeNull()
        ->and($result->points[2]->label)->toBe('Empty')
        ->and(fn () => $result->points[0]->label = 'nope')->toThrow(Error::class);
});

test('uses half-open ranges', function (): void {
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '1.000000', 'stage' => 'open', 'visible' => true, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00']);
    Core29AggregateResource::withoutGlobalScopes()->create(['amount' => '2.000000', 'stage' => 'open', 'visible' => true, 'created_at' => '2026-01-02 00:00:00', 'updated_at' => '2026-01-02 00:00:00']);

    $result = (new ResourceAggregateEngine)->run(new AggregateDefinition(
        Core29AggregateResource::class,
        AggregateOperation::Count,
        range: new DateRange(new DateTimeImmutable('2026-01-01 00:00:00 UTC'), new DateTimeImmutable('2026-01-02 00:00:00 UTC')),
    ));

    expect($result->value)->toBe(1);
});
