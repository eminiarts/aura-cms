<?php

use Aura\Base\Fields\Field;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * Characterization tests for Resource::__get() / __call() — the "magic" data
 * flow described in the #40 design (§3). These pin the EXACT current resolution
 * order BEFORE any decomposition so the later pure-move extractions have a tight
 * guard. Behaviour is pinned as-is even where it is surprising.
 */

// A relation-type spy field. Because its ->type is 'relation', Field::isRelation()
// returns true, so Resource::__get()/__call() route through getRelation() /
// relationship(). Both are instrumented with static counters so a test can assert
// they were NEVER invoked (the collision case) or invoked exactly once.
class MagicAccessorSpyField extends Field
{
    public static int $getRelationCalls = 0;

    public static mixed $relationReturn = null;

    public static int $relationshipCalls = 0;

    public string $type = 'relation';

    public function getRelation($model, $field)
    {
        static::$getRelationCalls++;

        return static::$relationReturn;
    }

    public function relationship($model, $field)
    {
        static::$relationshipCalls++;

        // Any real Eloquent Relation object works for the __call() assertion.
        return $model->hasMany(get_class($model), 'parent_id');
    }
}

class MagicAccessorResource extends Resource
{
    public static ?string $slug = 'magic-accessor';

    public static string $type = 'MagicAccessorResource';

    // Real accessors that COLLIDE with field slugs below. parent::__get() resolves
    // these before the field/relation resolution ladder is ever reached.
    public function getCollidingAccessorAttribute()
    {
        return 'real-attribute-value';
    }

    public function getEmptyStringValAttribute()
    {
        return '';
    }

    public function getFalseValAttribute()
    {
        return false;
    }

    public static function getFields()
    {
        return [
            ['name' => 'Colliding', 'slug' => 'colliding_accessor', 'type' => MagicAccessorSpyField::class, 'conditional_logic' => []],
            ['name' => 'Zero', 'slug' => 'zero_val', 'type' => MagicAccessorSpyField::class, 'conditional_logic' => []],
            ['name' => 'Empty', 'slug' => 'empty_string_val', 'type' => MagicAccessorSpyField::class, 'conditional_logic' => []],
            ['name' => 'False', 'slug' => 'false_val', 'type' => MagicAccessorSpyField::class, 'conditional_logic' => []],
            ['name' => 'Loaded', 'slug' => 'loaded_rel', 'type' => MagicAccessorSpyField::class, 'conditional_logic' => []],
            ['name' => 'Spy Rel', 'slug' => 'spy_rel', 'type' => MagicAccessorSpyField::class, 'conditional_logic' => []],
            ['name' => 'Plain Text', 'slug' => 'plain_text', 'type' => 'Aura\\Base\\Fields\\Text', 'conditional_logic' => []],
        ];
    }

    public function getZeroValAttribute()
    {
        return 0;
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    MagicAccessorSpyField::$getRelationCalls = 0;
    MagicAccessorSpyField::$relationshipCalls = 0;
    MagicAccessorSpyField::$relationReturn = null;
});

// (a) — THE priority test per the amendments.
test('a real accessor wins over a colliding relation field and getRelation is never invoked', function () {
    $model = new MagicAccessorResource;

    expect($model->colliding_accessor)->toBe('real-attribute-value')
        ->and(MagicAccessorSpyField::$getRelationCalls)->toBe(0);
});

// (a) — loaded-relation variant: a loaded Eloquent relation collides with a
// relation field slug; the loaded relation wins and getRelation stays untouched.
test('a loaded relation wins over a colliding relation field and getRelation is never invoked', function () {
    $model = new MagicAccessorResource;
    $loaded = collect(['loaded-item']);
    $model->setRelation('loaded_rel', $loaded);

    expect($model->loaded_rel)->toBe($loaded)
        ->and(MagicAccessorSpyField::$getRelationCalls)->toBe(0);
});

// (b) — falsy real attributes are returned as-is, not treated as "missing" and
// NOT allowed to fall through to relation/field resolution.
test('falsy real attributes (0, empty string, false) are returned as-is', function () {
    $model = new MagicAccessorResource;

    expect($model->zero_val)->toBe(0)
        ->and($model->empty_string_val)->toBe('')
        ->and($model->false_val)->toBe(false)
        // None of these fell through to the relation resolution ladder.
        ->and(MagicAccessorSpyField::$getRelationCalls)->toBe(0);
});

// (c) — a relation field slug with no parent value returns the relation result.
test('a relation field slug with no parent value returns the getRelation result', function () {
    MagicAccessorSpyField::$relationReturn = collect(['related-a', 'related-b']);

    $model = new MagicAccessorResource;

    expect($model->spy_rel)->toEqual(collect(['related-a', 'related-b']))
        ->and(MagicAccessorSpyField::$getRelationCalls)->toBe(1);
});

// (c) — a falsy (null) relation result yields an EMPTY collection via `?: collect()`.
// Note: only a genuinely falsy return (null/false) triggers the fallback — an empty
// Collection object is truthy in PHP, so it would be returned as-is (not replaced).
test('a null relation result yields an empty collection', function () {
    MagicAccessorSpyField::$relationReturn = null;

    $model = new MagicAccessorResource;

    $result = $model->spy_rel;

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result->isEmpty())->toBeTrue()
        ->and(MagicAccessorSpyField::$getRelationCalls)->toBe(1);
});

// (d) — a plain (non-relation) field slug falls through to the computed fields value.
test('a plain field slug falls through to the computed fields value', function () {
    $model = MagicAccessorResource::create(['type' => 'MagicAccessorResource']);
    $model->meta()->create(['key' => 'plain_text', 'value' => 'plain-value']);

    // Re-hydrate so the fields accessor cache is rebuilt from the stored meta.
    $model = MagicAccessorResource::find($model->id);

    expect($model->plain_text)->toBe('plain-value');
});

// (e) — an unknown key returns null.
test('an unknown key returns null', function () {
    $model = new MagicAccessorResource;

    expect($model->totally_unknown_key)->toBeNull();
});

// (f) — __call with a relation-field slug returns an Eloquent Relation object.
test('__call with a relation field slug returns an Eloquent Relation', function () {
    $model = new MagicAccessorResource;

    $relation = $model->spy_rel();

    expect($relation)->toBeInstanceOf(Relation::class)
        ->and(MagicAccessorSpyField::$relationshipCalls)->toBe(1);
});

// (f) — an unknown method still throws the normal BadMethodCallException.
test('__call with an unknown method throws BadMethodCallException', function () {
    $model = new MagicAccessorResource;

    $model->totallyUnknownMethodXyz();
})->throws(BadMethodCallException::class);
