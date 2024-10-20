<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Resource;
use Aura\Base\Resources\Genre;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    // Create some fake genres
    // Genre::factory()->count(3)->create();
});

// Create Resource for this test
class NewGenreModel extends Resource
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

            [
                'name' => 'movies',
                'type' => 'Aura\\Base\\Fields\\HasMany',
                'resource' => 'NewMovieModel',
                'slug' => 'movies',
                'create' => true,
            ],
        ];
    }

    public function title()
    {
        return optional($this)->title;
    }
}

class NewMovieModel extends Resource
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
                'resource' => 'NewGenreModel',
                'api' => true,
                'searchable' => true,
            ],
        ];
    }
}

test('movies can be attached to genres', function () {

    $genre1 = NewGenreModel::create([
        'title' => 'Action',
        'thumbnail' => ['1'],
    ]);

    $genre2 = NewGenreModel::create([
        'title' => 'Comedy',
        'thumbnail' => ['2'],
    ]);

    $movie1 = NewMovieModel::create([
        'title' => 'Matrix',
        'genre' => [[$genre1->id]],
    ]);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'Movie']);

    // dd($movie1->toArray(), $movie1->title);

    $this->assertEquals('Matrix', $movie1->title);
    $this->assertCount(1, $movie1->genre);
    $this->assertEquals($genre1->id, $movie1->genre[0]->id);

    // dd($movie1->genre->toArray());

    $this->assertCount(1, $movie1->genre);
});

test('displays attached movies on view genre page', function () {

    $genre1 = NewGenreModel::create([
        'title' => 'Action',
        'thumbnail' => ['1'],
    ]);

    $genre2 = NewGenreModel::create([
        'title' => 'Comedy',
        'thumbnail' => ['2'],
    ]);

    $movie1 = NewMovieModel::create([
        'title' => 'Matrix',
        'genre' => [[$genre1->id]],
    ]);

    // Aura::fake();
    // Aura::setModel($genre1);

    $model = new NewGenreModel;
    Aura::fake();
    Aura::setModel($model);

    $component = Livewire::test(View::class, ['slug' => 'NewGenreModel', 'id' => $genre1->id]);
    // ray($component->html());
    $component
        ->assertSee('View Genre')
        ->assertSee('movies')
        ->assertSee($movie1->title);

});
