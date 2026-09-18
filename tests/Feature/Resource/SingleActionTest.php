<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Declares its actions via the documented $actions property, without an
// actions() method. 'delete' is deliberately NOT declared.
class SingleActionPropertyResource extends Resource
{
    public array $actions = [
        'runTestAction' => [
            'label' => 'Run Test Action',
        ],
        'blockedAction' => [
            'label' => 'Blocked Action',
            'conditional_logic' => [self::class, 'never'],
        ],
    ];

    public static bool $ran = false;

    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields(): array
    {
        return [];
    }

    public static function never()
    {
        return false;
    }

    public function runTestAction()
    {
        static::$ran = true;
    }
}

test('an action declared via the $actions property runs without a BadMethodCallException', function () {
    SingleActionPropertyResource::$ran = false;

    $model = SingleActionPropertyResource::create([
        'title' => 'Test',
        'slug' => 'test',
    ]);

    Aura::fake();
    Aura::setModel($model);

    livewire(Edit::class, ['id' => $model->id])
        ->call('singleAction', 'runTestAction')
        ->assertHasNoErrors()
        ->assertDispatched('notify')
        ->assertSuccessful();

    expect(SingleActionPropertyResource::$ran)->toBeTrue();
});

test('the success notification uses the action label instead of the method name', function () {
    $model = SingleActionPropertyResource::create([
        'title' => 'Test Label',
        'slug' => 'test-label',
    ]);

    Aura::fake();
    Aura::setModel($model);

    livewire(Edit::class, ['id' => $model->id])
        ->call('singleAction', 'runTestAction')
        ->assertDispatched(
            'notify',
            fn ($event, $params) => $params['message'] === 'Successfully ran: Run Test Action'
        );
});

test('an undeclared public model method cannot be invoked as an action', function () {
    $model = SingleActionPropertyResource::create([
        'title' => 'Test Undeclared',
        'slug' => 'test-undeclared',
    ]);

    Aura::fake();
    Aura::setModel($model);

    livewire(Edit::class, ['id' => $model->id])
        ->call('singleAction', 'delete')
        ->assertNotFound();

    $this->assertDatabaseHas('posts', ['id' => $model->id]);
});

// Same shape, but declared through an actions() method — isolates the
// "undeclared action" guard from the property/method resolution fix.
class SingleActionMethodResource extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public function actions()
    {
        return [
            'runTestAction' => ['label' => 'Run Test Action'],
        ];
    }

    public static function getFields(): array
    {
        return [];
    }

    public function runTestAction()
    {
        //
    }
}

test('an undeclared method is rejected for resources using an actions() method', function () {
    $model = SingleActionMethodResource::create([
        'title' => 'Test Method Undeclared',
        'slug' => 'test-method-undeclared',
    ]);

    Aura::fake();
    Aura::setModel($model);

    livewire(Edit::class, ['id' => $model->id])
        ->call('singleAction', 'delete')
        ->assertNotFound();

    $this->assertDatabaseHas('posts', ['id' => $model->id]);
});

test('a declared action with failing conditional logic is still forbidden', function () {
    $model = SingleActionPropertyResource::create([
        'title' => 'Test Conditional',
        'slug' => 'test-conditional',
    ]);

    Aura::fake();
    Aura::setModel($model);

    livewire(Edit::class, ['id' => $model->id])
        ->call('singleAction', 'blockedAction')
        ->assertForbidden();
});
