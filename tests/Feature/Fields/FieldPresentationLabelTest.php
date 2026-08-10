<?php

namespace Tests\Feature\Fields;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Fields\Field;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Status;
use Aura\Base\Resource;
use Aura\Base\Support\FieldPresentationLabel;

class PresentationLabelRecord extends Resource
{
    public string $historicalPrefix = 'Historical';

    public static function getFields(): array
    {
        $resolver = function (
            mixed $rawValue,
            mixed $currentLabel,
            ?Resource $record,
            FieldValueContext $context,
        ): mixed {
            if ($rawValue === 'legacy-closed' && $record instanceof self) {
                return $record->historicalPrefix.' closed ('.$context->value.')';
            }

            return $currentLabel;
        };

        return [
            [
                'name' => 'State',
                'slug' => 'state',
                'type' => Select::class,
                'options' => [
                    ['key' => 'open', 'value' => 'Open now'],
                    ['key' => 0, 'value' => 'Integer zero'],
                    ['key' => '0', 'value' => 'String zero'],
                    ['key' => false, 'value' => 'False value'],
                ],
                'label_resolver' => $resolver,
            ],
            [
                'name' => 'Status',
                'slug' => 'status',
                'type' => Status::class,
                'options' => [
                    ['key' => 'open', 'value' => 'Open now', 'color' => 'bg-green-100'],
                ],
                'label_resolver' => $resolver,
            ],
        ];
    }
}

test('composed field presentation labels preserve strict option key semantics', function () {
    $labels = new FieldPresentationLabel;
    $options = PresentationLabelRecord::getFields()[0]['options'];

    expect($labels->current('open', $options))->toBe('Open now')
        ->and($labels->current('legacy', $options))->toBe('legacy')
        ->and($labels->current(0, $options))->toBe('Integer zero')
        ->and($labels->current('0', $options))->toBe('String zero')
        ->and($labels->current(false, $options))->toBe('False value')
        ->and($labels->current(null, $options))->toBeNull();
});

test('select resource presentation resolves historical labels on index view and export surfaces', function () {
    $record = new PresentationLabelRecord;
    $record->setAttribute('state', 'legacy-closed');

    expect(strip_tags((string) $record->display('state')))->toContain('Historical closed (index)')
        ->and((string) $record->displayInContext('state', FieldValueContext::View))
        ->toBe('Historical closed (view)')
        ->and((string) $record->exportFieldValue('state'))
        ->toBe('Historical closed (export)');
});

test('status resource presentation uses resolved current labels on index view and export surfaces', function () {
    $record = new PresentationLabelRecord;
    $record->setAttribute('status', 'open');

    expect(strip_tags((string) $record->display('status')))->toContain('Open now')
        ->and((string) $record->displayInContext('status', FieldValueContext::View))->toBe('Open now')
        ->and((string) $record->exportFieldValue('status'))->toBe('Open now');
});

test('unknown option codes remain visible across resource presentation surfaces', function () {
    $record = new PresentationLabelRecord;
    $record->setAttribute('state', 'unknown');

    expect(strip_tags((string) $record->display('state')))->toContain('unknown')
        ->and((string) $record->displayInContext('state', FieldValueContext::View))->toBe('unknown')
        ->and((string) $record->exportFieldValue('state'))->toBe('unknown');
});

test('legacy custom display extensions receive the raw hydrated value', function () {
    $field = new class extends Field
    {
        public mixed $receivedValue = null;

        public function display($field, $value, $model)
        {
            $this->receivedValue = $value;

            return 'legacy:'.$value;
        }
    };

    $definition = [
        'options' => [
            ['key' => 'open', 'value' => 'Open now'],
        ],
    ];

    expect((string) $field->presentValue('open', $definition, null, FieldValueContext::Index))
        ->toBe('legacy:open')
        ->and($field->receivedValue)->toBe('open');
});
