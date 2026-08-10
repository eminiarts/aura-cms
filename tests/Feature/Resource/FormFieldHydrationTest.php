<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Panel;
use Aura\Base\Fields\Slug;
use Aura\Base\Fields\Text;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class NullUnsafeLegacyHydrationField extends Text
{
    public function hydrate($value, $field)
    {
        if ($value === null) {
            throw new LogicException('Legacy hydration must not receive null.');
        }

        return 'legacy:'.$value;
    }
}

class FormFieldHydrationResource extends Resource
{
    public static $customTable = true;

    public static bool $sensitiveFieldVisible = true;

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
        $fields = [
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
            ['name' => 'Create hidden', 'slug' => 'create_hidden', 'type' => Text::class, 'default' => 'create hidden', 'on_create' => false],
            ['name' => 'Edit hidden', 'slug' => 'edit_hidden', 'type' => Text::class, 'default' => 'edit hidden', 'on_edit' => false],
            ['name' => 'Create hidden panel', 'slug' => 'create_hidden_panel', 'type' => Panel::class, 'on_create' => false],
            ['name' => 'Parent hidden', 'slug' => 'parent_hidden', 'type' => Text::class, 'default' => 'parent hidden'],
            ['name' => 'Edit hidden panel', 'slug' => 'edit_hidden_panel', 'type' => Panel::class, 'on_edit' => false],
            ['name' => 'Edit parent hidden', 'slug' => 'edit_parent_hidden', 'type' => Text::class, 'default' => 'edit parent hidden'],
            ['name' => 'Legacy nullable', 'slug' => 'legacy_nullable', 'type' => NullUnsafeLegacyHydrationField::class],
            ['name' => 'Derived provider slug', 'slug' => 'derived_provider_slug', 'type' => Slug::class, 'based_on' => 'sensitive_provider_field', 'custom' => false, 'disabled' => true],
        ];

        if (self::$sensitiveFieldVisible) {
            $fields[] = ['name' => 'Sensitive provider field', 'slug' => 'sensitive_provider_field', 'type' => Text::class, 'default' => 'sensitive default'];
        }

        return $fields;
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
    FormFieldHydrationResource::$sensitiveFieldVisible = true;
});

afterEach(function () {
    Aura::clear();
    FormFieldHydrationResource::$sensitiveFieldVisible = true;
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
                ->not->toHaveKey('hidden')
                ->not->toHaveKey('create_hidden')
                ->not->toHaveKey('parent_hidden');
        })
        ->set('form.fields.hidden', 'forged hidden')
        ->set('form.fields.create_hidden', 'forged create hidden')
        ->set('form.fields.parent_hidden', 'forged parent hidden')
        ->set('form.fields.read_only', 'forged read only')
        ->set('form.fields.unknown', 'forged unknown')
        ->set('form.fields.meta_value', 'required')
        ->call('save')
        ->assertHasNoErrors();

    $created = FormFieldHydrationResource::query()->sole();

    expect($created->physical_default)->toBe('database default')
        ->and($created->getMeta('hidden'))->toBeNull()
        ->and($created->getMeta('create_hidden'))->toBeNull()
        ->and($created->getMeta('parent_hidden'))->toBeNull()
        ->and($created->getMeta('read_only'))->toBeNull()
        ->and($created->getMeta('unknown'))->toBeNull();
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
        'edit_hidden' => 'stored edit hidden',
        'edit_parent_hidden' => 'stored edit parent hidden',
        'legacy_nullable' => null,
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
                ->not->toHaveKey('hidden')
                ->not->toHaveKey('edit_hidden')
                ->not->toHaveKey('edit_parent_hidden');
        })
        ->set('form.fields.hidden', 'forged hidden')
        ->set('form.fields.edit_hidden', 'forged edit hidden')
        ->set('form.fields.edit_parent_hidden', 'forged edit parent hidden')
        ->set('form.fields.read_only', 'forged read only')
        ->set('form.fields.unknown', 'forged unknown')
        ->call('save')
        ->assertHasNoErrors();

    $resource->refresh();

    expect($resource->getMeta('hidden'))->toBe('stored hidden')
        ->and($resource->getMeta('edit_hidden'))->toBe('stored edit hidden')
        ->and($resource->getMeta('edit_parent_hidden'))->toBe('stored edit parent hidden')
        ->and($resource->getMeta('read_only'))->toBe('stored read only')
        ->and($resource->getMeta('unknown'))->toBeNull();
});

test('query values and a changed field provider cannot seed or persist unavailable fields', function () {
    Livewire::withQueryParams([
        'create_hidden' => 'forged query hidden',
        'parent_hidden' => 'forged query parent hidden',
        'meta_value' => 'query value',
        'sensitive_provider_field' => 'query sensitive',
    ])->test(Create::class, ['slug' => 'form-field-hydration'])
        ->assertSet('form.fields.meta_value', 'query value')
        ->assertSet('form.fields.sensitive_provider_field', 'query sensitive')
        ->tap(function ($component) {
            expect($component->instance()->form['fields'])
                ->not->toHaveKey('create_hidden')
                ->not->toHaveKey('parent_hidden');
        })
        ->tap(function () {
            FormFieldHydrationResource::$sensitiveFieldVisible = false;
            Aura::flushFieldCache();
        })
        ->set('form.fields.sensitive_provider_field', 'forged after provider change')
        ->set('form.fields.derived_provider_slug', 'forged_security_identity')
        ->call('save')
        ->assertHasNoErrors();

    $created = FormFieldHydrationResource::query()->sole();

    expect($created->getMeta('sensitive_provider_field'))->toBeNull()
        ->and($created->getMeta('derived_provider_slug'))->toBeNull()
        ->and($created->getMeta('create_hidden'))->toBeNull()
        ->and($created->getMeta('parent_hidden'))->toBeNull();
});

test('modal parameters cannot seed fields absent from the create form', function () {
    Livewire::test(Create::class, [
        'slug' => 'form-field-hydration',
        'params' => [
            'create_hidden' => 'forged modal hidden',
            'parent_hidden' => 'forged modal parent hidden',
            'meta_value' => 'modal value',
        ],
    ])->assertSet('form.fields.meta_value', 'modal value')
        ->tap(function ($component) {
            expect($component->instance()->form['fields'])
                ->not->toHaveKey('create_hidden')
                ->not->toHaveKey('parent_hidden');
        });
});
