<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\AdvancedSelect;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Livewire\Livewire;

class LazySelectActor extends Resource
{
    public static string $type = 'LazySelectActor';

    public static function getFields(): array
    {
        return [['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text', 'searchable' => true]];
    }

    public function title(): string
    {
        return $this->title;
    }
}

class LazySelectMovie extends Resource
{
    public static ?string $slug = 'lazy-select-movie';

    public static string $type = 'LazySelectMovie';

    public static function getFields(): array
    {
        return [['name' => 'Actors', 'slug' => 'actors', 'type' => 'Aura\\Base\\Fields\\AdvancedSelect', 'resource' => LazySelectActor::class]];
    }
}

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
    Aura::fake();
    Aura::setModel(new LazySelectMovie);

    $this->actors = collect(range(1, 25))->map(fn ($number) => LazySelectActor::create(['title' => "Actor {$number}"]));
});

test('editing loads only selected actors and preserves selections outside the first page', function () {
    $ids = [$this->actors[24]->id, $this->actors[20]->id];
    $movie = LazySelectMovie::create(['fields' => ['actors' => $ids]]);
    $retrieved = 0;
    LazySelectActor::retrieved(function () use (&$retrieved) {
        $retrieved++;
    });

    $component = Livewire::test(Edit::class, ['slug' => 'lazy-select-movie', 'id' => $movie->id])
        ->assertSet('form.fields.actors', $ids)
        ->assertSee('Actor 25')
        ->assertSee('Actor 21')
        ->assertDontSee('Actor 10');

    // The relation and selected-item markup may each hydrate the selected actors.
    expect($retrieved)->toBeLessThanOrEqual(count($ids) * 3);

    $component->call('save')->assertHasNoErrors();
    expect($movie->fresh()->actors->modelKeys())->toBe($ids);
});

test('option requests paginate ten actors and search beyond the first page', function () {
    $payload = ['model' => LazySelectActor::class, 'slug' => 'actors', 'field' => AdvancedSelect::class, 'fullField' => []];

    $first = $this->postJson(route('aura.api.fields.values'), $payload)->assertOk()->assertJsonCount(10)->json();
    $second = $this->postJson(route('aura.api.fields.values'), $payload + ['page' => 2])->assertOk()->assertJsonCount(10)->json();
    expect(array_intersect(array_column($first, 'id'), array_column($second, 'id')))->toBe([]);

    $this->postJson(route('aura.api.fields.values'), $payload + ['page' => 3])->assertOk()->assertJsonCount(5);
    $this->postJson(route('aura.api.fields.values'), $payload + ['search' => 'Actor 25'])
        ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $this->actors[24]->id);
    $this->postJson(route('aura.api.fields.values'), $payload + ['search' => 'missing actor'])->assertOk()->assertExactJson([]);
});

test('explicitly disabling the api retains preloaded options', function () {
    $field = LazySelectMovie::getFields()[0] + ['api' => false, 'field' => new AdvancedSelect];

    $view = $this->withViewErrors([])->blade(
        '<x-aura::fields.advanced-select :field="$field" :form="$form" />',
        ['field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('Actor 1', 'Actor 25', 'api: false');
});
