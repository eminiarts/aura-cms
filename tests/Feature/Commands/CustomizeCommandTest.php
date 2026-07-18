<?php

use Aura\Base\Resources\User;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::ensureDirectoryExists(app_path('Aura/Resources'));
});

afterEach(function () {
    File::deleteDirectory(app_path('Livewire'));
    File::deleteDirectory(resource_path('views/aura'));

    // Generated resource classes must not leak into the app-resource discovery
    // of later tests booting in this process.
    File::cleanDirectory(app_path('Aura/Resources'));
});

// Each test loads its class into the process, so names must be unique per test.
function makeCustomizeTestResource(string $name, string $slug): string
{
    $path = app_path("Aura/Resources/{$name}.php");

    File::put($path, <<<PHP
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class {$name} extends Resource
{
    public static string \$type = '{$name}';

    public static ?string \$slug = '{$slug}';
}

PHP);

    require_once $path;

    return "App\\Aura\\Resources\\{$name}";
}

it('generates component, view and hook in full mode', function () {
    $class = makeCustomizeTestResource('Article', 'article');

    $this->artisan('aura:customize', ['resource' => $class, 'type' => ['view'], '--mode' => 'full'])
        ->assertSuccessful();

    // Component extends the package component and renders the copied view
    $component = File::get(app_path('Livewire/ViewArticle.php'));
    expect($component)
        ->toContain('namespace App\Livewire;')
        ->toContain('use Aura\Base\Livewire\Resource\View as BaseView;')
        ->toContain('class ViewArticle extends BaseView')
        ->toContain('public function mount($id, $slug = null)')
        ->toContain("parent::mount(\$id, \$slug ?? 'article');")
        ->toContain("return view('aura.article.view')->layout('aura::components.layout.app');");

    // Blade view is a copy of the package view
    $blade = resource_path('views/aura/article/view.blade.php');
    expect(File::exists($blade))->toBeTrue();
    expect(File::get($blade))->toBe(File::get(dirname(__DIR__, 3).'/resources/views/livewire/resource/view.blade.php'));

    // Resource declares the component; view resolution stays with render()
    $resource = File::get(app_path('Aura/Resources/Article.php'));
    expect($resource)
        ->toContain('public static function viewComponent(): string')
        ->toContain('return \App\Livewire\ViewArticle::class;')
        ->not->toContain('viewView');
});

it('copies only the blade view in view mode', function () {
    $class = makeCustomizeTestResource('Briefing', 'briefing');

    $this->artisan('aura:customize', ['resource' => $class, 'type' => ['edit'], '--mode' => 'view'])
        ->assertSuccessful();

    expect(File::exists(resource_path('views/aura/briefing/edit.blade.php')))->toBeTrue();
    expect(File::exists(app_path('Livewire/EditBriefing.php')))->toBeFalse();

    $resource = File::get(app_path('Aura/Resources/Briefing.php'));
    expect($resource)
        ->toContain('public function editView()')
        ->toContain("return 'aura.briefing.edit';")
        ->not->toContain('editComponent');
});

it('generates only the component in component mode', function () {
    $class = makeCustomizeTestResource('Catalog', 'catalog');

    $this->artisan('aura:customize', ['resource' => $class, 'type' => ['index'], '--mode' => 'component'])
        ->assertSuccessful();

    $component = File::get(app_path('Livewire/IndexCatalog.php'));
    expect($component)
        ->toContain('class IndexCatalog extends BaseIndex')
        ->toContain('public function mount($slug = null)')
        ->toContain("parent::mount(\$slug ?? 'catalog');")
        ->not->toContain('public function render()');

    expect(File::exists(resource_path('views/aura/catalog/index.blade.php')))->toBeFalse();

    expect(File::get(app_path('Aura/Resources/Catalog.php')))
        ->toContain('public static function indexComponent(): string');
});

it('handles multiple types in one run', function () {
    $class = makeCustomizeTestResource('Dossier', 'dossier');

    $this->artisan('aura:customize', ['resource' => $class, 'type' => ['view', 'edit'], '--mode' => 'full'])
        ->assertSuccessful();

    expect(File::exists(app_path('Livewire/ViewDossier.php')))->toBeTrue();
    expect(File::exists(app_path('Livewire/EditDossier.php')))->toBeTrue();
    expect(File::exists(resource_path('views/aura/dossier/view.blade.php')))->toBeTrue();
    expect(File::exists(resource_path('views/aura/dossier/edit.blade.php')))->toBeTrue();

    $resource = File::get(app_path('Aura/Resources/Dossier.php'));
    expect($resource)
        ->toContain('public static function viewComponent(): string')
        ->toContain('public static function editComponent(): string');
});

it('skips existing files without force and keeps injection idempotent', function () {
    $class = makeCustomizeTestResource('Estate', 'estate');

    $this->artisan('aura:customize', ['resource' => $class, 'type' => ['view'], '--mode' => 'full'])
        ->assertSuccessful();

    File::put(app_path('Livewire/ViewEstate.php'), '<?php // custom changes');

    $this->artisan('aura:customize', ['resource' => $class, 'type' => ['view'], '--mode' => 'full'])
        ->assertSuccessful();

    expect(File::get(app_path('Livewire/ViewEstate.php')))->toBe('<?php // custom changes');
    expect(substr_count(File::get(app_path('Aura/Resources/Estate.php')), 'function viewComponent'))->toBe(1);
});

it('overwrites existing files with force', function () {
    $class = makeCustomizeTestResource('Fleet', 'fleet');

    $this->artisan('aura:customize', ['resource' => $class, 'type' => ['view'], '--mode' => 'full'])
        ->assertSuccessful();

    File::put(app_path('Livewire/ViewFleet.php'), '<?php // custom changes');

    $this->artisan('aura:customize', ['resource' => $class, 'type' => ['view'], '--mode' => 'full', '--force' => true])
        ->assertSuccessful();

    expect(File::get(app_path('Livewire/ViewFleet.php')))->toContain('class ViewFleet extends BaseView');
});

it('rejects invalid types and modes', function () {
    $class = makeCustomizeTestResource('Grid', 'grid');

    $this->artisan('aura:customize', ['resource' => $class, 'type' => ['detail']])
        ->assertFailed();

    $this->artisan('aura:customize', ['resource' => $class, 'type' => ['view'], '--mode' => 'everything'])
        ->assertFailed();
});

it('resolves the resource interactively', function () {
    $class = makeCustomizeTestResource('Harbor', 'harbor');

    app('aura')::registerResources([$class]);

    $this->artisan('aura:customize')
        ->expectsQuestion('Which resource would you like to customize?', $class)
        ->expectsQuestion('Which pages would you like to customize?', ['view'])
        ->expectsQuestion('What would you like to customize?', 'full')
        ->assertSuccessful();

    expect(File::exists(app_path('Livewire/ViewHarbor.php')))->toBeTrue();
});

it('resolves a resource by name', function () {
    $class = makeCustomizeTestResource('Inventory', 'inventory');

    app('aura')::registerResources([$class]);

    $this->artisan('aura:customize', ['resource' => 'inventory', 'type' => ['view'], '--mode' => 'component'])
        ->assertSuccessful();

    expect(File::exists(app_path('Livewire/ViewInventory.php')))->toBeTrue();
});

it('scaffolds an app subclass for package resources', function () {
    $this->artisan('aura:customize', ['resource' => User::class, 'type' => ['view'], '--mode' => 'component'])
        ->expectsConfirmation('Create app/Aura/Resources/User.php extending Aura\Base\Resources\User?', 'yes')
        ->assertSuccessful();

    $subclass = File::get(app_path('Aura/Resources/User.php'));
    expect($subclass)
        ->toContain('namespace App\Aura\Resources;')
        ->toContain('class User extends \Aura\Base\Resources\User')
        ->toContain('public static function viewComponent(): string');

    expect(File::exists(app_path('Livewire/ViewUser.php')))->toBeTrue();
});
