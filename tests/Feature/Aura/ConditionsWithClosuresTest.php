<?php

use Eminiarts\Aura\ConditionalLogic;
use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Livewire\Posttype;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

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
});

class ConditionalLogicWithClosuresModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Text 1',
                'name' => 'Text 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'text1',
                'validation' => '',
                'conditional_logic' => [],
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                    function () {
                        return true;
                    },
                ],
                'slug' => 'text2',
            ],
        ];
    }

    public function isAppResource(): bool
    {
        return true;
    }
}
class ConditionalLogicWithoutClosuresModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Text 1',
                'name' => 'Text 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'slug' => 'text1',
                'validation' => '',
                'conditional_logic' => [],
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'text1',
                        'operator' => '==',
                        'value' => 'test',
                    ],
                ],
                'slug' => 'text2',
            ],
        ];
    }

    public function isAppResource(): bool
    {
        return true;
    }
}

test('Conditional Logic with Closures - true', function () {
    $model = new ConditionalLogicWithClosuresModel();

    $field = collect($model->getFields())->firstWhere('slug', 'text2');

    $check = ConditionalLogic::checkCondition($model, $field);

    expect($check)->toBeTrue();
});

test('Conditional Logic with Closures - false', function () {
    $model = new ConditionalLogicWithClosuresModel();

    $field = collect($model->getFields())->firstWhere('slug', 'text2');

    $field['conditional_logic'] = [
        function () {
            return false;
        },
    ];

    $check = ConditionalLogic::checkCondition($model, $field);

    expect($check)->toBeFalse();
});

test('Conditional Logic with Closures - config true', function () {
    $model = new ConditionalLogicWithClosuresModel();

    $field = collect($model->getFields())->firstWhere('slug', 'text2');

    $field['conditional_logic'] = [
        function () {
            return config('aura.teams');
        },
    ];

    $check = ConditionalLogic::checkCondition($model, $field);

    expect($check)->toBeTrue();
});

test('Conditional Logic with Closures - config false', function () {
    $model = new ConditionalLogicWithClosuresModel();

    $field = collect($model->getFields())->firstWhere('slug', 'text2');

    // set config to false
    config(['aura.teams' => false]);

    $field['conditional_logic'] = [
        function () {
            return config('aura.teams');
        },
    ];

    $check = ConditionalLogic::checkCondition($model, $field);

    expect($check)->toBeFalse();
});

test('Conditional Logic with Closures - returns false for superadmin', function () {
    $model = new ConditionalLogicWithClosuresModel();

    $field = collect($model->getFields())->firstWhere('slug', 'text2');

    // set config to false
    config(['aura.teams' => false]);

    $field['conditional_logic'] = [
        function () {
            return config('aura.teams');
        },
    ];

    $check = ConditionalLogic::checkCondition($model, $field);

    expect($check)->toBeFalse();
});

test('Posttype Builder not accessible if fields contain closure', function () {
    $model = new ConditionalLogicWithClosuresModel();

    Aura::fake();
    Aura::setModel($model);

    expect($model->fieldsHaveClosures($model->getFields()))->toBeTrue();

    Livewire::test(Posttype::class, ['slug' => 'Model'])->assertStatus(403);
});

test('Posttype Builder  accessible if fields dont contain closure', function () {
    $model = new ConditionalLogicWithoutClosuresModel();

    Aura::fake();
    Aura::setModel($model);

    expect($model->fieldsHaveClosures($model->getFields()))->toBeFalse();

    Livewire::test(Posttype::class, ['slug' => 'Model'])->assertOk();
});

test('fieldsHaveClosures() without closures', function () {
    $model = new ConditionalLogicWithClosuresModel();

    expect($model->fieldsHaveClosures($model->getFields()))->toBeTrue();

    expect($model->fieldsHaveClosures([
        [
            'label' => 'Text 1',
            'name' => 'Text 1',
            'type' => 'Eminiarts\\Aura\\Fields\\Text',
            'slug' => 'text1',
            'validation' => '',
            'conditional_logic' => [],
        ],
        [
            'label' => 'Text 2',
            'name' => 'Text 2',
            'type' => 'Eminiarts\\Aura\\Fields\\Text',
            'validation' => '',
            'conditional_logic' => [],
            'slug' => 'text2',
        ],
    ]))->toBeFalse();
});

test('fieldsHaveClosures() with closures', function () {
    $model = new ConditionalLogicWithClosuresModel();

    expect($model->fieldsHaveClosures($model->getFields()))->toBeTrue();

    expect($model->fieldsHaveClosures([
        [
            'label' => 'Text 1',
            'name' => 'Text 1',
            'type' => 'Eminiarts\\Aura\\Fields\\Text',
            'slug' => 'text1',
            'validation' => function ($value) {
                return $value === 'test';
            },
            'conditional_logic' => [],
        ],
        [
            'label' => 'Text 2',
            'name' => 'Text 2',
            'type' => 'Eminiarts\\Aura\\Fields\\Text',
            'validation' => '',
            'conditional_logic' => [],
            'slug' => 'text2',
        ],
    ]))->toBeTrue();
});
