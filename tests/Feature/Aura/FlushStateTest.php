<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Widgets\ValueWidget;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;

class FlushStateResource extends Resource
{
    public static array $fieldDefinition = [
        ['name' => 'First', 'slug' => 'first', 'type' => 'Aura\\Base\\Fields\\Text'],
    ];

    public static function getFields(): array
    {
        return static::$fieldDefinition;
    }
}

beforeEach(function () {
    FlushStateResource::$fieldDefinition = [
        ['name' => 'First', 'slug' => 'first', 'type' => 'Aura\\Base\\Fields\\Text'],
    ];
});

it('flushes all request-scoped caches through one public entry point', function () {
    $resource = new FlushStateResource;
    $visible = false;
    $conditionalField = [
        'slug' => 'conditional',
        'conditional_logic' => function () use (&$visible): bool {
            return $visible;
        },
    ];

    expect($resource->fieldsCollection()->pluck('slug')->all())->toBe(['first'])
        ->and(Aura::checkCondition($resource, $conditionalField))->toBeFalse();

    FlushStateResource::$fieldDefinition = [
        ['name' => 'Second', 'slug' => 'second', 'type' => 'Aura\\Base\\Fields\\Text'],
    ];
    $visible = true;
    Aura::useUserModel(stdClass::class);

    expect($resource->fieldsCollection()->pluck('slug')->all())->toBe(['first'])
        ->and(Aura::checkCondition($resource, $conditionalField))->toBeFalse();

    Aura::flushState();

    expect($resource->fieldsCollection()->pluck('slug')->all())->toBe(['second'])
        ->and(Aura::checkCondition($resource, $conditionalField))->toBeTrue()
        ->and(Aura::userModel())->toBe(User::class);
});

it('flushes state after a queue job is processed', function () {
    Aura::useUserModel(stdClass::class);

    Event::dispatch(new JobProcessed('sync', Mockery::mock(Job::class), null));

    expect(Aura::userModel())->toBe(User::class);
});

it('restores boot registrations and removes transient registrations', function () {
    $aura = new \Aura\Base\Aura;
    $baselineHook = fn (): string => 'baseline';
    $transientHook = fn (): string => 'transient';

    $aura->registerFields([Text::class]);
    $aura->registerResources([FlushStateResource::class]);
    $aura->registerWidgets([ValueWidget::class]);
    $aura->registerInjectView('baseline', $baselineHook);
    $aura->captureBaselineState();

    $aura->registerFields([stdClass::class]);
    $aura->registerResources([User::class]);
    $aura->registerWidgets([stdClass::class]);
    $aura->registerInjectView('transient', $transientHook);
    $aura->flushState();

    expect($aura->getFields())->toBe([Text::class])
        ->and($aura->getResources())->toBe([FlushStateResource::class])
        ->and($aura->getWidgets())->toBe([ValueWidget::class])
        ->and($aura->getInjectViews())->toHaveKey('baseline')
        ->and($aura->getInjectViews())->not->toHaveKey('transient');
});

it('clears the model held by the testing fake', function () {
    Aura::fake();
    Aura::setModel(new FlushStateResource);

    expect(Aura::findResourceBySlug('anything'))->toBeInstanceOf(FlushStateResource::class);

    Aura::flushState();

    expect(Aura::findResourceBySlug('anything'))->toBe('anything');
});
