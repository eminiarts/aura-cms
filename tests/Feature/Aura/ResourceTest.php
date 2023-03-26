<?php

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Resources\User;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

uses()->group('current');

test('resource folder in app gets loaded correctly', function () {
    // Delete app/Aura/Resources/TestPosttype.php if it exists
    File::delete(app_path('Aura/Resources/TestPosttype.php'));

    // expect Aura::getResources() not to include TestPosttype
    expect(Aura::getResources())->not->toContain('App\\Aura\\Resources\\TestPosttype');

    Artisan::call('aura:posttype', [
           'name' => 'TestPosttype',
       ]);

    // assert app/Aura/Resources/TestPosttype.php exists
    $this->assertTrue(File::exists(app_path('Aura/Resources/TestPosttype.php')));

    expect(Aura::getAppResources())->toContain('App\\Aura\\Resources\\TestPosttype');

    // delete app/Aura/Resources/TestPosttype.php
    File::delete(app_path('Aura/Resources/TestPosttype.php'));
});

test('Aura findResourceBySlug() returns correct resource', function () {
    $user = Aura::findResourceBySlug('User');

    expect($user)->toBeInstanceOf(User::class);
});

test('Aura findResourceBySlug() lowercase', function () {
    $user = Aura::findResourceBySlug('user');

    expect($user)->toBeInstanceOf(User::class);
});

test('app resource overwrites vendor resource', function () {
    // Todo: add test
})->todo();

test('Aura getAppResources()', function () {
    expect(Aura::getAppResources())->toBeArray();
});

test('Aura config of Resources', function () {
    $path = config('aura.resources.path');

    expect($path)->toBe(app_path('Aura/Resources'));
});
