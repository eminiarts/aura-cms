<?php

use Eminiarts\Aura\Livewire\Table\Table;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('table can be rendered', function () {
    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Test Post']);

    // Visit the Post Index Page
    $this->get(route('aura.resource.index', $post->type))
        ->assertSeeLivewire('aura::post-index')
        ->assertSeeLivewire('aura::table');
});

test('table shows all input fields', function () {
    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // Assert Post is in DB
    $this->assertDatabaseHas('posts', ['title' => 'Test Post']);

    // Visit the Post Index Page
    $response = $this->get(route('aura.resource.index', $post->type));

    // dd($response->content());

    $response->assertOk();

    // Assert that all input fields are shown
    $response->assertSee('ID');
    $response->assertSee('Text');
    $response->assertSee('Number');
    $response->assertSee('Date');
    $response->assertSee('Description');
    $response->assertSee('Team');
    $response->assertSee('User');
});

test('table default values', function () {
    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // $post = Aura::findResourceBySlug($slug);

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post])
        ->assertSet('tableView', $post->defaultTableView())
        ->assertSet('perPage', $post->defaultPerPage())
        ->assertSet('columns', $post->getDefaultColumns());
});

test('table custom user columns', function () {
    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // Save custom user columns auth()->user()->getOptionColumns($this->model->getType())
    auth()->user()->updateOption('columns.'.$post->getType(), ['id', 'text', 'number']);

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post])
        ->assertSet('tableView', $post->defaultTableView())
        ->assertSet('perPage', $post->defaultPerPage())
        ->assertSet('columns', auth()->user()->getOptionColumns($post->getType()));

    // Assert Number, Date, Description, Tags, Categories, Team, User are not in the columns $component->columns
    $this->assertContains('id', $component->columns);
    $this->assertContains('text', $component->columns);
    $this->assertContains('number', $component->columns);
    $this->assertNotContains('date', $component->columns);
    $this->assertNotContains('description', $component->columns);
    $this->assertNotContains('tags', $component->columns);
    $this->assertNotContains('categories', $component->columns);
    $this->assertNotContains('team', $component->columns);
    $this->assertNotContains('user', $component->columns);

    // Visit the Post Index Page
    $response = $this->get(route('aura.resource.index', $post->type))
        ->assertOk();
});

test('table custom user columns can be sorted', function () {
    // Create a Post
    $post = Post::create([
        'title' => 'Test Post',
        'content' => 'Test Content',
        'type' => 'Post',
        'status' => 'publish',
    ]);

    // Save custom user columns auth()->user()->getOptionColumns($this->model->getType())
    auth()->user()->updateOption('columns.'.$post->getType(), ['id' => true, 'text' => true, 'number' => true, 'description' => false]);

    // Visit the Post Index Page
    $component = Livewire::test(Table::class, ['query' => null, 'model' => $post])
        ->assertSet('tableView', $post->defaultTableView())
        ->assertSet('perPage', $post->defaultPerPage())
        ->assertSet('columns', auth()->user()->getOptionColumns($post->getType()));

    $response = $this->get(route('aura.resource.index', $post->type))
        ->assertOk();

    // Assert see in order: id, text, number
    $response->assertSeeInOrder(['id', 'text', 'number']);

    // Update Colums, reorder to number, id, text
    auth()->user()->updateOption('columns_sort.'.$post->getType(), ['number', 'id', 'text']);

    $response = $this->get(route('aura.resource.index', $post->type))
        ->assertOk();

    // Assert see in order: id, text, number
    $response->assertSeeInOrder(['number', 'id', 'text']);
});
