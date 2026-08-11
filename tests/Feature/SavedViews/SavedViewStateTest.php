<?php

use Aura\Base\Contracts\DeclaresComputedTableColumns;
use Aura\Base\Resource;
use Aura\Base\SavedViews\SavedViewState;
use Aura\Base\Table\ComputedTableColumn;

class SavedViewStateResource extends Resource implements DeclaresComputedTableColumns
{
    public static ?string $slug = 'saved-view-state';

    public static string $type = 'SavedViewState';

    public function computedTableColumns(): array
    {
        return [ComputedTableColumn::make(
            key: 'title_length',
            label: 'Title length',
            render: static fn (self $record): int => strlen((string) $record->getAttribute('title')),
            export: static fn (self $record): int => strlen((string) $record->getAttribute('title')),
        )];
    }

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\Base\\Fields\\Text', 'on_index' => true],
        ];
    }
}

function validSavedViewState(): array
{
    return [
        'v' => 1,
        'query' => [
            'v' => 1,
            'filters' => [],
            'search' => null,
            'sorts' => [],
        ],
        'columns' => ['title_length'],
        'view_mode' => 'list',
        'grouping' => null,
    ];
}

test('saved view state validates canonical query and computed columns', function () {
    $state = SavedViewState::fromArray(validSavedViewState(), new SavedViewStateResource);

    expect($state->toArray())->toBe(validSavedViewState());
});

test('saved view state upgrades the pre-release flat state', function () {
    $state = SavedViewState::fromArray([
        'v' => 0,
        'filters' => [],
        'search' => 'needle',
        'sorts' => [],
        'columns' => ['title_length'],
        'view_mode' => 'list',
        'grouping' => null,
    ], new SavedViewStateResource);

    expect($state->query->search)->toBe('needle')
        ->and($state->toArray()['v'])->toBe(1);
});

test('saved view state rejects removed columns and future versions', function (array $state) {
    expect(fn () => SavedViewState::fromArray($state, new SavedViewStateResource))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'removed column' => [fn (): array => array_replace(validSavedViewState(), ['columns' => ['removed']])],
    'invalid operator' => [function (): array {
        $state = validSavedViewState();
        $state['query']['filters'] = [[
            'operator' => 'and',
            'filters' => [[
                'name' => 'title',
                'operator' => 'forged',
                'value' => 'x',
                'main_operator' => 'and',
            ]],
        ]];

        return $state;
    }],
    'future version' => [fn (): array => array_replace(validSavedViewState(), ['v' => 99])],
]);
