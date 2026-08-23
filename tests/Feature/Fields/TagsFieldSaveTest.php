<?php

namespace Tests\Feature\Fields;

use Aura\Base\Resource;
use Aura\Base\Resources\Tag;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

class TagsSaveModel extends Resource
{
    public static string $type = 'TagsSaveModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }
}

class TagsSaveNoCreateModel extends Resource
{
    public static string $type = 'TagsSaveNoCreateModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'create' => false,
                'validation' => '',
                'conditional_logic' => [],
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }
}

test('numeric string attaches the existing tag instead of creating one', function () {
    $tag = Tag::create(['title' => 'Existing Tag', 'slug' => 'existing-tag']);

    $before = Tag::count();

    $model = TagsSaveModel::create(['tags' => [(string) $tag->id]]);

    expect(Tag::count())->toBe($before)
        ->and($model->refresh()->tags->pluck('id')->all())->toBe([$tag->id]);
});

test('unknown ids are dropped instead of being attached', function () {
    $before = Tag::count();

    $model = TagsSaveModel::create(['tags' => ['999999']]);

    expect(Tag::count())->toBe($before)
        ->and($model->refresh()->tags)->toHaveCount(0);
});

test('a label is dropped when the field disallows creating', function () {
    $before = Tag::count();

    $model = TagsSaveNoCreateModel::create(['tags' => ['Brand New Tag']]);

    expect(Tag::count())->toBe($before)
        ->and($model->refresh()->tags)->toHaveCount(0);
});

test('a label is dropped when the user may not create the target resource', function () {
    $this->actingAs(createAdmin());

    $before = Tag::count();

    $model = TagsSaveModel::create(['tags' => ['Unauthorized Tag']]);

    expect(Tag::count())->toBe($before)
        ->and($model->refresh()->tags)->toHaveCount(0);
});

test('a label creates the tag once and reuses it on the next save', function () {
    $before = Tag::count();

    $first = TagsSaveModel::create(['tags' => ['Reusable Tag']]);

    expect(Tag::count())->toBe($before + 1);

    $second = TagsSaveModel::create(['tags' => ['Reusable Tag']]);

    expect(Tag::count())->toBe($before + 1)
        ->and($second->refresh()->tags->pluck('id')->all())
        ->toBe($first->refresh()->tags->pluck('id')->all());
});

test('an id owned by another team is not attached', function () {
    $tag = Tag::create(['title' => 'Foreign Tag', 'slug' => 'foreign-tag']);

    Tag::withoutGlobalScopes()
        ->whereKey($tag->id)
        ->update(['team_id' => foreignTeam()->id]);

    expect(Tag::whereKey($tag->id)->exists())->toBeFalse();

    $model = TagsSaveModel::create(['tags' => [$tag->id]]);

    expect($model->refresh()->tags)->toHaveCount(0);
})->skip(fn () => ! config('aura.teams'), 'Teams are disabled.');
