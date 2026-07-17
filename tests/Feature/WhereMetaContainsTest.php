<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new WhereMetaContainsModel);
});

class WhereMetaContainsModel extends Resource
{
    public static ?string $slug = 'where-meta-contains';

    public static string $type = 'WhereMetaContainsModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Related IDs',
                'slug' => 'related_ids',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_forms' => true,
                'on_index' => false,
                'on_view' => true,
            ],
        ];
    }
}

test('whereMetaContains matches string elements in a JSON meta array', function () {
    $match = WhereMetaContainsModel::create(['type' => 'WhereMetaContainsModel']);
    $match->meta()->updateOrCreate(
        ['key' => 'related_ids'],
        ['value' => json_encode(['1', '2'])],
    );

    $other = WhereMetaContainsModel::create(['type' => 'WhereMetaContainsModel']);
    $other->meta()->updateOrCreate(
        ['key' => 'related_ids'],
        ['value' => json_encode(['9', '10'])],
    );

    $results = WhereMetaContainsModel::query()
        ->whereMetaContains('related_ids', 1)
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($match->id);
});

test('whereMetaContains matches integer elements in a JSON meta array', function () {
    $match = WhereMetaContainsModel::create(['type' => 'WhereMetaContainsModel']);
    $match->meta()->updateOrCreate(
        ['key' => 'related_ids'],
        ['value' => json_encode([1, 2])],
    );

    $other = WhereMetaContainsModel::create(['type' => 'WhereMetaContainsModel']);
    $other->meta()->updateOrCreate(
        ['key' => 'related_ids'],
        ['value' => json_encode([9, 10])],
    );

    $results = WhereMetaContainsModel::query()
        ->whereMetaContains('related_ids', '1')
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($match->id);
});

test('whereMetaContains does not match when the value is absent', function () {
    $model = WhereMetaContainsModel::create(['type' => 'WhereMetaContainsModel']);
    $model->meta()->updateOrCreate(
        ['key' => 'related_ids'],
        ['value' => json_encode(['1', '2'])],
    );

    $results = WhereMetaContainsModel::query()
        ->whereMetaContains('related_ids', 99)
        ->get();

    expect($results)->toHaveCount(0);
});
