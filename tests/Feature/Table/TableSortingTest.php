<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new Post);
    Aura::setModel(new MetaSortingModel);
});

class MetaSortingModel extends Resource
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
                'name' => 'Number',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'number',
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
        ];
    }
}

describe('default sorting', function () {
    test('table defaults to sorting by id descending', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $post2 = Post::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post])
            ->assertSet('settings.default_view', $post->defaultTableView())
            ->assertSet('perPage', $post->defaultPerPage())
            ->assertSet('columns', $post->getDefaultColumns());

        expect($component->sorts)->toBe([]);

        // Default order: newest first (id desc)
        $component->assertViewHas('rows', fn ($rows) => is_array($rows->items()))
            ->assertViewHas('rows', fn ($rows) => $rows->items()[0]->id === $post2->id)
            ->assertViewHas('rows', fn ($rows) => $rows->items()[1]->id === $post->id);
    });

    test('clicking id column toggles sort direction', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $post2 = Post::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        // First click: sort ascending
        $component->call('sortBy', 'id');
        expect($component->sorts)->toBe(['id' => 'asc']);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[0]->id === $post->id);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[1]->id === $post2->id);

        // Second click: sort descending
        $component->call('sortBy', 'id');
        expect($component->sorts)->toBe(['id' => 'desc']);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[0]->id === $post2->id);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[1]->id === $post->id);
    });

    test('can sort by content column', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $post2 = Post::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('sortBy', 'content');
        expect($component->sorts)->toBe(['content' => 'asc']);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[0]->id === $post->id);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[1]->id === $post2->id);
    });
});

describe('meta field sorting', function () {
    test('can sort by meta text field', function () {
        $post = MetaSortingModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B',
        ]);

        $post2 = MetaSortingModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A',
        ]);

        expect($post->isMetaField('meta'))->toBeTrue();
        expect($post->isTaxonomyField('meta'))->toBeFalse();

        $component = livewire(Table::class, ['query' => null, 'model' => $post])
            ->assertSet('settings.default_view', $post->defaultTableView())
            ->assertSet('perPage', $post->defaultPerPage())
            ->assertSet('columns', $post->getDefaultColumns());

        // Sort ascending: A before B
        $component->call('sortBy', 'meta');
        expect($component->sorts)->toBe(['meta' => 'asc']);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[0]->id === $post2->id && $rows->items()[1]->id === $post->id);

        // Sort descending: B before A
        $component->call('sortBy', 'meta');
        expect($component->sorts)->toBe(['meta' => 'desc']);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[0]->id === $post->id && $rows->items()[1]->id === $post2->id);
    });

    test('can sort by meta number field', function () {
        $post = MetaSortingModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B',
            'number' => 10,
        ]);

        $post2 = MetaSortingModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A',
            'number' => 20,
        ]);

        $post3 = MetaSortingModel::create([
            'title' => 'Test Post 3',
            'content' => 'Test Content C',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'C',
            'number' => 100,
        ]);

        expect($post->isMetaField('number'))->toBeTrue();
        expect($post->isTaxonomyField('number'))->toBeFalse();
        expect($post->isNumberField('number'))->toBeTrue();

        $component = livewire(Table::class, ['query' => null, 'model' => $post])
            ->assertSet('settings.default_view', $post->defaultTableView())
            ->assertSet('perPage', $post->defaultPerPage())
            ->assertSet('columns', $post->getDefaultColumns());

        // Sort ascending: 10, 20, 100
        $component->call('sortBy', 'number');
        expect($component->sorts)->toBe(['number' => 'asc']);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[0]->id === $post->id);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[1]->id === $post2->id);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[2]->id === $post3->id);

        // Sort descending: 100, 20, 10
        $component->call('sortBy', 'number');
        expect($component->sorts)->toBe(['number' => 'desc']);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[0]->id === $post3->id);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[1]->id === $post2->id);
        $component->assertViewHas('rows', fn ($rows) => $rows->items()[2]->id === $post->id);
    });
});

describe('taxonomy field sorting', function () {
    test('can sort by taxonomy/tags field', function () {
        $post = MetaSortingModel::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'B',
            'tags' => [
                'Tag 1', 'Tag 2', 'Tag 3',
            ],
        ]);

        $post2 = MetaSortingModel::create([
            'title' => 'Test Post 2',
            'content' => 'Test Content B',
            'type' => 'Post',
            'status' => 'publish',
            'meta' => 'A',
            'tags' => [
                'Tag 3', 'Tag 4', 'Tag 5',
            ],
        ]);

        expect($post->isTaxonomyField('tags'))->toBeTrue();
        expect($post->isMetaField('tags'))->toBeTrue();

        $component = livewire(Table::class, ['query' => null, 'model' => $post])
            ->assertSet('settings.default_view', $post->defaultTableView())
            ->assertSet('perPage', $post->defaultPerPage())
            ->assertSet('columns', $post->getDefaultColumns());

        // Sort ascending
        $component->call('sortBy', 'tags');
        expect($component->sorts)->toBe(['tags' => 'asc']);

        $query = $component->instance()->rowsQuery();
        $rows = $query->get();
        expect($rows[0]->id)->toBe($post->id);
        expect($rows[1]->id)->toBe($post2->id);

        // Sort descending
        $component->call('sortBy', 'tags');
        expect($component->sorts)->toBe(['tags' => 'desc']);

        $query = $component->instance()->rowsQuery();
        $rows = $query->get();
        expect($rows[0]->id)->toBe($post2->id);
        expect($rows[1]->id)->toBe($post->id);

        // Verify SQL structure
        expect($query->toSql())->toContain('left join "post_relations" as "pr" on "posts"."id" = "pr"."related_id"');
        expect($query->getBindings()[0])->toBe('MetaSortingModel');
    });
});

describe('sorting state persistence', function () {
    test('sorting replaces previous sort when clicking different column', function () {
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test Content A',
            'type' => 'Post',
            'status' => 'publish',
        ]);

        $component = livewire(Table::class, ['query' => null, 'model' => $post]);

        $component->call('sortBy', 'id');
        expect($component->sorts)->toBe(['id' => 'asc']);

        $component->call('sortBy', 'content');
        expect($component->sorts)->toBe(['content' => 'asc']);
        expect($component->sorts)->not->toHaveKey('id');
    });
});
