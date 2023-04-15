<?php

use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Widgets\Sparkline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(RefreshDatabase::class);

// current
//uses()->group('current');

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

it('calculates current and previous values correctly', function () {
    $widgetTest = Livewire::test(Sparkline::class, ['widget' => ['method' => 'count', 'name' => 'Total Posts Created'], 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(15))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    $values = $widget->getValuesProperty();

    expect($values)->toBeArray();
    expect($values['current'])->toBeArray();
    expect($values['previous'])->toBeArray();
});

it('correctly handles date range for current and previous', function () {
    $widgetTest = Livewire::test(Sparkline::class, ['widget' => ['method' => 'count', 'name' => 'Total Posts Created'], 'model' => new Post()])
        ->set('start', Carbon::now()->endOfDay()->subDays(30))
        ->set('end', Carbon::now()->endOfDay());

    $widget = $widgetTest->instance();

    $values = $widget->getValuesProperty();

    // expect $values['current'] and $values['previous'] to have the same number of elements
    expect($values['current'])->toBeArray();
    expect($values['previous'])->toBeArray();
    expect(count($values['current']))->toBe(count($values['previous']));

    // $values['current'] sum should be 2
    expect(array_sum($values['current']))->toBe(2);

    // $values['previous'] sum should be 1
    expect(array_sum($values['previous']))->toBe(1);

    expect($values['current'][Carbon::now()->subDays(15)->format('Y-m-d')])->toBe(1);

    expect($values['current'][Carbon::now()->subDays(25)->format('Y-m-d')])->toBe(1);

    expect($values['previous'][Carbon::now()->subDays(35)->format('Y-m-d')])->toBe(1);
});

it('calculates values correctly for given date range', function () {
    $widget = ['method' => 'count', 'name' => 'Total Posts Created'];

    $sparklineTest = Livewire::test(Sparkline::class, ['widget' => $widget, 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $sparkline = $sparklineTest->instance();

    $values = $sparkline->getValuesProperty();

    expect($values)->toBeArray();
    expect($values['current'])->toBeArray();
    expect($values['previous'])->toBeArray();
});

it('updates date range correctly', function () {
    $widget = ['method' => 'count', 'name' => 'Total Posts Created'];

    $sparklineTest = Livewire::test(Sparkline::class, ['widget' => $widget, 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $newStart = Carbon::now()->subDays(60);
    $newEnd = Carbon::now()->subDays(30);

    $sparklineTest->call('updateDateRange', $newStart, $newEnd);

    expect($sparklineTest->get('start'))->toEqual($newStart);
    expect($sparklineTest->get('end'))->toEqual($newEnd);
});

it('calculates values for the current date range correctly', function () {
    $widget = ['method' => 'count', 'name' => 'Total Posts Created'];

    Post::create([
        'title' => 'Post 4',
        'number' => 30,
        'created_at' => Carbon::now()->subMinutes(5),
    ]);

    $sparklineTest = Livewire::test(Sparkline::class, ['widget' => $widget, 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $sparkline = $sparklineTest->instance();

    $values = $sparkline->getValuesProperty();

    // Check the correct number of data points for the current date range
    expect(count($values['current']))->toBe(30);

    // Check if the last data point of the current date range has the correct value (should be 1)
    expect(end($values['current']))->toBe(1);
});

it('calculates values for the previous date range correctly', function () {
    $widget = ['method' => 'sum', 'name' => 'Total Posts Created'];

    $sparklineTest = Livewire::test(Sparkline::class, ['widget' => $widget, 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $sparkline = $sparklineTest->instance();

    $values = $sparkline->getValuesProperty();

    // Check the correct number of data points for the previous date range
    expect(count($values['previous']))->toBe(30);

    // Check if the first data point of the previous date range has the correct value (should be 0)
    expect(reset($values['previous']))->toBe(0);
});

it('calculates values for a custom column correctly', function () {
    $widget = ['method' => 'sum', 'name' => 'Total Posts Created'];

    Post::create([
        'title' => 'Post 4',
        'number' => 40,
        'created_at' => Carbon::now()->subMinutes(5),
    ]);

    $sparklineTest = Livewire::test(Sparkline::class, ['widget' => array_merge($widget, ['column' => 'number']), 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now()->endOfDay());

    $sparkline = $sparklineTest->instance();

    $values = $sparkline->getValuesProperty();

    // Check if the last data point of the current date range has the correct value for the custom column (should be 10)
    expect(end($values['current']))->toBe(40);
});

it('mounts with the correct method if specified in the widget', function () {
    $widget = ['method' => 'sum', 'name' => 'Total Posts Created'];

    Post::create([
        'title' => 'Post 4',
        'number' => 40,
        'created_at' => Carbon::now()->subMinutes(5),
    ]);

    $sparklineTest = Livewire::test(Sparkline::class, ['widget' => array_merge($widget, ['method' => 'sum']), 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    // Check if the method has been set correctly
    expect($sparklineTest->get('method'))->toBe('sum');
});

it('calculates sum correctly', function () {
    $widget = ['method' => 'sum', 'name' => 'Total Posts Created'];

    Post::create([
        'title' => 'Post 4',
        'number' => 40,
        'created_at' => Carbon::now()->subMinutes(5),
    ]);

    Post::create([
        'title' => 'Post 4',
        'number' => 60,
        'created_at' => Carbon::now()->subMinutes(5),
    ]);

    $sparklineTest = Livewire::test(Sparkline::class, ['widget' => array_merge($widget, ['column' => 'number']), 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now()->endOfDay());

    $sparkline = $sparklineTest->instance();

    $values = $sparkline->getValuesProperty();

    expect(end($values['current']))->toBe(100);
});

it('calculates avg correctly', function () {
    $widget = ['method' => 'avg', 'name' => 'Total Posts Created'];

    Post::create([
        'title' => 'Post 4',
        'number' => 40,
        'created_at' => Carbon::now()->subMinutes(5),
    ]);

    Post::create([
        'title' => 'Post 4',
        'number' => 60,
        'created_at' => Carbon::now()->subMinutes(5),
    ]);

    $sparklineTest = Livewire::test(Sparkline::class, ['widget' => array_merge($widget, ['column' => 'number']), 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now()->endOfDay());

    $sparkline = $sparklineTest->instance();

    $values = $sparkline->getValuesProperty();

    expect($sparkline->format(end($values['current'])))->toBe('50');
});

it('calculates min correctly', function () {
    $widget = ['method' => 'min', 'name' => 'Total Posts Created'];

    Post::create([
        'title' => 'Post 4',
        'number' => 40,
        'created_at' => Carbon::now()->subMinutes(5),
    ]);

    Post::create([
        'title' => 'Post 4',
        'number' => 60,
        'created_at' => Carbon::now()->subMinutes(5),
    ]);

    $sparklineTest = Livewire::test(Sparkline::class, ['widget' => array_merge($widget, ['column' => 'number']), 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now()->endOfDay());

    $sparkline = $sparklineTest->instance();

    $values = $sparkline->getValuesProperty();

    expect($sparkline->format(end($values['current'])))->toBe('40');
});

it('calculates max correctly', function () {
    $widget = ['method' => 'max', 'name' => ''];

    Post::create([
        'title' => 'Post 4',
        'number' => 30,
        'created_at' => Carbon::now()->subMinutes(5),
    ]);

    Post::create([
        'title' => 'Post 4',
        'number' => 70,
        'created_at' => Carbon::now()->subMinutes(5),
    ]);

    $sparklineTest = Livewire::test(Sparkline::class, ['widget' => array_merge($widget, ['column' => 'number']), 'model' => new Post()])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now()->endOfDay());

    $sparkline = $sparklineTest->instance();

    $values = $sparkline->getValuesProperty();

    expect($sparkline->format(end($values['current'])))->toBe('70');
});

it('renders sparkline correctly', function () {
    $widget = ['method' => 'max', 'name' => 'Test Name'];

    Livewire::test(Sparkline::class, ['widget' => $widget, 'model' => new Post()])
        ->assertOk()
        ->assertSet('loaded', false)
        ->set('loaded', true)
        ->assertSee('Test Name');
});
