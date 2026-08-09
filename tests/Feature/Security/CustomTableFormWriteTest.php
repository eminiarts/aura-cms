<?php

namespace Aura\Base\Tests\Feature\Security;

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class CustomTableFormWriteResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'custom-table-form-write';

    public static string $type = 'CustomTableFormWrite';

    public static bool $usesMeta = false;

    protected $fillable = [
        'name',
        'team_id',
        'user_id',
        'system_managed',
    ];

    protected $table = 'custom_table_form_writes';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_forms' => true,
            ],
        ];
    }
}

beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Custom-table tenancy tests require the teams schema.');
    }

    Schema::create('custom_table_form_writes', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->foreignId('team_id')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->boolean('system_managed')->default(false);
        $table->timestamps();
    });

    $this->actingAs($this->actor = createSuperAdmin());
    $this->otherTeam = Team::factory()->createQuietly(['user_id' => $this->actor->id]);
    $this->otherUser = User::factory()->create(['current_team_id' => $this->otherTeam->id]);

    Aura::fake();
    Aura::setModel(new CustomTableFormWriteResource);
});

afterEach(function () {
    Schema::dropIfExists('custom_table_form_writes');
});

it('persists only declared form fields when editing a custom-table row', function () {
    $recordId = DB::table('custom_table_form_writes')->insertGetId([
        'name' => 'Original',
        'team_id' => $this->actor->current_team_id,
        'user_id' => $this->actor->id,
        'system_managed' => false,
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    $originalCreatedAt = DB::table('custom_table_form_writes')->where('id', $recordId)->value('created_at');

    Livewire::test(Edit::class, ['slug' => 'custom-table-form-write', 'id' => $recordId])
        ->set('form.fields.name', 'Safe update')
        ->set('form.fields.team_id', null)
        ->set('form.fields.user_id', $this->otherUser->id)
        ->set('form.fields.system_managed', true)
        ->set('form.fields.created_at', now()->addYear()->toDateTimeString())
        ->call('save')
        ->assertHasNoErrors();

    $record = DB::table('custom_table_form_writes')->where('id', $recordId)->first();

    expect($record->name)->toBe('Safe update')
        ->and((int) $record->team_id)->toBe($this->actor->current_team_id)
        ->and((int) $record->user_id)->toBe($this->actor->id)
        ->and((bool) $record->system_managed)->toBeFalse()
        ->and($record->created_at)->toBe($originalCreatedAt);
});

it('persists only declared form fields when creating a custom-table row', function () {
    Livewire::test(Create::class, ['slug' => 'custom-table-form-write'])
        ->set('form.fields.name', 'Safe create')
        ->set('form.fields.team_id', null)
        ->set('form.fields.user_id', $this->otherUser->id)
        ->set('form.fields.system_managed', true)
        ->call('save')
        ->assertHasNoErrors();

    $record = DB::table('custom_table_form_writes')->where('name', 'Safe create')->first();

    expect($record)->not->toBeNull()
        ->and((int) $record->team_id)->toBe($this->actor->current_team_id)
        ->and((int) $record->user_id)->toBe($this->actor->id)
        ->and((bool) $record->system_managed)->toBeFalse();
});
