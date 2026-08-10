<?php

namespace Tests\Feature\Fields;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Fields\Field;
use Aura\Base\Fields\Select;
use Aura\Base\Resource;

class PresentationLabelRecord extends Resource
{
    public string $historicalPrefix = 'Historical';
}

test('field presentation distinguishes raw values from current option labels', function () {
    $field = new Select;
    $definition = [
        'slug' => 'state',
        'options' => [
            ['key' => 'open', 'value' => 'Open now'],
            ['key' => 0, 'value' => 'Zero label'],
        ],
    ];

    expect($field->rawValue('open'))->toBe('open')
        ->and($field->currentOptionLabel('open', $definition))->toBe('Open now')
        ->and($field->currentOptionLabel('legacy', $definition))->toBe('legacy')
        ->and($field->currentOptionLabel(0, $definition))->toBe('Zero label')
        ->and($field->currentOptionLabel(false, $definition))->toBe('Zero label')
        ->and($field->currentOptionLabel(null, $definition))->toBeNull();
});

test('record aware label resolver receives historical raw value current label and context', function () {
    $field = new Select;
    $record = new PresentationLabelRecord;
    $definition = [
        'slug' => 'state',
        'options' => ['closed' => 'Closed now'],
        'label_resolver' => function (
            mixed $rawValue,
            mixed $currentLabel,
            ?Resource $contextRecord,
            FieldValueContext $context,
        ): mixed {
            if ($rawValue === 'legacy-closed' && $contextRecord instanceof PresentationLabelRecord) {
                return $contextRecord->historicalPrefix.' closed ('.$context->value.')';
            }

            return $currentLabel;
        },
    ];

    expect($field->resolveLabel('legacy-closed', $definition, $record, FieldValueContext::Index))
        ->toBe('Historical closed (index)')
        ->and($field->resolveLabel('legacy-closed', $definition, $record, FieldValueContext::View))
        ->toBe('Historical closed (view)')
        ->and($field->resolveLabel('legacy-closed', $definition, $record, FieldValueContext::Export))
        ->toBe('Historical closed (export)')
        ->and($field->resolveLabel('unknown', $definition, null, FieldValueContext::Export))
        ->toBe('unknown');
});

test('generic presentation uses labels while preserving false zero empty and null', function () {
    $field = new class extends Field
    {
        public function display($field, $value, $model)
        {
            return $value;
        }
    };

    $definition = [
        'options' => [
            '0' => 'Zero',
            '1' => 'One',
        ],
    ];

    expect((string) $field->presentValue(0, $definition, null, FieldValueContext::Index))->toBe('Zero')
        ->and((string) $field->presentValue(false, $definition, null, FieldValueContext::View))->toBe('Zero')
        ->and($field->presentValue('', $definition, null, FieldValueContext::Export))->toBe('')
        ->and($field->presentValue(null, $definition, null, FieldValueContext::Export))->toBeNull();
});
