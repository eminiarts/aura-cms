<?php

namespace Tests\Feature\Livewire;

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Http\Livewire\Post\Create;
use Eminiarts\Aura\Http\Livewire\Post\Edit;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

class DateFieldModel extends Resource
{
    public static $singularName = 'Date Model';

    public static ?string $slug = 'date-model';

    public static string $type = 'DateModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Date for Test',
                'type' => 'Eminiarts\\Aura\\Fields\\Date',
                'validation' => '',
                'format' => 'd.m.Y',
                'conditional_logic' => [],
                'slug' => 'date',
            ],
        ];
    }
}

test('Date Field in Livewire Component', function () {
    
    // $this->withoutExceptionHandling();

    $model = new DateFieldModel();

    $component = Livewire::test(Create::class, ['slug' => 'Post'])
        ->call('setModel', $model)
        ->assertSee('Create Date Model')
        ->assertSee('Date for Test')
        ->assertSeeHtml('<svg class="w-5 h-5 text-gray-400"')
        ->call('save')
        ->assertHasNoErrors(['post.fields.date']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'DateModel']);

    $component->set('post.fields.date', '2021-01-01')
        ->call('save')
        ->assertHasNoErrors(['post.fields.date']);

    // get the datemodel from db
    $dateModel = DateFieldModel::orderBy('id', 'desc')->first();

    // assert $datemodel->date is 2021-01-01
    $this->assertEquals($dateModel->fields['date'], '2021-01-01');
    $this->assertEquals($dateModel->date, '2021-01-01');

    // If I call $dateModel->display('date'), it should return 01.01.2021
    // $this->assertEquals($dateModel->display('date'), '01.01.2021');
});

test('Date Field in View', function () {

    $model = new DateFieldModel();

    Aura::fake();
    Aura::setModel($model);

    $this->actingAs($this->user)
        ->get('/admin/DateModel/create')
        ->assertOk()
        ->assertSee('Date for Test')
        ->assertSeeLivewire('aura::post-create');

    // $a = Aura::findResourceBySlug('DateModel');
    // $editComponent = Livewire::test(Edit::class, ['slug' => 'Post', 'id' => $dateModel->id])
    //     ->call('setModel', $model)
    //     ->assertSee('Edit')
    //     ->assertSee('Date for Test')
    //     ->assertSee('01.01.2021')
    //     ->call('save')
    //     ->assertHasNoErrors(['post.fields.date']);
});
