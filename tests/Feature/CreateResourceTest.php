<?php

use Aura\Base\Livewire\CreateResource;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('regular user cannot access create resource component', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    livewire(CreateResource::class)
        ->assertForbidden();
});

test('super admin can access create resource component', function () {
    livewire(CreateResource::class)
        ->assertOk();
});

test('save fails without name', function () {
    livewire(CreateResource::class)
        ->call('save')
        ->assertHasErrors(['form.fields.name' => 'required']);
});

test('save calls artisan resource command with name', function () {
    $artisanMock = Mockery::mock();
    Artisan::swap($artisanMock);

    $artisanMock->shouldReceive('call')
        ->with('aura:resource', ['name' => 'Test'])
        ->once();

    $artisanMock->shouldReceive('call')
        ->with('cache:clear')
        ->once();

    livewire(CreateResource::class)
        ->set('form.fields.name', 'Test')
        ->call('save')
        ->assertHasNoErrors();

    $artisanMock->shouldHaveReceived('call');
});

test('resource file is created in correct location', function () {
    livewire(CreateResource::class)
        ->set('form.fields.name', 'Test')
        ->call('save')
        ->assertHasNoErrors();

    $filePath = app_path('Aura/Resources/Test.php');

    expect($filePath)->toBeFile();

    File::delete($filePath);
});
