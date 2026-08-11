<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Reporting\CurrentStateProjectionReconciler;
use Aura\Base\Tests\Resources\Post;
use Aura\Base\Widgets\ValueWidget;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Freeze time: the posts are seeded relative to now() and the widget
    // ranges are built from a later now() — crossing a second boundary in
    // between (routine on loaded CI runners) drops the -15d post out of the
    // window and flakes the calculation assertions.
    Carbon::setTestNow(Carbon::now());
    config([
        'aura.reporting.projection.enabled' => true,
        'aura.reporting.projection.reads_enabled' => true,
    ]);
    (require dirname(__DIR__, 3).'/database/migrations/create_aura_reporting_projections.php.stub')->up();

    $this->actingAs($this->user = createSuperAdmin());
    Aura::registerResources([Post::class]);

    // Create 3 posts before each test
    Post::create([
        'title' => 'Post 1',
        'slug' => 'post-1',
        'number' => 10,
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
        'created_at' => Carbon::now()->subDays(15),
    ]);

    Post::create([
        'title' => 'Post 2',
        'slug' => 'post-2',
        'number' => 20,
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
        'created_at' => Carbon::now()->subDays(25),
    ]);

    Post::create([
        'title' => 'Post 3',
        'slug' => 'post-3',
        'number' => 30,
        'user_id' => $this->user->id,
        ...config('aura.teams') ? ['team_id' => $this->user->current_team_id] : [],
        'created_at' => Carbon::now()->subDays(35),
    ]);

    Post::query()->get()->each(fn (Post $post) => app(CurrentStateProjectionReconciler::class)->resync($post));

    $this->widget = (new Post)->widgets()->first();
});

it('calculates count correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'count', 'name' => 'Total Posts Created'], 'model' => new Post])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());
    // ->assertSet('value', 2)

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe(2);
});

it('calculates avg correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'avg', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe('15.000000');
});

it('calculates sum correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'sum', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe('30.000000');
});

it('calculates min correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'min', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe('10.000000');
});

it('calculates max correctly', function () {
    $widgetTest = Livewire::test(ValueWidget::class, ['widget' => ['method' => 'max', 'column' => 'number', 'name' => 'Widget'], 'model' => new Post])
        ->set('start', Carbon::now()->subDays(30))
        ->set('end', Carbon::now());

    $widget = $widgetTest->instance();

    expect($widget->getValue($widget->start, $widget->end))->toBe('20.000000');
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

function core24RelativeLuminance(array $rgb): float
{
    $channels = array_map(static function (int $channel): float {
        $value = $channel / 255;

        return $value <= 0.04045
            ? $value / 12.92
            : (($value + 0.055) / 1.055) ** 2.4;
    }, $rgb);

    return (0.2126 * $channels[0]) + (0.7152 * $channels[1]) + (0.0722 * $channels[2]);
}

function core24ContrastRatio(array $foreground, array $background): float
{
    $lighter = max(core24RelativeLuminance($foreground), core24RelativeLuminance($background));
    $darker = min(core24RelativeLuminance($foreground), core24RelativeLuminance($background));

    return ($lighter + 0.05) / ($darker + 0.05);
}

it('renders accessible mode-specific goal positive and negative badges', function (
    array $widget,
    Carbon $start,
    string $classes,
): void {
    Livewire::test(ValueWidget::class, [
        'widget' => $widget,
        'model' => new Post,
    ])
        ->set('start', $start)
        ->set('end', Carbon::now())
        ->call('loadWidget')
        ->assertSeeHtml($classes);
})->with([
    'goal' => [
        ['method' => 'count', 'name' => 'Goal', 'slug' => 'goal', 'goal' => 4],
        fn (): Carbon => Carbon::now()->subDays(30),
        'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
    ],
    'positive' => [
        ['method' => 'count', 'name' => 'Positive', 'slug' => 'positive'],
        fn (): Carbon => Carbon::now()->subDays(30),
        'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-200',
    ],
    'negative' => [
        ['method' => 'count', 'name' => 'Negative', 'slug' => 'negative'],
        fn (): Carbon => Carbon::now()->subDays(10),
        'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-200',
    ],
]);

it('keeps every badge text and background pair above WCAG AA contrast', function (
    array $foreground,
    array $background,
): void {
    expect(core24ContrastRatio($foreground, $background))->toBeGreaterThanOrEqual(4.5);
})->with([
    'goal light' => [[30, 64, 175], [219, 234, 254]],
    'goal dark' => [[191, 219, 254], [23, 37, 84]],
    'positive light' => [[22, 101, 52], [220, 252, 231]],
    'positive dark' => [[187, 247, 208], [5, 46, 22]],
    'negative light' => [[153, 27, 27], [254, 226, 226]],
    'negative dark' => [[254, 202, 202], [69, 10, 10]],
]);
