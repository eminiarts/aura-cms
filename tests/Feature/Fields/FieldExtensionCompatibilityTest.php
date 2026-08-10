<?php

use Symfony\Component\Process\Process;

/**
 * @param  list<string>  $classDeclarations
 */
function declareLegacyFieldExtensions(array $classDeclarations): Process
{
    $autoload = dirname(__DIR__, 3).'/vendor/autoload.php';
    $code = sprintf(
        "require %s;\n%s\necho 'loaded';",
        var_export($autoload, true),
        implode("\n", $classDeclarations),
    );
    $process = new Process([PHP_BINARY, '-d', 'display_errors=1', '-r', $code]);
    $process->run();

    return $process;
}

test('legacy field persistence overrides remain source compatible', function () {
    $process = declareLegacyFieldExtensions([
        <<<'PHP'
class LegacyBaseFieldExtension extends \Aura\Base\Fields\Field
{
    public function setTableValue($post, $field, $value)
    {
        return $value;
    }
}
PHP,
        <<<'PHP'
class LegacyDateFieldExtension extends \Aura\Base\Fields\Date
{
    public function set($post, $field, $value)
    {
        return $value;
    }
}
PHP,
        <<<'PHP'
class LegacyDatetimeFieldExtension extends \Aura\Base\Fields\Datetime
{
    public function set($post, $field, $value)
    {
        return $value;
    }
}
PHP,
        <<<'PHP'
class LegacySelectFieldExtension extends \Aura\Base\Fields\Select
{
    public function set($post, $field, $value)
    {
        return $value;
    }
}
PHP,
    ]);

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput().$process->getOutput())
        ->and($process->getOutput())->toBe('loaded');
});

test('legacy filter capability methods remain outside the Aura inheritance contract', function () {
    $process = declareLegacyFieldExtensions([
        <<<'PHP'
class LegacyBaseFilterCapabilityExtension extends \Aura\Base\Fields\Field
{
    public function filterCapability()
    {
        return 'legacy';
    }
}
PHP,
        <<<'PHP'
class LegacyDateFilterCapabilityExtension extends \Aura\Base\Fields\Date
{
    public function filterCapability($configuration)
    {
        return $configuration;
    }
}
PHP,
        <<<'PHP'
class LegacyDatetimeFilterCapabilityExtension extends \Aura\Base\Fields\Datetime
{
    protected function filterCapability()
    {
        return null;
    }
}
PHP,
        <<<'PHP'
class LegacySelectFilterCapabilityExtension extends \Aura\Base\Fields\Select
{
    public static function filterCapability(...$arguments)
    {
        return $arguments;
    }
}
PHP,
    ]);

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput().$process->getOutput())
        ->and($process->getOutput())->toBe('loaded');
});

test('legacy presentation helper names remain outside the Aura field inheritance contract', function () {
    $process = declareLegacyFieldExtensions([
        <<<'PHP'
class LegacyRawValueFieldExtension extends \Aura\Base\Fields\Field
{
    public function rawValue($value, $configuration)
    {
        return [$value, $configuration];
    }
}
PHP,
        <<<'PHP'
class LegacyResolveLabelFieldExtension extends \Aura\Base\Fields\Select
{
    protected function resolveLabel()
    {
        return 'legacy';
    }
}
PHP,
        <<<'PHP'
class LegacyRelationshipLabelFieldExtension extends \Aura\Base\Fields\BelongsTo
{
    public static function label($record)
    {
        return $record;
    }
}
PHP,
    ]);

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput().$process->getOutput())
        ->and($process->getOutput())->toBe('loaded');
});
