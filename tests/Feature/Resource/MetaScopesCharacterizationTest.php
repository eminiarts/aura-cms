<?php

use Aura\Base\Resource;

/**
 * Characterization tests for the meta query scopes in AuraModelConfig (design §1
 * QueriesMeta, §5 step 7): whereMeta (2-arg / 3-arg operator / array), orWhereMeta,
 * whereInMeta, whereNotInMeta. Exercised against the real SQLite driver with both
 * matching and non-matching rows. (whereMetaContains is covered separately in
 * tests/Feature/WhereMetaContainsTest.php and is not duplicated here.)
 */
class MetaScopeModel extends Resource
{
    public static ?string $slug = 'meta-scope-model';

    public static string $type = 'MetaScopeModel';

    public static function getFields()
    {
        return [
            ['name' => 'Level', 'slug' => 'level', 'type' => 'Aura\\Base\\Fields\\Text', 'conditional_logic' => []],
            ['name' => 'Score', 'slug' => 'score', 'type' => 'Aura\\Base\\Fields\\Text', 'conditional_logic' => []],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    $this->gold = MetaScopeModel::create(['type' => 'MetaScopeModel']);
    $this->gold->meta()->create(['key' => 'level', 'value' => 'gold']);
    $this->gold->meta()->create(['key' => 'score', 'value' => '10']);

    $this->silver = MetaScopeModel::create(['type' => 'MetaScopeModel']);
    $this->silver->meta()->create(['key' => 'level', 'value' => 'silver']);
    $this->silver->meta()->create(['key' => 'score', 'value' => '20']);

    $this->bronze = MetaScopeModel::create(['type' => 'MetaScopeModel']);
    $this->bronze->meta()->create(['key' => 'level', 'value' => 'bronze']);
    $this->bronze->meta()->create(['key' => 'score', 'value' => '5']);
});

test('whereMeta (2-arg) matches exactly one row by key/value equality', function () {
    $results = MetaScopeModel::query()->whereMeta('level', 'gold')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($this->gold->id);
});

test('whereMeta (2-arg) matches no rows when the value is absent', function () {
    expect(MetaScopeModel::query()->whereMeta('level', 'platinum')->get())->toHaveCount(0);
});

test('whereMeta (3-arg operator) applies the operator to the value', function () {
    $results = MetaScopeModel::query()->whereMeta('level', '!=', 'gold')->get();

    expect($results->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->silver->id, $this->bronze->id])->sort()->values()->all());
});

test('whereMeta (array form) matches rows satisfying all key/value pairs', function () {
    // Single pair.
    expect(MetaScopeModel::query()->whereMeta(['level' => 'gold'])->get())
        ->toHaveCount(1);

    // Multiple pairs are AND-ed together: only the gold row has both.
    $both = MetaScopeModel::query()->whereMeta(['level' => 'gold', 'score' => '10'])->get();
    expect($both)->toHaveCount(1)
        ->and($both->first()->id)->toBe($this->gold->id);

    // Mismatched pairs match nothing.
    expect(MetaScopeModel::query()->whereMeta(['level' => 'gold', 'score' => '20'])->get())
        ->toHaveCount(0);
});

test('orWhereMeta (2-arg) unions the meta condition with the outer query', function () {
    $results = MetaScopeModel::query()
        ->where('id', $this->bronze->id)
        ->orWhereMeta('level', 'gold')
        ->get();

    expect($results->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->gold->id, $this->bronze->id])->sort()->values()->all());
});

test('orWhereMeta (array form) unions the AND-ed pairs with the outer query', function () {
    $results = MetaScopeModel::query()
        ->whereRaw('1 = 0')
        ->orWhereMeta(['level' => 'silver'])
        ->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($this->silver->id);
});

test('whereInMeta matches any row whose meta value is in the set', function () {
    $results = MetaScopeModel::query()->whereInMeta('level', ['gold', 'silver'])->get();

    expect($results->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->gold->id, $this->silver->id])->sort()->values()->all());
});

test('whereInMeta wraps a scalar value into a single-element set', function () {
    $results = MetaScopeModel::query()->whereInMeta('level', 'bronze')->get();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($this->bronze->id);
});

test('whereNotInMeta excludes rows whose meta value is in the set', function () {
    $results = MetaScopeModel::query()->whereNotInMeta('level', ['gold'])->get();

    expect($results->pluck('id')->sort()->values()->all())
        ->toBe(collect([$this->silver->id, $this->bronze->id])->sort()->values()->all());
});
