<?php

use Aura\Base\Tests\Resources\Post;
use Aura\Base\Widgets\ValueWidget;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    // Create 3 posts before each test
    Post::create([
        'title' => 'Post 1',
        'slug' => 'post-1',
        'number' => 10,
        'user_id' => $this->user->id,
        'team_id' => $this->user->team_id,
        'created_at' => Carbon::now()->subDays(15),
    ]);

    Post::create([
        'title' => 'Post 2',
        'slug' => 'post-2',
        'number' => 20,
        'user_id' => $this->user->id,
        'team_id' => $this->user->team_id,
        'created_at' => Carbon::now()->subDays(25),
    ]);

    Post::create([
        'title' => 'Post 3',
        'slug' => 'post-3',
        'number' => 30,
        'user_id' => $this->user->id,
        'team_id' => $this->user->team_id,
        'created_at' => Carbon::now()->subDays(35),
    ]);

    $this->widget = (new Post)->widgets()->first();
});

it('calculates count correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'count', 'name' => 'Total Posts Created'], 'model' => new Post])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());
    //->assertSet('value', 2)

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe(2);
});

it('calculates avg correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'avg', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe(15.0);
});

it('calculates sum correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'sum', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe(30);
});

it('calculates min correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'min', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe(10);
});

it('calculates max correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'max', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe(20);
});

it('returns correct calculated values for current, previous, change', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'count', 'name' => 'Total Posts Created', 'slug' => 'total_posts_created'], 'model' => new Post])
        ->set('start', Carbon::now()->subDays(15))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    $values = $widget->getValuesProperty();

    expect($values)->toBeArray();
    expect($values['current'])->toBe('1');
    expect($values['previous'])->toBe('1');
    expect($values['change'])->toBe('0');
});

it('formats a number to 2 decimals', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'max', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->format(2.2222222))->toBe('2.22');
    expect($widget->format(2.123))->toBe('2.12');
    expect($widget->format(2.588))->toBe('2.59');
    expect($widget->format(2))->toBe('2');
    expect($widget->format(2.00))->toBe('2');
});
