<?php

use Aura\Base\Aura;
use Aura\Base\Tests\Fixtures\Plugin\Fields\PackageField;
use Aura\Base\Tests\Support\FieldPluginAfterAuraTestCase;

uses(FieldPluginAfterAuraTestCase::class);

test('field sources registered after Aura load during the same package boot', function () {
    expect(config('aura-settings.paths.resources.path'))->toBe(app_path('Aura/Resources'))
        ->and(config('aura-settings.paths.fields.discover.app'))->toBe([
            'namespace' => 'App\\Aura\\Fields',
            'path' => app_path('Aura/Fields'),
        ])
        ->and(app(Aura::class)->getFields())->toContain(PackageField::class);
});
