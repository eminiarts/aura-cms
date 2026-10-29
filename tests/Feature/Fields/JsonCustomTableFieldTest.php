<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class JsonCustomTableModel extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'json-custom-table';

    public static string $type = 'JsonCustomTable';

    public static bool $usesMeta = false;

    protected $fillable = ['payload', 'user_id', 'team_id'];

    protected $table = 'json_custom_tables';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Payload',
                'type' => 'Aura\\Base\\Fields\\Json',
                'slug' => 'payload',
                'validation' => 'nullable',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs(createSuperAdmin());

    Schema::create('json_custom_tables', function (Blueprint $table) {
        $table->id();
        $table->text('payload')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    Aura::fake();
    Aura::setModel(new JsonCustomTableModel);
});

afterEach(function () {
    Schema::dropIfExists('json_custom_tables');
});

it('stores editor JSON as text in an uncast custom-table column', function () {
    $record = JsonCustomTableModel::create(['payload' => '{"enabled":false}']);

    Livewire::test(Edit::class, ['slug' => 'json-custom-table', 'id' => $record->id])
        ->set('form.fields.payload', '{"enabled": true}')
        ->call('save')
        ->assertHasNoErrors();

    expect(json_decode(DB::table('json_custom_tables')->value('payload'), true))->toBe(['enabled' => true]);
});

it('does not fail on undecodable text when the field has no json rule', function () {
    $record = JsonCustomTableModel::create(['payload' => '{"enabled":false}']);

    Livewire::test(Edit::class, ['slug' => 'json-custom-table', 'id' => $record->id])
        ->set('form.fields.payload', '{"enabled": tru')
        ->call('save')
        ->assertHasNoErrors();

    expect(DB::table('json_custom_tables')->value('payload'))->toBe('{"enabled": tru');
});

it('creates a record with editor JSON in an uncast custom-table column', function () {
    Livewire::test(Create::class, ['slug' => 'json-custom-table'])
        ->set('form.fields.payload', '{"enabled": true}')
        ->call('save')
        ->assertHasNoErrors();

    expect(json_decode(DB::table('json_custom_tables')->value('payload'), true))->toBe(['enabled' => true]);
});

it('keeps a JSON string value intact in an uncast custom-table column', function () {
    $record = JsonCustomTableModel::create(['payload' => '{"enabled":false}']);

    Livewire::test(Edit::class, ['slug' => 'json-custom-table', 'id' => $record->id])
        ->set('form.fields.payload', '"dark"')
        ->call('save')
        ->assertHasNoErrors();

    expect(json_decode(DB::table('json_custom_tables')->value('payload'), true))->toBe('dark');
});
