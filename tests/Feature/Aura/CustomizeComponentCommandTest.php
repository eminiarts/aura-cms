<?php

// beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Set up a fake application structure
    config(['aura.path' => 'admin']);

    if (! File::exists(base_path('routes'))) {
        File::makeDirectory(base_path('routes'));
    }
    File::put(base_path('routes/web.php'), '<?php');

    $this->app->bind('aura', fn () => new class
    {
        public function getResources()
        {
            return [
                'App\Models\User' => 'User',
                'App\Models\Post' => 'Post',
            ];
        }
    });
});

afterEach(function () {
    // Clean up created files
    File::deleteDirectory(app_path('Http/Livewire'));
    File::delete(base_path('routes/web.php'));
});

it('creates a custom component file and updates routes', function () {
    $this->artisan('aura:customize-component')
        ->expectsQuestion('Which component would you like to customize?', 'Edit')
        ->expectsQuestion('For which resource?', 'App\Models\User')
        ->assertSuccessful();

    expect(File::exists(app_path('Http/Livewire/EditUser.php')))->toBeTrue();

    $content = File::get(app_path('Http/Livewire/EditUser.php'));
    expect($content)
        ->toContain('namespace App\Http\Livewire;')
        ->toContain('use Aura\Base\Livewire\Resource\Edit;')
        ->toContain('class EditUser extends Edit')
        ->toContain('public function mount($id, $slug = \'User\')')
        ->toContain('parent::mount($slug, $id);');

    $routeContent = File::get(base_path('routes/web.php'));
    expect($routeContent)->toContain("Route::get('admin/user/{id}/edit', App\Http\Livewire\EditUser::class)->name('user.edit');");
});

it('handles different component types', function ($componentType) {
    $this->artisan('aura:customize-component')
        ->expectsQuestion('Which component would you like to customize?', $componentType)
        ->expectsQuestion('For which resource?', 'App\Models\Post')
        ->assertSuccessful();

    expect(File::exists(app_path("Http/Livewire/{$componentType}Post.php")))->toBeTrue();

    $content = File::get(app_path("Http/Livewire/{$componentType}Post.php"));
    expect($content)
        ->toContain("namespace App\Http\Livewire;")
        ->toContain("use Aura\Base\Livewire\Resource\\{$componentType};")
        ->toContain("class {$componentType}Post extends {$componentType}")
        ->toContain('public function mount($id, $slug = \'Post\')')
        ->toContain('parent::mount($slug, $id);');

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

it('appends new routes without overwriting existing content', function () {
    File::put(base_path('routes/web.php'), '<?php

// Existing routes
Route::get("/", function () {
    return view("welcome");
});
');

    $this->artisan('aura:customize-component')
        ->expectsQuestion('Which component would you like to customize?', 'Edit')
        ->expectsQuestion('For which resource?', 'App\Models\User')
        ->assertSuccessful();

    $routeContent = File::get(base_path('routes/web.php'));
    expect($routeContent)
        ->toContain('// Existing routes')
        ->toContain('return view("welcome");')
        ->toContain("Route::get('admin/user/{id}/edit', App\Http\Livewire\EditUser::class)->name('user.edit');");
});
