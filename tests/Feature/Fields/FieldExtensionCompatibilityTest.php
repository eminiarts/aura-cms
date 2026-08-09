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
