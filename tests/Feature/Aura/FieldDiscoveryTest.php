<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Tests\Fixtures\Plugin\Fields\PackageField;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->applicationFieldPath = app_path('Aura/Fields/ApplicationField.php');

    File::ensureDirectoryExists(dirname($this->applicationFieldPath));
    File::put($this->applicationFieldPath, <<<'PHP'
<?php

namespace App\Aura\Fields;

use Aura\Base\Fields\Text;

class ApplicationField extends Text
{
}
PHP);

    if (! class_exists('App\\Aura\\Fields\\ApplicationField')) {
        require_once $this->applicationFieldPath;
    }
});

afterEach(function () {
    File::delete($this->applicationFieldPath);
});

test('application fields are discovered from configured sources', function () {
    expect(config('aura-settings.paths.fields.discover.app'))->toBe([
        'namespace' => 'App\\Aura\\Fields',
        'path' => app_path('Aura/Fields'),
    ])->and(Aura::getAppFields())->toBe([
        'App\\Aura\\Fields\\ApplicationField',
    ]);
});

test('package fields are discovered once in deterministic class order', function () {
    $packageFieldPath = dirname(__DIR__, 2).'/Fixtures/Plugin/Fields';

    config()->set('aura-settings.paths.fields', [
        'discover' => [
            'plugin' => [
                'namespace' => 'Aura\\Base\\Tests\\Fixtures\\Plugin\\Fields',
                'path' => $packageFieldPath,
            ],
            'app' => [
                'namespace' => 'App\\Aura\\Fields',
                'path' => app_path('Aura/Fields'),
            ],
            'duplicate-plugin' => [
                'namespace' => 'Aura\\Base\\Tests\\Fixtures\\Plugin\\Fields',
                'path' => $packageFieldPath,
            ],
        ],
        'register' => [
            PackageField::class,
        ],
    ]);

    expect(Aura::getAppFields())->toBe([
        'App\\Aura\\Fields\\ApplicationField',
        PackageField::class,
    ]);
});

test('the previously published single field path remains supported', function () {
    config()->set('aura-settings.paths.fields', [
        'namespace' => 'App\\Aura\\Fields',
        'path' => app_path('Aura/Fields'),
        'register' => [
            PackageField::class,
        ],
    ]);

    expect(Aura::getAppFields())->toBe([
        'App\\Aura\\Fields\\ApplicationField',
        PackageField::class,
    ]);
});
