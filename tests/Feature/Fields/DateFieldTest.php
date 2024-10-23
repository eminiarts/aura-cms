<?php

namespace Tests\Feature\Livewire;

use Livewire\Livewire;
use Aura\Base\Resource;
use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Post;
use Aura\Base\Livewire\Resource\Edit;
use Illuminate\Support\Facades\Route;
use Aura\Base\Livewire\Resource\Create;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());   
});

class DateFieldModel extends Resource
{
    public static $singularName = 'Date Model';

    public static ?string $slug = 'datemodel';

    public static string $type = 'DateModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Date for Test',
                'type' => 'Aura\\Base\\Fields\\Date',
                'validation' => '',
                'format' => 'd.m.Y',
                'conditional_logic' => [],
                'slug' => 'date',
            ],
        ];
    }
}

test('Date Field in Livewire Component', function () {
    $this->withoutExceptionHandling();

    $model = new DateFieldModel;

    Aura::fake();
    Aura::setModel($model);

    $component = Livewire::test(Create::class, ['slug' => 'datemodel'])
        ->call('setModel', $model)
        ->assertSee('Create Date Model')
        ->assertSee('Date for Test')
        ->assertSeeHtml('<svg class="w-5 h-5 text-gray-400"')
        ->call('save')
        ->assertHasNoErrors(['form.fields.date']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'DateModel']);

    $component->set('form.fields.date', '2021-01-01')
        ->call('save')
        ->assertHasNoErrors(['form.fields.date']);

    // get the datemodel from db
    $dateModel = DateFieldModel::orderBy('id', 'desc')->first();

    // assert $datemodel->date is 2021-01-01
    $this->assertEquals($dateModel->fields['date'], '2021-01-01');
    $this->assertEquals($dateModel->date, '2021-01-01');

    // If I call $dateModel->display('date'), it should return 01.01.2021
    // $this->assertEquals($dateModel->display('date'), '01.01.2021');
});

test('Date Field in View', function () {
    $model = new DateFieldModel;

    Aura::fake();
    Aura::setModel($model);

    ray(Route::has("admin/datemodel/create"))->red();
    // Add this to debug the actual route URL:

    $this->actingAs($this->user)
        ->get('/admin/datemodel/create')
        ->assertOk()
        ->assertSee('Date for Test')
        ->assertSeeLivewire('aura::post-create');
});
