<?php

use Aura\Base\Exceptions\InvalidFieldValue;
use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Tag;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    $model = new TableFilterModel;

    Aura::fake();
    Aura::setModel($model);
});

// Create Resource for this test
class TableFilterModel extends Resource
{
    public static $singularName = 'Post';

    public static ?string $slug = 'resource';

    public static string $type = 'Post';

    public static function getFields()
    {
        return [
            [
                'name' => 'Meta',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'meta',
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Other Tags',
                'slug' => 'other_tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Number',
                'slug' => 'number',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'required',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Configured decimal',
                'slug' => 'configured_decimal',
                'type' => 'Aura\\Base\\Fields\\Number',
                'number_type' => 'decimal',
                'precision' => 4,
                'scale' => 2,
                'on_index' => true,
            ],
            [
                'name' => 'Configured integer',
                'slug' => 'configured_integer',
                'type' => 'Aura\\Base\\Fields\\Number',
                'number_type' => 'integer',
                'precision' => 3,
                'on_index' => true,
            ],
        ];
    }
}

describe('filter initialization', function () {
    test('table initializes with empty custom filter array', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        expect($post->isMetaField('meta'))->toBeTrue();

        $component = livewire(Table::class, ['query' => null, 'model' => $post])
            ->assertSet('settings.default_view', $post->defaultTableView())
            ->assertSet('perPage', $post->defaultPerPage())
            ->assertSet('columns', $post->getDefaultColumns());

        expect($component->filters)
            ->toBeArray()
            ->toHaveCount(1)
            ->toHaveKey('custom');
    });
});

describe('contains operator', function () {
    beforeEach(function () {
        $this->post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $this->post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);
    });

    test('filter by meta field with contains operator', function () {
        $component = livewire(Table::class, ['query' => null, 'model' => $this->post]);

        $component->call('addFilterGroup');

        expect($component->filters['custom'])->toHaveCount(1);
        expect($component->filters['custom'][0]['filters'])->toHaveCount(1);
        expect($component->filters['custom'][0]['filters'][0])
            ->toHaveKeys(['name', 'value', 'operator'])
            ->and($component->filters['custom'][0]['filters'][0]['name'])->toBe('meta')
            ->and($component->filters['custom'][0]['filters'][0]['operator'])->toBe('contains');

        // Filter for 'A' - should show post2
        $component->set('filters.custom.0.filters.0.value', 'A');
        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe('A');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->post2->id);

        // Filter for 'B' - should show post1
        $component->set('filters.custom.0.filters.0.value', 'B');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->post->id);

        // Filter for 'C' - should show no results
        $component->set('filters.custom.0.filters.0.value', 'C');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });

    test('filter by tags with contains operator', function () {
        $post2OtherTags = TableFilterModel::create([
            'title' => 'Test Post 3',
            'content' => 'Test Content C',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'C',
            'other_tags' => [2],
        ]);

        $tags = Tag::get();

        $component = livewire(Table::class, ['query' => null, 'model' => $this->post]);

        $component->call('addFilterGroup');

        // Set filter for tags
        $component->set('filters.custom.0.filters.0.name', 'tags');
        $component->set('filters.custom.0.filters.0.value', $tags->first()->id);

        expect($component->filters['custom'][0]['filters'][0]['name'])->toBe('tags');
        expect($component->filters['custom'][0]['filters'][0]['operator'])->toBe('contains');
        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe($tags->first()->id);

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->post->id);

        // Change to last tag
        $component->set('filters.custom.0.filters.0.value', $tags->last()->id);
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $this->post2->id);

        // Create and filter by non-existent tag
        $tag7 = Tag::create(['title' => 'Tag 7']);
        $component->set('filters.custom.0.filters.0.value', $tag7->id);
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);

        // Clear filter - should show all posts
        $component->set('filters.custom.0.filters.0.value', null);
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 3);

        // Test other_tags filter - lookup the second tag (ID=2) which is assigned to post2OtherTags
        $component->call('addFilterGroup');
        $component->set('filters.custom.1.filters.0.name', 'other_tags');
        $component->set('filters.custom.1.filters.0.value', 2);

        expect($component->filters['custom'][1]['filters'][0]['name'])->toBe('other_tags');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2OtherTags->id);
    });
});

describe('does_not_contain operator', function () {
    test('filter by meta field with does_not_contain operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        expect($component->filters['custom'])->toHaveCount(1);
        expect($component->filters['custom'][0]['filters'])->toHaveCount(1);
        expect($component->filters['custom'][0]['filters'][0])
            ->toHaveKeys(['name', 'value', 'operator'])
            ->and($component->filters['custom'][0]['filters'][0]['name'])->toBe('meta');

        $component->set('filters.custom.0.filters.0.operator', 'does_not_contain');
        expect($component->filters['custom'][0]['filters'][0]['operator'])->toBe('does_not_contain');

        // Exclude 'A' - should show post1
        $component->set('filters.custom.0.filters.0.value', 'A');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);

        // Exclude 'B' - should show post2
        $component->set('filters.custom.0.filters.0.value', 'B');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        // Exclude 'C' - should show both
        $component->set('filters.custom.0.filters.0.value', 'C');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 2);
    });
});

describe('starts_with operator', function () {
    test('filter by meta field with starts_with operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B amazing',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A custom meta',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        expect($component->filters['custom'])->toHaveCount(1);
        expect($component->filters['custom'][0]['filters'])->toHaveCount(1);
        expect($component->filters['custom'][0]['filters'][0])
            ->toHaveKeys(['name', 'value', 'operator'])
            ->and($component->filters['custom'][0]['filters'][0]['name'])->toBe('meta')
            ->and($component->filters['custom'][0]['filters'][0]['operator'])->toBe('contains');

        $component->set('filters.custom.0.filters.0.operator', 'starts_with');
        $component->set('filters.custom.0.filters.0.value', 'A');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe('A');

        // Should find post2 (starts with 'A')
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        // Filter for 'B' - should find post1
        $component->set('filters.custom.0.filters.0.value', 'B');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);

        // Filter for 'C' - no matches
        $component->set('filters.custom.0.filters.0.value', 'C');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });
});

describe('ends_with operator', function () {
    test('filter by meta field with ends_with operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B amazing',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A custom meta',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.operator', 'ends_with');
        $component->set('filters.custom.0.filters.0.value', 'meta');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe('meta');

        // Should find post2 (ends with 'meta')
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        // Filter for 'amazing' - should find post1
        $component->set('filters.custom.0.filters.0.value', 'amazing');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);

        // Filter for 'C' - no matches
        $component->set('filters.custom.0.filters.0.value', 'C');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });
});

describe('is operator', function () {
    test('filter by meta field with is (exact match) operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B amazing',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A custom meta',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.operator', 'is');
        $component->set('filters.custom.0.filters.0.value', 'A custom meta');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe('A custom meta');

        // Exact match for post2
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        // Exact match for post1
        $component->set('filters.custom.0.filters.0.value', 'B amazing');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);

        // Partial match should not work
        $component->set('filters.custom.0.filters.0.value', 'A custom');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });
});

describe('comparison operators', function () {
    test('configured decimal filters compare the hydrated rounded values and exclude overflow rows', function () {
        $rawValues = [
            'canonical' => '1.23',
            'rounded equivalent' => '1.234',
            'rounded higher' => '1.235',
            'overflow' => '100',
        ];
        $posts = collect($rawValues)->mapWithKeys(function (string $rawValue, string $title): array {
            $post = TableFilterModel::create([
                'title' => $title,
                'content' => 'Configured decimal comparison',
                'type' => 'Post',
                'status' => 'publish',
                'configured_decimal' => '0',
            ]);
            DB::table('meta')
                ->where('metable_id', $post->id)
                ->where('metable_type', $post->getMorphClass())
                ->where('key', 'configured_decimal')
                ->update(['value' => $rawValue]);

            return [$title => $post];
        });

        expect($posts['rounded equivalent']->refresh()->configured_decimal)->toBe('1.23')
            ->and($posts['rounded higher']->refresh()->configured_decimal)->toBe('1.24')
            ->and($posts['overflow']->refresh()->configured_decimal)->toBe('100');

        $component = livewire(Table::class, ['query' => null, 'model' => $posts->first()]);
        $component->call('addFilterGroup');
        $component->set('filters.custom.0.filters.0.name', 'configured_decimal');
        $component->set('filters.custom.0.filters.0.operator', 'equals');
        $component->set('filters.custom.0.filters.0.value', '1.23');
        $component->assertViewHas('rows', fn ($rows): bool => collect($rows->items())->pluck('id')->sort()->values()->all() === [
            $posts['canonical']->id,
            $posts['rounded equivalent']->id,
        ]);

        $component->set('filters.custom.0.filters.0.operator', 'greater_than');
        $component->assertViewHas('rows', fn ($rows): bool => collect($rows->items())->pluck('id')->all() === [
            $posts['rounded higher']->id,
        ]);
    });

    test('configured integer filters exclude fractional and precision overflow legacy rows', function () {
        $rawValues = [
            'two' => '2',
            'padded two' => '+002',
            'fraction' => '1.5',
            'overflow' => '1000',
        ];
        $posts = collect($rawValues)->mapWithKeys(function (string $rawValue, string $title): array {
            $post = TableFilterModel::create([
                'title' => $title,
                'content' => 'Configured integer comparison',
                'type' => 'Post',
                'status' => 'publish',
                'configured_integer' => 0,
            ]);
            DB::table('meta')
                ->where('metable_id', $post->id)
                ->where('metable_type', $post->getMorphClass())
                ->where('key', 'configured_integer')
                ->update(['value' => $rawValue]);

            return [$title => $post];
        });

        expect($posts['padded two']->refresh()->configured_integer)->toBe(2)
            ->and($posts['fraction']->refresh()->configured_integer)->toBe('1.5')
            ->and($posts['overflow']->refresh()->configured_integer)->toBe('1000');

        $component = livewire(Table::class, ['query' => null, 'model' => $posts->first()]);
        $component->call('addFilterGroup');
        $component->set('filters.custom.0.filters.0.name', 'configured_integer');
        $component->set('filters.custom.0.filters.0.operator', 'greater_than');
        $component->set('filters.custom.0.filters.0.value', '1');
        $component->assertViewHas('rows', fn ($rows): bool => collect($rows->items())->pluck('id')->sort()->values()->all() === collect([
            $posts['two']->id,
            $posts['padded two']->id,
        ])->sort()->values()->all());
    });

    test('numeric filters accept zero for every comparison operator and exclude invalid legacy values', function (string $operator, int|string $value, array $expectedNumbers) {
        $posts = collect([-1, 0, 1])->mapWithKeys(function (int $number): array {
            $post = TableFilterModel::create([
                'title' => "Number {$number}",
                'content' => 'Numeric comparison',
                'type' => 'Post',
                'status' => 'publish',
                'meta' => (string) $number,
                'number' => $number,
            ]);

            return [$number => $post];
        });
        $invalid = TableFilterModel::create([
            'title' => 'Invalid legacy number',
            'content' => 'Numeric comparison',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'invalid',
            'number' => 2,
        ]);
        DB::table('meta')
            ->where('metable_id', $invalid->id)
            ->where('metable_type', $invalid->getMorphClass())
            ->where('key', 'number')
            ->update(['value' => 'not-a-number']);

        $component = livewire(Table::class, ['query' => null, 'model' => $posts->first()]);
        $component->call('addFilterGroup');
        $component->set('filters.custom.0.filters.0.name', 'number');
        $component->set('filters.custom.0.filters.0.operator', $operator);
        $component->set('filters.custom.0.filters.0.value', $value);

        $component->assertViewHas('rows', function ($rows) use ($expectedNumbers, $posts): bool {
            $expectedIds = collect($expectedNumbers)->map(fn (int $number) => $posts[$number]->id)->sort()->values()->all();
            $actualIds = collect($rows->items())->pluck('id')->sort()->values()->all();

            return $actualIds === $expectedIds;
        });
    })->with([
        'equals integer zero' => ['equals', 0, [0]],
        'not equals string zero' => ['not_equals', '0', [-1, 1]],
        'greater than string zero' => ['greater_than', '0', [1]],
        'less than integer zero' => ['less_than', 0, [-1]],
        'greater than or equal string zero' => ['greater_than_or_equal', '0', [0, 1]],
        'less than or equal integer zero' => ['less_than_or_equal', 0, [-1, 0]],
    ]);

    test('numeric filters reject genuinely empty and malformed values', function () {
        $post = TableFilterModel::create([
            'title' => 'Number zero',
            'content' => 'Numeric validation',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'zero',
            'number' => 0,
        ]);
        $component = livewire(Table::class, ['query' => null, 'model' => $post]);
        $component->call('addFilterGroup');
        $component->set('filters.custom.0.filters.0.name', 'number');
        $component->set('filters.custom.0.filters.0.operator', 'equals');

        foreach ([null, '', '   '] as $empty) {
            $component->set('filters.custom.0.filters.0.value', $empty)
                ->assertViewHas('rows', fn ($rows): bool => count($rows->items()) === 1);
        }

        expect(fn () => $component->set('filters.custom.0.filters.0.value', 'not-a-number'))
            ->toThrow(InvalidFieldValue::class);
    });

    test('filter by meta field with greater_than operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '100',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '200',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.operator', 'greater_than');
        $component->set('filters.custom.0.filters.0.value', '150');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe('150');

        // Only post2 has value > 150
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        // No results > 200
        $component->set('filters.custom.0.filters.0.value', '200');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });

    test('filter by meta field with less_than operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '100',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '200',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.operator', 'less_than');
        $component->set('filters.custom.0.filters.0.value', '150');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBe('150');

        // Only post1 has value < 150
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post->id);

        // No results < 100
        $component->set('filters.custom.0.filters.0.value', '100');
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 0);
    });
});

describe('empty operators', function () {
    test('filter by number field with is_empty operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '200',
            'number' => 100,
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.operator', 'is_empty');
        $component->set('filters.custom.0.filters.0.name', 'number');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBeNull();
    });

    test('filter by meta field with is_not_empty operator', function () {
        $post = TableFilterModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '',
            'tags' => ['Tag 1', 'Tag 2', 'Tag 3'],
        ]);

        $post2 = TableFilterModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => '200',
            'tags' => ['Tag 3', 'Tag 4', 'Tag 5'],
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('addFilterGroup');

        $component->set('filters.custom.0.filters.0.operator', 'is_not_empty');
        $component->set('filters.custom.0.filters.0.name', 'meta');

        expect($component->filters['custom'][0]['filters'][0]['value'])->toBeNull();
        expect($component->filters['custom'][0]['filters'][0]['name'])->toBe('meta');

        // Only post2 has non-empty meta
        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1 && $rows->items()[0]->id === $post2->id);

        // Switch to is_empty - should find post1
        $component->set('filters.custom.0.filters.0.operator', 'is_empty');
        $component->set('filters.custom.0.filters.0.name', 'meta');

        $component->assertViewHas('rows', fn ($rows) => count($rows->items()) === 1);
    });
});
