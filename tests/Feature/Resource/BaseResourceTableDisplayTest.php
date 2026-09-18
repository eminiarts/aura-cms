<?php

use Aura\Base\BaseResource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * BaseResource is the table-capable foundation for models whose fields live in
 * physical columns. AuraResourceTableConfig::display() resolves a plain column
 * through resolveFieldValue(), so a BaseResource subclass must be able to feed
 * an index table without a meta table anywhere in sight.
 */
class BaseResourceTableModel extends BaseResource
{
    public static $customTable = true;

    public static ?string $slug = 'base-widget';

    public static string $type = 'BaseWidget';

    public static bool $usesMeta = false;

    protected $fillable = ['name', 'quantity'];

    protected $table = 'base_widgets';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\Base\Fields\Text',
                'on_index' => true,
            ],
            [
                'name' => 'Quantity',
                'slug' => 'quantity',
                'type' => 'Aura\Base\Fields\Number',
                'on_index' => true,
            ],
        ];
    }
}

afterEach(function () {
    Schema::dropIfExists('base_widgets');
});

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Schema::create('base_widgets', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
        $table->integer('quantity')->nullable();
        $table->timestamps();
    });
});

test('a BaseResource subclass renders plain columns in an index table', function () {
    $widget = BaseResourceTableModel::create(['name' => 'Bolt', 'quantity' => 7]);

    expect($widget->getHeaders()->all())->toBe([
        'id' => 'ID',
        'name' => 'Name',
        'quantity' => 'Quantity',
    ]);

    $row = collect($widget->getHeaders()->keys())
        ->mapWithKeys(fn ($key) => [$key => $widget->display($key)])
        ->all();

    expect($row)->toBe([
        'id' => (string) $widget->id,
        'name' => 'Bolt',
        'quantity' => '7',
    ]);
});

test('a BaseResource subclass resolves a falsy plain column', function () {
    $widget = BaseResourceTableModel::create(['name' => '', 'quantity' => 0]);

    // The value survives display() instead of being swallowed as "falsy".
    expect($widget->display('quantity'))->toBe('0')
        ->and($widget->display('name'))->toBe('');
});

test('the BaseResource fields accessor is keyed by slug and holds no meta', function () {
    $widget = BaseResourceTableModel::create(['name' => 'Nut', 'quantity' => 3]);

    expect($widget->fields->all())->toBe(['name' => 'Nut', 'quantity' => 3])
        ->and($widget->getMeta())->toBe([])
        ->and($widget->getMeta('name'))->toBeNull();
});
