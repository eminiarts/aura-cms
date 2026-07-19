<?php

use Aura\Base\Resource;
use Aura\Base\Traits\SaveMetaFields;
use Illuminate\Support\Facades\DB;

/**
 * Regression coverage for #37: the save steps (initial post columns, packing
 * field attributes into `fields`, persisting `fields` as meta) used to be
 * three separate `saving` listeners registered by three trait boot methods.
 *
 * Laravel ≥13 invokes trait boot methods in ReflectionClass::getMethods()
 * order, and PHP 8.5 changed that order: a trait method re-imported by a
 * subclass reflects BEFORE inherited methods. A subclass re-`use`ing
 * SaveMetaFields therefore booted the meta consumer before the packer — the
 * consumer saw no `fields` yet, the packer then created it, and the literal
 * `fields` array leaked into the INSERT:
 *
 *   SQLSTATE[HY000]: table posts has no column named fields
 *
 * The pipeline is now ONE saving listener with hard-coded step order
 * (SaveFieldAttributes::bootSaveFieldAttributes), so trait boot order no
 * longer matters. These tests pin that for both the plain and the
 * re-`use`ing subclass — on every PHP version.
 */
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

class PipelinePlainModel extends Resource
{
    public static string $type = 'PipelinePlainModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Note',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'note',
            ],
        ];
    }
}

class PipelineReusesTraitModel extends Resource
{
    // Redundant on purpose: Resource already uses SaveMetaFields. Re-`use`ing
    // it copies the trait's methods into this class, which is exactly the
    // shape that inverted the boot order on PHP 8.5.
    use SaveMetaFields;

    public static string $type = 'PipelineReusesTraitModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Note',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'note',
            ],
        ];
    }
}

test('a plain resource save persists meta and never inserts a fields column', function () {
    $model = new PipelinePlainModel;
    $model->note = 'hello';
    $model->save();

    expect($model->exists)->toBeTrue();

    $meta = DB::table('meta')
        ->where('metable_id', $model->id)
        ->where('key', 'note')
        ->first();

    expect($meta)->not->toBeNull()
        ->and($meta->value)->toBe('hello');
});

test('a subclass re-using SaveMetaFields saves identically to the plain resource', function () {
    $model = new PipelineReusesTraitModel;
    $model->note = 'hello';
    $model->save();

    expect($model->exists)->toBeTrue();

    $meta = DB::table('meta')
        ->where('metable_id', $model->id)
        ->where('key', 'note')
        ->first();

    expect($meta)->not->toBeNull()
        ->and($meta->value)->toBe('hello');

    // Update path stays clean too.
    $model->note = 'changed';
    $model->save();

    expect(DB::table('meta')
        ->where('metable_id', $model->id)
        ->where('key', 'note')
        ->value('value'))->toBe('changed');
});

test('the saving pipeline runs its steps in canonical order regardless of listener registration', function () {
    $model = new PipelineReusesTraitModel;
    $model->note = 'ordered';
    $model->save();

    $row = DB::table('posts')->where('id', $model->id)->first();

    // Initial post columns were applied (step 1)...
    expect($row->type)->toBe('PipelineReusesTraitModel')
        ->and($row->user_id)->toBe($this->user->id);

    // ...and the packed `fields` array was fully consumed (steps 2+3):
    // it must exist neither as a column value nor as a lingering attribute.
    expect(property_exists($row, 'fields'))->toBeFalse()
        ->and($model->getAttributes())->not->toHaveKey('fields')
        ->and($model->getAttributes())->not->toHaveKey('note');
});
