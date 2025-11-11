<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

test('resource folder in app gets loaded correctly', function () {
    // Delete app/Aura/Resources/TestResource.php if it exists
    File::delete(app_path('Aura/Resources/TestResource.php'));

    // expect Aura::getResources() not to include TestResource
    expect(Aura::getResources())->not->toContain('App\\Aura\\Resources\\TestResource');

    Artisan::call('aura:resource', [
        'name' => 'TestResource',
    ]);

    // assert app/Aura/Resources/TestResource.php exists
    $this->assertTrue(File::exists(app_path('Aura/Resources/TestResource.php')));

    // Require the file to load the class into PHP
    require_once app_path('Aura/Resources/TestResource.php');

    expect(Aura::getAppResources())->toContain('App\\Aura\\Resources\\TestResource');

    // delete app/Aura/Resources/TestResource.php
    File::delete(app_path('Aura/Resources/TestResource.php'));
});

test('Aura findResourceBySlug() returns correct resource', function () {
    $user = Aura::findResourceBySlug('User');

    expect($user)->toBeInstanceOf(User::class);
});

test('Aura findResourceBySlug() lowercase', function () {
    $user = Aura::findResourceBySlug('user');

    expect($user)->toBeInstanceOf(User::class);
});

test('Aura findResourceBySlug() Attachment', function () {
    $attachment = Aura::findResourceBySlug('Attachment');

    expect($attachment)->toBeInstanceOf(Attachment::class);
});

test('Aura getAppResources()', function () {
    expect(Aura::getAppResources())->toBeArray();
});

test('Aura config of Resources', function () {
    $path = config('aura-settings.paths.resources.path');

    expect($path)->toBe(app_path('Aura/Resources'));
});
