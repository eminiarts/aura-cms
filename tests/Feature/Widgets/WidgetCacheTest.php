<?php

use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Fixtures\Widgets\CacheProbeWidget;
use Aura\Base\Tests\Fixtures\Widgets\ContextualCacheProbeWidget;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    Cache::clear();
    Carbon::setTestNow('2026-08-10 12:00:00 UTC');
    $this->actingAs($this->user = createSuperAdmin());
});

test('a missing slug uses a stable component identity and child mount cannot bypass cache initialization', function () {
    $parameters = [
        'widget' => ['name' => 'No slug'],
        'model' => new Post,
        'start' => Carbon::parse('2026-08-01 00:00:00 UTC'),
        'end' => Carbon::parse('2026-08-10 00:00:00 UTC'),
    ];

    $first = Livewire::test(CacheProbeWidget::class, $parameters)
        ->assertSet('childMounted', true)
        ->assertSet('isCached', false)
        ->assertSet('loaded', false);

    $key = $first->instance()->getCacheKeyProperty();
    Cache::put($key, ['value' => 1], 60);

    Livewire::test(CacheProbeWidget::class, $parameters)
        ->assertSet('childMounted', true)
        ->assertSet('isCached', true)
        ->assertSet('loaded', true);

    expect($key)->toStartWith('aura.widget.v1.')
        ->and($first->instance()->getCacheKeyProperty())->toBe($key);
});

test('cache identity canonicalizes associative configuration and equivalent dates', function () {
    $first = Livewire::test(CacheProbeWidget::class, [
        'widget' => [
            'id' => 'pipeline-total',
            'filters' => ['stage' => 'open', 'owner' => 42],
            'method' => 'count',
        ],
        'start' => Carbon::parse('2026-08-01 02:00:00 Europe/Zurich'),
        'end' => Carbon::parse('2026-08-10 02:00:00 Europe/Zurich'),
    ])->instance();

    $same = Livewire::test(CacheProbeWidget::class, [
        'widget' => [
            'method' => 'count',
            'filters' => ['owner' => 42, 'stage' => 'open'],
            'id' => 'pipeline-total',
        ],
        'start' => '2026-08-01T00:00:00+00:00',
        'end' => '2026-08-10T00:00:00+00:00',
    ])->instance();

    $changed = Livewire::test(CacheProbeWidget::class, [
        'widget' => [
            'id' => 'pipeline-total',
            'filters' => ['stage' => 'closed', 'owner' => 42],
            'method' => 'count',
        ],
        'start' => '2026-08-01T00:00:00+00:00',
        'end' => '2026-08-10T00:00:00+00:00',
    ])->instance();

    expect($first->getCacheKeyProperty())->toBe($same->getCacheKeyProperty())
        ->and($changed->getCacheKeyProperty())->not->toBe($first->getCacheKeyProperty());
});

test('widget cache configuration cannot be replaced through Livewire hydration', function () {
    $component = Livewire::test(CacheProbeWidget::class, [
        'widget' => ['id' => 'trusted-widget'],
        'start' => now()->subDay(),
        'end' => now(),
    ]);

    expect(fn () => $component->set('widget.id', 'forged-widget'))
        ->toThrow(CannotUpdateLockedPropertyException::class);
});

test('actor and resource dimensions affect keys only when declared by the widget class', function () {
    $otherUser = User::factory()->create([
        ...config('aura.teams') ? ['current_team_id' => $this->user->current_team_id] : [],
    ]);
    $parameters = [
        'widget' => ['id' => 'dimension-probe'],
        'model' => new Post,
        'start' => now()->subDay(),
        'end' => now(),
    ];

    $plainFirst = Livewire::test(CacheProbeWidget::class, $parameters)->instance()->getCacheKeyProperty();
    $contextualFirst = Livewire::test(ContextualCacheProbeWidget::class, $parameters)->instance()->getCacheKeyProperty();

    $this->actingAs($otherUser);

    $plainSecond = Livewire::test(CacheProbeWidget::class, $parameters)->instance()->getCacheKeyProperty();
    $contextualSecond = Livewire::test(ContextualCacheProbeWidget::class, $parameters)->instance()->getCacheKeyProperty();

    expect($plainSecond)->toBe($plainFirst)
        ->and($contextualSecond)->not->toBe($contextualFirst);
});

test('declared team and resource dimensions prevent cross tenant and cross resource cache reuse', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team cache isolation requires teams enabled.');
    }

    $parameters = [
        'widget' => ['id' => 'tenant-probe'],
        'model' => new Post,
        'start' => now()->subDay(),
        'end' => now(),
    ];
    $firstTeamId = $this->user->current_team_id;
    $firstTeamKey = Livewire::test(ContextualCacheProbeWidget::class, $parameters)
        ->instance()->getCacheKeyProperty();
    $otherResourceKey = Livewire::test(ContextualCacheProbeWidget::class, [
        ...$parameters,
        'model' => new User,
    ])->instance()->getCacheKeyProperty();

    $otherTeam = Team::factory()->create(['user_id' => $this->user->getKey()]);
    $this->user->forceFill(['current_team_id' => $otherTeam->getKey()])->saveQuietly();
    $otherTeamKey = Livewire::test(ContextualCacheProbeWidget::class, $parameters)
        ->instance()->getCacheKeyProperty();
    $explicitFirstTeamKey = TeamScope::forTeam(
        $firstTeamId,
        fn (): string => Livewire::test(ContextualCacheProbeWidget::class, $parameters)
            ->instance()->getCacheKeyProperty(),
        (new Post)->getConnection(),
    );

    expect($otherResourceKey)->not->toBe($firstTeamKey)
        ->and($otherTeamKey)->not->toBe($firstTeamKey)
        ->and($explicitFirstTeamKey)->toBe($firstTeamKey);
});

test('resource dimensions isolate same class records and database connections', function () {
    $firstPost = createPost(['title' => 'First cache record']);
    $secondPost = createPost(['title' => 'Second cache record']);
    $keyFor = function (Post $model): string {
        $widget = new ContextualCacheProbeWidget;
        $widget->widget = ['id' => 'record-context'];
        $widget->model = $model;
        $widget->start = now()->subDay();
        $widget->end = now();

        return $widget->getCacheKeyProperty();
    };

    $firstKey = $keyFor($firstPost);
    $secondKey = $keyFor($secondPost);
    $otherConnectionModel = (new Post)->setConnection('media-security-testing');
    $otherConnectionKey = $keyFor($otherConnectionModel);

    Cache::put($firstKey, ['record' => $firstPost->getKey()], 60);

    expect($secondKey)->not->toBe($firstKey)
        ->and($otherConnectionKey)->not->toBe($firstKey)
        ->and(Cache::has($secondKey))->toBeFalse()
        ->and(Cache::has($otherConnectionKey))->toBeFalse();
});

test('cache invalidation forgets the exact variant without flushing unrelated widgets', function () {
    CacheProbeWidget::$resolutions = 0;
    $parameters = [
        'widget' => ['id' => 'invalidate-me'],
        'start' => now()->subDay(),
        'end' => now(),
    ];
    $component = Livewire::test(CacheProbeWidget::class, $parameters);
    $key = $component->instance()->getCacheKeyProperty();
    expect($component->instance()->cachedValue())->toBe(1)
        ->and($component->instance()->cachedValue())->toBe(1)
        ->and(CacheProbeWidget::$resolutions)->toBe(1);
    Cache::put('unrelated-widget', ['value' => 2], 60);

    $component->call('clearCache')
        ->assertSet('isCached', false);

    expect(Cache::has($key))->toBeFalse()
        ->and($component->instance()->cachedValue())->toBe(2)
        ->and(CacheProbeWidget::$resolutions)->toBe(2)
        ->and(Cache::get('unrelated-widget'))->toBe(['value' => 2]);
});
