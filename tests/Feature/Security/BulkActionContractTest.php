<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Illuminate\Support\Facades\Cache;

use function Pest\Livewire\livewire;

class Core06BulkResource extends Resource
{
    public array $bulkActions = [
        'assignOwner' => [
            'ability' => 'update',
            'label' => 'Assign owner',
            'parameters' => [
                'owner_id' => [
                    'label' => 'Owner',
                    'rules' => ['required', 'integer', 'min:1'],
                    'type' => 'integer',
                ],
            ],
        ],
    ];

    public static $singularName = 'Core06 bulk resource';

    public static ?string $slug = 'core06-bulk-resource';

    public static string $type = 'Core06BulkResource';

    public function assignOwner(array $parameters): void
    {
        $this->content = get_debug_type($parameters['owner_id']).':'.$parameters['owner_id'];
        $this->save();
    }

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
        ];
    }
}

beforeEach(function () {
    Aura::fake();
    Aura::registerResources([Core06BulkResource::class]);
    Aura::setModel(new Core06BulkResource);
    Cache::clear();

    $this->actingAs(createSuperAdmin());
});

test('declared bulk parameters are validated, typed, and passed to a record action', function () {
    $resource = Core06BulkResource::create(['title' => 'Target']);

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkAction', 'assignOwner', ['owner_id' => '42'])
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('int:42');
});

test('bulk actions reject forged and invalid parameters before invoking a handler', function (array $parameters) {
    $resource = Core06BulkResource::create([
        'title' => 'Target',
        'content' => 'unchanged',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core06BulkResource])
        ->set('selected', [$resource->getKey()])
        ->call('bulkAction', 'assignOwner', $parameters)
        ->assertHasErrors();

    expect($resource->fresh()->content)->toBe('unchanged');
})->with([
    'undeclared key' => [['owner_id' => 42, 'is_admin' => true]],
    'wrong type' => [['owner_id' => 'not-an-integer']],
    'missing required value' => [[]],
]);
