<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// Simple posts-table resource with one meta-backed Text field.
class AppendConfigPost extends Resource
{
    public static $singularName = 'AppendPost';

    public static ?string $slug = 'append-config-post';

    public static string $type = 'AppendConfigPost';

    public static function getFields()
    {
        return [
            ['name' => 'Headline', 'slug' => 'headline', 'type' => 'Aura\\Base\\Fields\\Text', 'validation' => '', 'conditional_logic' => [], 'on_index' => true],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

afterEach(function () {
    Aura::clear();
});

/*
|--------------------------------------------------------------------------
| Serialization: default (legacy_fields_append = true)
|--------------------------------------------------------------------------
*/

test('by default the fields accessor is appended to serialized output', function () {
    config(['aura.features.legacy_fields_append' => true]);

    Aura::fake();
    Aura::setModel(new AppendConfigPost);

    $post = AppendConfigPost::create(['headline' => 'Hello']);

    expect($post->toArray())->toHaveKey('fields');
    expect($post->toArray()['fields'])->toHaveKey('headline');
});

/*
|--------------------------------------------------------------------------
| Serialization: opt-in (legacy_fields_append = false)
|--------------------------------------------------------------------------
*/

test('with the flag off the fields accessor is not appended', function () {
    config(['aura.features.legacy_fields_append' => false]);

    Aura::fake();
    Aura::setModel(new AppendConfigPost);

    $post = AppendConfigPost::create(['headline' => 'Hello']);

    expect($post->toArray())->not->toHaveKey('fields');
});

test('with the flag off callers can still opt in per model via append(fields)', function () {
    config(['aura.features.legacy_fields_append' => false]);

    Aura::fake();
    Aura::setModel(new AppendConfigPost);

    $post = AppendConfigPost::create(['headline' => 'Hello']);

    $array = $post->append('fields')->toArray();

    expect($array)->toHaveKey('fields');
    expect($array['fields'])->toHaveKey('headline');
});

/*
|--------------------------------------------------------------------------
| Livewire table rendering must work in BOTH modes
|--------------------------------------------------------------------------
*/

test('livewire table renders with the append flag on', function () {
    config(['aura.features.legacy_fields_append' => true]);

    $model = new AppendConfigPost;
    Aura::fake();
    Aura::setModel($model);

    collect(range(1, 3))->each(fn ($i) => AppendConfigPost::create(['headline' => 'Row '.$i]));

    livewire(Table::class, ['query' => null, 'model' => $model])
        ->set('perPage', 100)
        ->call('$refresh')
        ->assertSee('Row 1')
        ->assertSee('Row 3');
});

test('livewire table renders with the append flag off', function () {
    config(['aura.features.legacy_fields_append' => false]);

    $model = new AppendConfigPost;
    Aura::fake();
    Aura::setModel($model);

    collect(range(1, 3))->each(fn ($i) => AppendConfigPost::create(['headline' => 'Row '.$i]));

    livewire(Table::class, ['query' => null, 'model' => $model])
        ->set('perPage', 100)
        ->call('$refresh')
        ->assertSee('Row 1')
        ->assertSee('Row 3');
});
