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

    protected $casts = [
        'faq' => 'array',
    ];

    protected $fillable = [
        'name',
        'create_hidden',
        'edit_hidden',
        'forms_hidden',
        'faq',
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
            [
                'name' => 'Create Hidden',
                'slug' => 'create_hidden',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable|string',
                'on_create' => false,
                'on_edit' => true,
            ],
            [
                'name' => 'Edit Hidden',
                'slug' => 'edit_hidden',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable|string',
                'on_create' => true,
                'on_edit' => false,
            ],
            [
                'name' => 'Forms Hidden',
                'slug' => 'forms_hidden',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable|string',
                'on_forms' => false,
            ],
            [
                'name' => 'FAQ',
                'slug' => 'faq',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => 'nullable|array',
            ],
            [
                'name' => 'Question',
                'slug' => 'question',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable|string',
            ],
            [
                'name' => 'Create Hidden Note',
                'slug' => 'create_hidden_note',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable|string',
                'on_create' => false,
                'on_edit' => true,
            ],
            [
                'name' => 'Edit Hidden Note',
                'slug' => 'edit_hidden_note',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable|string',
                'on_create' => true,
                'on_edit' => false,
            ],
            [
                'name' => 'Forms Hidden Note',
                'slug' => 'forms_hidden_note',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable|string',
                'on_forms' => false,
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
        $table->string('create_hidden')->default('create-default');
        $table->string('edit_hidden')->default('edit-default');
        $table->string('forms_hidden')->default('forms-default');
        $table->json('faq')->nullable();
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
        'create_hidden' => 'original create-hidden value',
        'edit_hidden' => 'original edit-hidden value',
        'forms_hidden' => 'original forms-hidden value',
        'faq' => json_encode([[
            'question' => 'Original question',
            'create_hidden_note' => 'original create-hidden note',
            'edit_hidden_note' => 'original edit-hidden note',
            'forms_hidden_note' => 'original forms-hidden note',
        ]]),
        'team_id' => $this->actor->current_team_id,
        'user_id' => $this->actor->id,
        'system_managed' => false,
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    $originalCreatedAt = DB::table('custom_table_form_writes')->where('id', $recordId)->value('created_at');

    Livewire::test(Edit::class, ['slug' => 'custom-table-form-write', 'id' => $recordId])
        ->set('form.fields.name', 'Safe update')
        ->set('form.fields.create_hidden', 'Allowed on edit')
        ->set('form.fields.edit_hidden', 'Injected edit-hidden value')
        ->set('form.fields.forms_hidden', 'Injected forms-hidden value')
        ->set('form.fields.faq.0.question', 'Safe nested update')
        ->set('form.fields.faq.0.create_hidden_note', 'Allowed nested edit')
        ->set('form.fields.faq.0.edit_hidden_note', 'Injected nested edit-hidden value')
        ->set('form.fields.faq.0.forms_hidden_note', 'Injected nested forms-hidden value')
        ->set('form.fields.team_id', null)
        ->set('form.fields.user_id', $this->otherUser->id)
        ->set('form.fields.system_managed', true)
        ->set('form.fields.created_at', now()->addYear()->toDateTimeString())
        ->call('save')
        ->assertHasNoErrors();

    $record = DB::table('custom_table_form_writes')->where('id', $recordId)->first();
    $faq = json_decode($record->faq, true);

    expect($record->name)->toBe('Safe update')
        ->and($record->create_hidden)->toBe('Allowed on edit')
        ->and($record->edit_hidden)->toBe('original edit-hidden value')
        ->and($record->forms_hidden)->toBe('original forms-hidden value')
        ->and($faq)->toBe([[
            'question' => 'Safe nested update',
            'create_hidden_note' => 'Allowed nested edit',
        ]])
        ->and((int) $record->team_id)->toBe($this->actor->current_team_id)
        ->and((int) $record->user_id)->toBe($this->actor->id)
        ->and((bool) $record->system_managed)->toBeFalse()
        ->and($record->created_at)->toBe($originalCreatedAt);
});

it('persists only declared form fields when creating a custom-table row', function () {
    Livewire::test(Create::class, ['slug' => 'custom-table-form-write'])
        ->set('form.fields.name', 'Safe create')
        ->set('form.fields.create_hidden', 'Injected create-hidden value')
        ->set('form.fields.edit_hidden', 'Allowed on create')
        ->set('form.fields.forms_hidden', 'Injected forms-hidden value')
        ->set('form.fields.faq', [[
            'question' => 'Safe nested create',
            'create_hidden_note' => 'Injected nested create-hidden value',
            'edit_hidden_note' => 'Allowed nested create',
            'forms_hidden_note' => 'Injected nested forms-hidden value',
        ]])
        ->set('form.fields.team_id', null)
        ->set('form.fields.user_id', $this->otherUser->id)
        ->set('form.fields.system_managed', true)
        ->call('save')
        ->assertHasNoErrors();

    $record = DB::table('custom_table_form_writes')->where('name', 'Safe create')->first();
    $faq = json_decode($record->faq, true);

    expect($record)->not->toBeNull()
        ->and($record->create_hidden)->toBe('create-default')
        ->and($record->edit_hidden)->toBe('Allowed on create')
        ->and($record->forms_hidden)->toBe('forms-default')
        ->and($faq)->toBe([[
            'question' => 'Safe nested create',
            'edit_hidden_note' => 'Allowed nested create',
        ]])
        ->and((int) $record->team_id)->toBe($this->actor->current_team_id)
        ->and((int) $record->user_id)->toBe($this->actor->id)
        ->and((bool) $record->system_managed)->toBeFalse();
});
