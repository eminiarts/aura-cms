<?php

use Aura\Base\Commands\CustomizeComponent;
// beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));
use Aura\Base\Facades\Aura;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;



beforeEach(function () {
    // Set up a fake application structure
    config(['aura.path' => 'admin']);
    
    if (!File::exists(base_path('routes'))) {
        File::makeDirectory(base_path('routes'));
    }
    File::put(base_path('routes/web.php'), '<?php');

    // Create a stub file
    if (!File::exists(__DIR__.'/../src/Commands/Stubs')) {
        File::makeDirectory(__DIR__.'/../src/Commands/Stubs', 0755, true);
    }

    $this->app->bind('aura', fn () => new class {
        public function getResources() {
            return [
                'App\Models\User' => 'User',
                'App\Models\Post' => 'Post'
            ];
        }
    });
});

afterEach(function () {
    // Clean up created files
    File::deleteDirectory(app_path('Http/Livewire'));
    File::delete(base_path('routes/web.php'));
    File::deleteDirectory(__DIR__.'/../src/Commands/Stubs');
});

it('creates a custom component file and updates routes', function () {
    $command = $this->app->make(CustomizeComponent::class);

    // Simulate user input
    $this->artisan('aura:customize-component')
         ->expectsQuestion('Which component would you like to customize?', 'Edit')
         ->expectsQuestion('For which resource?', 'App\Models\User')
         ->assertSuccessful();

    // Check if the component file was created
    expect(File::exists(app_path('Http/Livewire/EditUser.php')))->toBeTrue();
    
    $content = File::get(app_path('Http/Livewire/EditUser.php'));
    expect($content)->toContain('namespace App\Http\Livewire;')
                    ->toContain('class EditUser extends \Aura\Base\Livewire\Resource\Edit')
                    ->toContain('// Add your custom logic here');

    // Check if the route was added
    $routeContent = File::get(base_path('routes/web.php'));
    expect($routeContent)->toContain("Route::get('admin/user/{id}/edit', App\Http\Livewire\EditUser::class)->name('user.edit');");
});

it('handles different component types', function ($componentType) {
    $command = $this->app->make(CustomizeComponent::class);

    $this->artisan('aura:customize-component')
         ->expectsQuestion('Which component would you like to customize?', $componentType)
         ->expectsQuestion('For which resource?', 'App\Models\Post')
         ->assertSuccessful();

    expect(File::exists(app_path("Http/Livewire/{$componentType}Post.php")))->toBeTrue();
    
    $content = File::get(app_path("Http/Livewire/{$componentType}Post.php"));

    ray($content)->red();

    expect($content)->toContain("class {$componentType}Post extends \Aura\Base\Livewire\Resource\\{$componentType}");

    $routeContent = File::get(base_path('routes/web.php'));
    $routeName = strtolower($componentType);
    expect($routeContent)->toContain("Route::get('admin/post/{id}/{$routeName}', App\Http\Livewire\\{$componentType}Post::class)->name('post.{$routeName}');");
})->with(['Index', 'Create', 'Edit', 'View']);

it('handles non-existent directories', function () {
    File::deleteDirectory(app_path('Http'));

    $this->artisan('aura:customize-component')
         ->expectsQuestion('Which component would you like to customize?', 'Create')
         ->expectsQuestion('For which resource?', 'App\Models\User')
         ->assertSuccessful();

    expect(File::exists(app_path('Http/Livewire/CreateUser.php')))->toBeTrue();
});