<?php

use Eminiarts\Aura\Http\Livewire\CreatePosttype;
use Eminiarts\Aura\Resources\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use function Pest\Livewire\livewire;

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
});

// make sure only superadmins can access this component
test('user can not access component', function () {
    // Create User
    $user = User::factory()->create();

    // Login
    $this->actingAs($user);

    // Test InviteUser Livewire Component
    livewire(CreatePosttype::class)
        ->assertForbidden();
});

// make sure only superadmins can access this component
test('only superadmins can access this component', function () {
    livewire(CreatePosttype::class)
        ->assertOk();
});

// call save should fail
test('call save should fail', function () {
    livewire(CreatePosttype::class)
        ->call('save')
        ->assertHasErrors(['post.fields.name' => 'required']);
});

test('set name and call save should pass and should call artisan', function () {
    // Create a mock for the Artisan facade
    $artisanMock = Mockery::mock();
    Artisan::swap($artisanMock);

    // Set up the expectation for the Artisan::call method with 'aura:posttype'
    $artisanMock->shouldReceive('call')
        ->with('aura:posttype', ['name' => 'Test'])
        ->once();

    // Set up the expectation for the Artisan::call method with 'cache:clear'
    $artisanMock->shouldReceive('call')
        ->with('cache:clear')
        ->once();

    livewire(CreatePosttype::class)
        ->set('post.fields.name', 'Test')
        ->call('save')
        ->assertHasNoErrors();

    // Verify the Artisan::call method was called as expected
    $artisanMock->shouldHaveReceived('call');
});

test('posttype file gets created correctly', function () {
    livewire(CreatePosttype::class)
        ->set('post.fields.name', 'Test')
        ->call('save')
        ->assertHasNoErrors();

    // Check if the file was created
    $filePath = app_path('Aura/Resources/Test.php');
    $this->assertFileExists($filePath);

    // Clean up the created file
    File::delete($filePath);
});
