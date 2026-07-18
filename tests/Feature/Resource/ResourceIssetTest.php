<?php

use Aura\Base\Fields\Field;
use Aura\Base\Resource;
use Illuminate\Support\Collection;

/**
 * Regression coverage for #64: Resource resolved meta / computed field slugs
 * through __get but never implemented __isset. Because PHP consults __isset
 * before __get for null-coalescing (`??`), isset()/empty(), and Collection
 * pluck() (via data_get()'s `isset($target->{$segment})` guard), every one of
 * those silently reported meta-backed attributes as "unset" — turning
 * `$model->metaField ?? 'x'` into always-'x' and `pluck('metaField')` into
 * all-nulls.
 *
 * These tests pin the NEW behaviour: __isset mirrors __get's resolution ladder
 * exactly (real Eloquent state -> relation field slug -> computed `fields`
 * value), reporting true whenever a non-null value would resolve, while leaving
 * real attributes and relations behaving exactly as Eloquent always did.
 */

// A relation-type field: because ->type is 'relation', Field::isRelation() is
// true, so a matching slug routes through getRelation() in __get/__isset.
class IssetSpyField extends Field
{
    public static mixed $relationReturn = null;

    public string $type = 'relation';

    public function getRelation($model, $field)
    {
        return static::$relationReturn;
    }
}

class IssetTestResource extends Resource
{
    public static ?string $slug = 'isset-test';

    public static string $type = 'IssetTestResource';

    public static function getFields()
    {
        return [
            ['name' => 'Plain Text', 'slug' => 'plain_text', 'type' => 'Aura\\Base\\Fields\\Text', 'conditional_logic' => []],
            ['name' => 'Count', 'slug' => 'count', 'type' => 'Aura\\Base\\Fields\\Number', 'conditional_logic' => []],
            ['name' => 'Spy Rel', 'slug' => 'spy_rel', 'type' => IssetSpyField::class, 'conditional_logic' => []],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    IssetSpyField::$relationReturn = null;
});

/**
 * Persist a meta-backed value and re-hydrate so the fields accessor cache is
 * rebuilt from stored meta — the realistic path a loaded model takes.
 */
function makeIssetResource(array $meta = []): IssetTestResource
{
    $model = IssetTestResource::create(['type' => 'IssetTestResource']);

    foreach ($meta as $key => $value) {
        $model->meta()->create(['key' => $key, 'value' => $value]);
    }

    return IssetTestResource::find($model->id);
}

// --- the core bug: isset() on a meta field --------------------------------

test('isset() is true for a meta field that has a value', function () {
    $model = makeIssetResource(['plain_text' => 'hello']);

    expect(isset($model->plain_text))->toBeTrue();
});

test('isset() is false for a meta field with no value', function () {
    $model = makeIssetResource();

    expect(isset($model->plain_text))->toBeFalse();
});

test('isset() is false for a completely unknown key', function () {
    $model = makeIssetResource(['plain_text' => 'hello']);

    expect(isset($model->totally_unknown_key))->toBeFalse();
});

// --- null-coalescing now sees the real value ------------------------------

test('?? fallback returns the meta value when present (was always the fallback)', function () {
    $model = makeIssetResource(['plain_text' => 'hello']);

    expect($model->plain_text ?? 'default')->toBe('hello');
});

test('?? fallback returns the default only when the meta value is genuinely absent', function () {
    $model = makeIssetResource();

    expect($model->plain_text ?? 'default')->toBe('default');
});

// --- pluck() over a collection of resources -------------------------------

test('pluck() on a collection of resources returns meta values (was all nulls)', function () {
    makeIssetResource(['plain_text' => 'alpha']);
    makeIssetResource(['plain_text' => 'beta']);
    makeIssetResource(['plain_text' => 'gamma']);

    $values = IssetTestResource::all()->pluck('plain_text');

    expect($values->all())->toEqualCanonicalizing(['alpha', 'beta', 'gamma']);
});

// --- empty() semantics -----------------------------------------------------

test('empty() is false for a non-empty meta value and true when absent', function () {
    $withValue = makeIssetResource(['plain_text' => 'hello']);
    $withoutValue = makeIssetResource();

    expect(empty($withValue->plain_text))->toBeFalse()
        ->and(empty($withoutValue->plain_text))->toBeTrue();
});

test('empty() honours falsy meta values (0 is empty even though isset is true)', function () {
    $model = makeIssetResource(['count' => 0]);

    // isset mirrors __get: a stored 0 resolves to a non-null value, so isset is
    // true — but empty() still reports true for the falsy 0, exactly as PHP does
    // for a real property.
    expect(isset($model->count))->toBeTrue()
        ->and(empty($model->count))->toBeTrue();
});

// --- regular Eloquent attributes are untouched -----------------------------

test('regular Eloquent attributes keep native isset/?? behaviour', function () {
    $model = IssetTestResource::create(['type' => 'IssetTestResource', 'title' => 'Real Title', 'parent_id' => null]);

    // A set real column reads through parent::__get exactly as before; a null
    // nullable column stays "unset" and falls through to the ?? default.
    expect(isset($model->title))->toBeTrue()
        ->and($model->title ?? 'default')->toBe('Real Title')
        ->and(isset($model->parent_id))->toBeFalse()
        ->and($model->parent_id ?? 'default')->toBe('default');
});

test('a falsy real attribute is reported as set, matching __get', function () {
    $model = IssetTestResource::create(['type' => 'IssetTestResource', 'order' => 0]);

    expect(isset($model->order))->toBeTrue()
        ->and($model->order)->toBe(0);
});

// --- relations mirror __get (never regress to null) ------------------------

test('a relation field slug is reported as set, mirroring __get', function () {
    IssetSpyField::$relationReturn = collect(['related-a', 'related-b']);

    $model = makeIssetResource();

    // __get returns the relation collection, so isset() must agree.
    expect(isset($model->spy_rel))->toBeTrue()
        ->and($model->spy_rel)->toEqual(collect(['related-a', 'related-b']));
});

test('a relation field slug with a null relation still reads as set (empty collection)', function () {
    IssetSpyField::$relationReturn = null;

    $model = makeIssetResource();

    // __get coerces a null relation to an empty (non-null) collection, so
    // isset() mirrors that and reports true.
    expect(isset($model->spy_rel))->toBeTrue()
        ->and($model->spy_rel)->toBeInstanceOf(Collection::class)
        ->and($model->spy_rel->isEmpty())->toBeTrue();
});

test('a loaded Eloquent relation is reported as set without hitting the field ladder', function () {
    $model = makeIssetResource();
    $loaded = collect(['loaded-item']);
    $model->setRelation('spy_rel', $loaded);

    expect(isset($model->spy_rel))->toBeTrue()
        ->and($model->spy_rel)->toBe($loaded);
});

// --- teams-off compatibility (computed-fields path, no DB/team scope) -------

test('isset resolves the computed fields path with teams disabled', function () {
    config(['aura.teams' => false]);

    // Prime the fields accessor cache directly: this exercises resolveDynamicAttribute
    // step 4 (the computed `fields` value) without any team context or DB access,
    // so it holds identically whether teams are on or off.
    $model = new IssetTestResource;
    $model->fieldsAttributeCache = collect(['plain_text' => 'primed-value']);

    expect(isset($model->plain_text))->toBeTrue()
        ->and($model->plain_text ?? 'default')->toBe('primed-value')
        ->and(isset($model->plain_text_missing))->toBeFalse();
});
