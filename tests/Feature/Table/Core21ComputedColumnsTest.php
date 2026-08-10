<?php

use Aura\Base\Contracts\DeclaresComputedTableColumns;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Table\ComputedTableColumn;
use Aura\Base\Table\TableQueryState;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new Core21ComputedResource);
});

class Core21ComputedResource extends Resource implements DeclaresComputedTableColumns
{
    public static ?string $slug = 'core-21-computed';

    public static string $type = 'Core21Computed';

    public function computedTableColumns(): array
    {
        return [
            ComputedTableColumn::make(
                key: 'plain_summary',
                label: 'Plain summary',
                render: static fn (self $record): string => '<script>'.$record->content.'</script>',
                export: static fn (self $record): string => 'export:'.$record->content,
            ),
            ComputedTableColumn::make(
                key: 'trusted_summary',
                label: 'Trusted summary',
                render: static fn (self $record): HtmlString => new HtmlString('<strong>'.e($record->content).'</strong>'),
                export: static fn (self $record): string => strip_tags($record->content),
            ),
            ComputedTableColumn::make(
                key: 'title_length',
                label: 'Title length',
                render: static fn (self $record): int => strlen($record->title),
                export: static fn (self $record): int => strlen($record->title),
                applySort: static function (Builder $query, Resource $resource, string $direction): void {
                    $query->orderByRaw('LENGTH('.$query->getQuery()->getGrammar()->wrap($resource->qualifyColumn('title')).') '.$direction)
                        ->orderBy($resource->getQualifiedKeyName());
                },
                operators: ['greater_than'],
                validateFilter: static fn (array $filter): bool => is_int($filter['value'] ?? null),
                applyFilter: static function (Builder $query, Resource $resource, array $filter): void {
                    $query->whereRaw(
                        'LENGTH('.$query->getQuery()->getGrammar()->wrap($resource->qualifyColumn('title')).') > ?',
                        [$filter['value']],
                    );
                },
            ),
        ];
    }

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['name' => 'Content', 'slug' => 'content', 'type' => 'Aura\\Base\\Fields\\Text'],
        ];
    }
}

class Core21DuplicateComputedResource extends Core21ComputedResource
{
    public function computedTableColumns(): array
    {
        $column = ComputedTableColumn::make(
            key: 'duplicate',
            label: 'Duplicate',
            render: static fn (): string => 'one',
            export: static fn (): string => 'one',
        );

        return [$column, $column];
    }
}

class Core21CollidingComputedResource extends Core21ComputedResource
{
    public function computedTableColumns(): array
    {
        return [ComputedTableColumn::make(
            key: 'title',
            label: 'Forged title',
            render: static fn (): string => 'forged',
            export: static fn (): string => 'forged',
        )];
    }
}

test('computed columns render safely and use their explicit export callbacks', function () {
    $record = Core21ComputedResource::create([
        'title' => 'Alpha',
        'content' => '<em>unsafe</em>',
        'status' => 'publish',
    ]);

    livewire(Table::class, ['model' => new Core21ComputedResource])
        ->assertSee('Plain summary')
        ->assertSee('Trusted summary')
        ->assertSee('&lt;script&gt;&lt;em&gt;unsafe&lt;/em&gt;&lt;/script&gt;', false)
        ->assertSeeHtml('<strong>&lt;em&gt;unsafe&lt;/em&gt;</strong>')
        ->assertDontSeeHtml('<script>');

    expect($record->exportFieldValue('plain_summary'))->toBe('export:<em>unsafe</em>')
        ->and($record->exportFieldValue('trusted_summary'))->toBe('unsafe');
});

test('computed sort and filter callbacks are applied only through canonical capabilities', function () {
    $short = Core21ComputedResource::create(['title' => 'Two', 'content' => 'short', 'status' => 'publish']);
    $long = Core21ComputedResource::create(['title' => 'Eleven chars', 'content' => 'long', 'status' => 'publish']);

    $state = TableQueryState::fromArray([
        'v' => 1,
        'filters' => [['filters' => [[
            'name' => 'title_length',
            'operator' => 'greater_than',
            'value' => 3,
        ]]]],
        'sorts' => [['key' => 'title_length', 'direction' => 'desc']],
    ]);

    livewire(Table::class, [
        'model' => new Core21ComputedResource,
        'tableState' => $state->toQueryString(),
    ])->assertViewHas('rows', fn ($rows): bool => $rows->pluck('id')->all() === [$long->id]);

    livewire(Table::class, ['model' => new Core21ComputedResource])
        ->call('sortBy', 'plain_summary')
        ->assertStatus(422);

    livewire(Table::class, ['model' => new Core21ComputedResource])
        ->call('sortBy', 'forged_computed_key')
        ->assertStatus(422);

    expect($short->exists)->toBeTrue();
});

test('non sortable computed headers emit no sorting mutation', function () {
    Core21ComputedResource::create(['title' => 'Alpha', 'content' => 'summary', 'status' => 'publish']);

    livewire(Table::class, ['model' => new Core21ComputedResource])
        ->assertDontSeeHtml('wire:click="sortBy(\'plain_summary\')"')
        ->assertSeeHtml('wire:click="sortBy(\'title_length\')"');
});

test('computed declarations reject duplicates and field collisions', function (Resource $resource) {
    expect(fn () => $resource->getTableHeaders())->toThrow(InvalidArgumentException::class);
})->with([
    'duplicate key' => [fn () => new Core21DuplicateComputedResource],
    'field collision' => [fn () => new Core21CollidingComputedResource],
]);
