<?php

use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Widgets\ValueWidget;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// current
uses()->group('current');

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);

    // Create 3 posts before each test
    Post::create([
        'title' => 'Post 1',
        'number' => 10,
        'created_at' => Carbon::now()->subDays(15),
    ]);

    Post::create([
        'title' => 'Post 2',
        'number' => 20,
        'created_at' => Carbon::now()->subDays(25),
    ]);

    Post::create([
        'title' => 'Post 3',
        'number' => 30,
        'created_at' => Carbon::now()->subDays(35),
    ]);

    $this->widget = (new Post())->widgets()->first();
});

it('calculates count correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'count', 'name' => 'Total Posts Created'], 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now())
        //->assertSet('value', 2)
;

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe(2);
});

it('calculates avg correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'avg', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe(15.0);
});

it('calculates sum correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'sum', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe(30);
});

it('calculates min correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'min', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe(10);
});

it('calculates max correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'max', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe(20);
});

it('returns correct calculated values for current, previous, change', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'count', 'name' => 'Total Posts Created', 'slug' => 'total_posts_created'], 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(15))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    $values = $widget->getValuesProperty();

    expect($values)->toBeArray();
    expect($values['current'])->toBe(1);
    expect($values['previous'])->toBe(1);
    expect($values['change'])->toBe(0);
});
