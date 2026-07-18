<?php

use Aura\Base\Resource;

/**
 * Characterization tests for isMetaField() / isTableField() across the four
 * storage combinations (design §1 ResourceMeta, §5 step 10). Pins which slugs
 * resolve to the meta table vs. the model table for each combo.
 *
 * isMetaField()/isTableField() inspect only $baseFillable, inputFieldsSlugs(),
 * usesMeta() and usesCustomTable() — no query is issued — so the custom-table
 * fixtures do not need their physical table to exist for these assertions.
 * (Self-contained fixtures are used rather than the cross-file
 * ResourceWithCustomTable* classes so the file does not depend on load order.)
 */

// (1) posts table + meta (the default).
class MatrixPostsMetaResource extends Resource
{
    public static ?string $slug = 'matrix-posts-meta';

    public static string $type = 'MatrixPostsMeta';

    public static function getFields()
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text', 'conditional_logic' => []],
            ['name' => 'Meta Text', 'slug' => 'metatext', 'type' => 'Aura\\Base\\Fields\\Text', 'conditional_logic' => []],
        ];
    }
}

// (2) custom table + meta.
class MatrixCustomMetaResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'matrix-custom-meta';

    public static string $type = 'MatrixCustomMeta';

    protected $fillable = ['name'];

    protected $table = 'matrix_custom_meta';

    public static function getFields()
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => 'Aura\\Base\\Fields\\Text', 'conditional_logic' => []],
            ['name' => 'Extra', 'slug' => 'extra', 'type' => 'Aura\\Base\\Fields\\Text', 'conditional_logic' => []],
        ];
    }
}

// (3) custom table WITHOUT meta.
class MatrixCustomNoMetaResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'matrix-custom-nometa';

    public static string $type = 'MatrixCustomNoMeta';

    public static bool $usesMeta = false;

    protected $fillable = ['name'];

    protected $table = 'matrix_custom_nometa';

    public static function getFields()
    {
        return [
            ['name' => 'Name', 'slug' => 'name', 'type' => 'Aura\\Base\\Fields\\Text', 'conditional_logic' => []],
            ['name' => 'Extra', 'slug' => 'extra', 'type' => 'Aura\\Base\\Fields\\Text', 'conditional_logic' => []],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('posts + meta: base-fillable slugs are table fields, other input slugs are meta', function () {
    $model = new MatrixPostsMetaResource;

    // id is never a meta field and never a table field here.
    expect($model->isMetaField('id'))->toBeFalse()
        ->and($model->isTableField('id'))->toBeFalse();

    // 'title' is in the base posts fillable → table field, not meta.
    expect($model->isMetaField('title'))->toBeFalse()
        ->and($model->isTableField('title'))->toBeTrue();

    // 'metatext' is an input field NOT in base fillable → meta field.
    expect($model->isMetaField('metatext'))->toBeTrue()
        ->and($model->isTableField('metatext'))->toBeFalse();

    // Unknown key: neither.
    expect($model->isMetaField('nope'))->toBeFalse()
        ->and($model->isTableField('nope'))->toBeFalse();
});

test('custom table + meta: base-fillable slugs are table fields, other input slugs are meta', function () {
    $model = new MatrixCustomMetaResource;

    expect($model->isMetaField('id'))->toBeFalse();

    // 'name' is fillable → table field.
    expect($model->isMetaField('name'))->toBeFalse()
        ->and($model->isTableField('name'))->toBeTrue();

    // 'extra' is an input field NOT in fillable → meta field (meta is on).
    expect($model->isMetaField('extra'))->toBeTrue()
        ->and($model->isTableField('extra'))->toBeFalse();
});

test('custom table without meta: every input slug is a table field, nothing is meta', function () {
    $model = new MatrixCustomNoMetaResource;

    // usesMeta() is false → isMetaField() is always false.
    expect($model->isMetaField('name'))->toBeFalse()
        ->and($model->isMetaField('extra'))->toBeFalse()
        ->and($model->isMetaField('id'))->toBeFalse();

    // 'name' (fillable) and 'extra' (non-fillable input slug) are BOTH table fields.
    expect($model->isTableField('name'))->toBeTrue()
        ->and($model->isTableField('extra'))->toBeTrue();

    // A key that is neither fillable nor an input slug is not a table field.
    expect($model->isTableField('unknown'))->toBeFalse();
});

test('fields-not-in-fillable resolve by storage mode: meta when meta is on, table column when custom+no-meta', function () {
    // The same non-fillable input slug ('extra') resolves differently depending on
    // the storage mode — this is the load-bearing "fields not in fillable" case.
    expect((new MatrixCustomMetaResource)->isMetaField('extra'))->toBeTrue()
        ->and((new MatrixCustomMetaResource)->isTableField('extra'))->toBeFalse();

    expect((new MatrixCustomNoMetaResource)->isTableField('extra'))->toBeTrue()
        ->and((new MatrixCustomNoMetaResource)->isMetaField('extra'))->toBeFalse();

    expect((new MatrixPostsMetaResource)->isMetaField('metatext'))->toBeTrue();
});
