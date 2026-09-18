<?php

use Aura\Base\Facades\Aura;
use Illuminate\Support\Facades\File;

/**
 * getAppFields() used to read `aura.fields.path` / `aura.fields.namespace`,
 * which do not exist in config/aura.php — so it always returned [] and app
 * fields under app/Aura/Fields were never registered. The canonical keys are
 * the same `aura-settings.paths.*` pair getAppResources() uses.
 */
beforeEach(function () {
    $this->fieldPath = config('aura-settings.paths.fields.path');
    $this->fieldFile = $this->fieldPath.'/DiscoveredField.php';

    File::ensureDirectoryExists($this->fieldPath);
    File::put($this->fieldFile, <<<'PHP'
<?php

namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class DiscoveredField extends Field
{
    public $edit = 'aura::fields.text';

    public $view = 'aura::fields.view-value';
}
PHP);
});

afterEach(function () {
    File::delete($this->fieldFile);

    if (File::isDirectory($this->fieldPath) && File::files($this->fieldPath) === []) {
        File::deleteDirectory($this->fieldPath);
    }

    Aura::flushState();
});

test('getAppFields discovers classes under the configured fields path', function () {
    expect(Aura::getAppFields())->toContain('App\Aura\Fields\DiscoveredField');
});

test('a discovered app field is registered on the Aura instance', function () {
    // Same call the service provider makes when it boots.
    Aura::registerFields(Aura::getAppFields());

    expect(Aura::getFields())->toContain('App\Aura\Fields\DiscoveredField');
});

test('getAppFields returns an empty array when the fields path is absent', function () {
    config()->set('aura-settings.paths.fields.path', base_path('does/not/exist'));

    expect(Aura::getAppFields())->toBe([]);
});
