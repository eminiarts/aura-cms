<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Text;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class FormFieldHydrationResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'form-field-hydration';

    public static string $type = 'FormFieldHydration';

    protected $fillable = [
        'physical_default',
        'physical_empty',
        'physical_false',
        'physical_null',
        'physical_zero',
        'unknown_column',
    ];

    protected $table = 'form_field_hydration_resources';

    public static function getFields(): array
    {
        return [
            ['name' => 'Physical default', 'slug' => 'physical_default', 'type' => Text::class],
            ['name' => 'Physical false', 'slug' => 'physical_false', 'type' => Boolean::class],
            ['name' => 'Physical zero', 'slug' => 'physical_zero', 'type' => Number::class, 'default' => 0],
            ['name' => 'Physical empty', 'slug' => 'physical_empty', 'type' => Text::class, 'default' => ''],
            ['name' => 'Physical null', 'slug' => 'physical_null', 'type' => Text::class, 'default' => null],
            ['name' => 'Meta value', 'slug' => 'meta_value', 'type' => Text::class, 'validation' => 'required'],
            ['name' => 'Meta false', 'slug' => 'meta_false', 'type' => Boolean::class],
            ['name' => 'Meta zero', 'slug' => 'meta_zero', 'type' => Number::class, 'default' => 0],
            ['name' => 'Meta empty', 'slug' => 'meta_empty', 'type' => Text::class, 'default' => ''],
            ['name' => 'Meta null', 'slug' => 'meta_null', 'type' => Text::class, 'default' => null],
            ['name' => 'Read only', 'slug' => 'read_only', 'type' => Text::class, 'default' => 'read only', 'disabled' => true],
            ['name' => 'Hidden', 'slug' => 'hidden', 'type' => Text::class, 'default' => 'hidden', 'on_forms' => false],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Schema::create('form_field_hydration_resources', function (Blueprint $table) {
        $table->id();
        $table->string('physical_default')->default('database default');
        $table->string('physical_empty')->nullable();
        $table->boolean('physical_false')->nullable();
        $table->string('physical_null')->nullable();
        $table->integer('physical_zero')->nullable();
        $table->string('unknown_column')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    Aura::fake();
    Aura::setModel(new FormFieldHydrationResource);
});

afterEach(function () {
    Aura::clear();
});

test('create initializes only meaningful declared field state and preserves database defaults', function () {
    Livewire::test(Create::class, ['slug' => 'form-field-hydration'])
        ->assertSet('form.fields.physical_false', false)
        ->assertSet('form.fields.physical_zero', 0)
        ->assertSet('form.fields.physical_empty', '')
        ->assertSet('form.fields.physical_null', null)
        ->assertSet('form.fields.meta_false', false)
        ->assertSet('form.fields.meta_zero', 0)
        ->assertSet('form.fields.meta_empty', '')
        ->assertSet('form.fields.meta_null', null)
        ->assertSet('form.fields.read_only', 'read only')
        ->tap(function ($component) {
            expect($component->instance()->form['fields'])
                ->not->toHaveKey('physical_default')
                ->not->toHaveKey('meta_value')
                ->not->toHaveKey('hidden');
        })
        ->set('form.fields.meta_value', 'required')
        ->call('save')
        ->assertHasNoErrors();

    expect(FormFieldHydrationResource::query()->sole()->physical_default)->toBe('database default');
});

test('edit hydrates declared physical and meta fields without legacy fields serialization', function () {
    config(['aura.features.legacy_fields_append' => false]);

    $resource = FormFieldHydrationResource::create([
        'physical_default' => 'physical value',
        'physical_empty' => '',
        'physical_false' => false,
        'physical_null' => null,
        'physical_zero' => 0,
        'unknown_column' => 'must not hydrate',
        'meta_value' => 'meta value',
        'meta_false' => false,
        'meta_zero' => 0,
        'meta_empty' => '',
        'meta_null' => null,
        'read_only' => 'stored read only',
        'hidden' => 'stored hidden',
    ])->refresh();

    Livewire::test(Edit::class, ['id' => $resource->id, 'slug' => 'form-field-hydration'])
        ->assertSet('form.fields.physical_default', 'physical value')
        ->assertSet('form.fields.physical_empty', '')
        ->assertSet('form.fields.physical_false', false)
        ->assertSet('form.fields.physical_null', null)
        ->assertSet('form.fields.physical_zero', 0)
        ->assertSet('form.fields.meta_value', 'meta value')
        ->assertSet('form.fields.meta_false', false)
        ->assertSet('form.fields.meta_zero', 0)
        ->assertSet('form.fields.meta_empty', '')
        ->assertSet('form.fields.meta_null', null)
        ->assertSet('form.fields.read_only', 'stored read only')
        ->tap(function ($component) {
            expect($component->instance()->form)
                ->not->toHaveKey('unknown_column')
                ->and($component->instance()->form['fields'])
                ->not->toHaveKey('hidden');
        })
        ->call('save')
        ->assertHasNoErrors();
});
