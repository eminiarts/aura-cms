<?php

use Livewire\Livewire;
use Aura\Base\Resource;
use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Genre;
use Aura\Base\Livewire\Resource\Create;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new GenreModel);
    Aura::registerRoutes('genre');
    Aura::registerRoutes('movie');
    Aura::clear();

    // Create some fake genres
    // Genre::factory()->count(3)->create();
});

// Create Resource for this test
class GenreModel extends Resource
{
    public static $singularName = 'Genre';

    public static ?string $slug = 'genre';

    public static string $type = 'Genre';

    public static function getFields()
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'title',
            ],
            [
                'name' => 'Thumbnail',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'thumbnail',
            ],
        ];
    }

    public function title()
    {
        return optional($this)->title;
    }
}

class MovieModel extends Resource
{
    public static $singularName = 'Movie';

    public static ?string $slug = 'movie';

    public static string $type = 'Movie';

    public static function getFields()
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'title',
            ],
            [
                'name' => 'Genre',
                'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
                'validation' => '',
                'slug' => 'genre',
                'resource' => 'GenreModel',
                'api' => true,
                'searchable' => true,
                'thumbnail' => 'path/to/thumbnail.jpg',
            ],
        ];
    }
}

test('displays selected genre in advanced select when api option is enabled', function () {

    $genre1 = GenreModel::create([
        'title' => 'Action',
        'thumbnail' => ['1'],
    ]);

    $genre2 = GenreModel::create([
        'title' => 'Comedy',
        'thumbnail' => ['2'],
    ]);

    $model = new MovieModel;

    $component = Livewire::test(Create::class, ['slug' => 'movie'])
        ->call('setModel', $model)
        ->assertSee('Create Movie')
        ->assertSee('Genre')
        ->set('form.fields.title', 'The Matrix')

        // ->assertSeeHtml('x-data="{ api: true }"')
        // ->assertSeeHtml('path/to/thumbnail.jpg')
        // ->assertSeeHtml('id="resource-field-genre-wrapper"')
        // ->call('dispatchBrowserEvent', 'click', ['selector' => '#resource-field-genre-wrapper button'])

        // ->assertSeeHtml('Action')
        // ->assertSeeHtml('Comedy')
        ->assertDontSee('advanced-select-view-selected')

        ->set('form.fields.genre', [[$genre1->id]])

        ->assertSee('advanced-select-view-selected')

        ->assertSeeHtml('x-text="selectedItemMarkup(item).title"')
        ->call('save')
        ->assertHasNoErrors(['form.fields.advancedselect']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'Movie']);

    $model = MovieModel::first();

    $this->assertEquals('The Matrix', $model->title);
    // dd($model->genre);
    $this->assertCount(1, $model->genre);
    $this->assertEquals($genre1->id, $model->genre[0]->id);

    $this->assertCount(1, $model->genre);
});
