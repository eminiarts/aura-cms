<?php

use Aura\Base\Fields\Select;
use Aura\Base\Fields\Status;
use Aura\Base\Fields\Text;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Support\KanbanConfiguration;
use Illuminate\Support\Facades\Gate;

use function Pest\Livewire\livewire;

class Core07StatusBoardResource extends Resource
{
    public static ?string $slug = 'core07-status-board';

    public static string $type = 'Core07StatusBoard';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => Text::class,
            ],
            [
                'name' => 'Summary',
                'slug' => 'content',
                'type' => Text::class,
            ],
            [
                'name' => 'Status',
                'slug' => 'status',
                'type' => Status::class,
                'options' => [
                    ['key' => 'draft', 'value' => 'Draft', 'color' => 'gray'],
                    ['key' => 'reviewed', 'value' => 'Reviewed', 'color' => 'green'],
                    ['key' => 'published', 'value' => 'Published', 'color' => 'blue'],
                ],
            ],
        ];
    }

    public function kanbanSettings(): array
    {
        return [
            'enabled' => true,
            'group_field' => 'status',
            'columns' => ['reviewed', 'draft', 'published'],
            'card_title' => 'title',
            'card_subtitle' => 'content',
            'order_by' => ['field' => 'title', 'direction' => 'asc'],
            'show_empty_columns' => true,
        ];
    }
}

class Core07StageBoardResource extends Resource
{
    public static ?string $slug = 'core07-stage-board';

    public static string $type = 'Core07StageBoard';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => Text::class,
            ],
            [
                'name' => 'Stage',
                'slug' => 'content',
                'type' => Select::class,
                'options' => [
                    'lead' => 'Lead',
                    'won' => 'Won',
                    'lost' => 'Lost',
                ],
            ],
        ];
    }

    public function kanbanSettings(): array
    {
        return [
            'enabled' => true,
            'group_field' => 'content',
            'columns' => ['lead', 'won'],
            'card_title' => 'title',
            'card_subtitle' => null,
            'show_empty_columns' => true,
        ];
    }
}

class Core07ListOnlyResource extends Resource
{
    public static ?string $slug = 'core07-list-only';

    public static string $type = 'Core07ListOnly';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => Text::class,
            ],
        ];
    }
}

class Core07InvalidBoardResource extends Core07StatusBoardResource
{
    public static ?string $slug = 'core07-invalid-board';

    public static string $type = 'Core07InvalidBoard';

    public function kanbanSettings(): array
    {
        return array_replace(parent::kanbanSettings(), [
            'columns' => ['draft', 'forged'],
        ]);
    }
}

class Core07DisabledBoardResource extends Core07StatusBoardResource
{
    public static ?string $slug = 'core07-disabled-board';

    public static string $type = 'Core07DisabledBoard';

    public function kanbanSettings(): array
    {
        return array_replace(parent::kanbanSettings(), [
            'enabled' => false,
        ]);
    }
}

class Core07DenyUpdatePolicy
{
    public function update(User $user, Core07StageBoardResource $resource): bool
    {
        return false;
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('KanbanConfiguration resolves declared columns and disables invalid contracts', function () {
    $valid = KanbanConfiguration::for(new Core07StatusBoardResource);
    expect($valid['enabled'])->toBeTrue()
        ->and($valid['valid'])->toBeTrue()
        ->and(array_keys($valid['columns']))->toBe(['reviewed', 'draft', 'published'])
        ->and($valid['group_field'])->toBe('status')
        ->and($valid['card_title'])->toBe('title')
        ->and($valid['card_subtitle'])->toBe('content')
        ->and($valid['order_by'])->toBe(['field' => 'title', 'direction' => 'asc']);

    $stage = KanbanConfiguration::for(new Core07StageBoardResource);
    expect($stage['enabled'])->toBeTrue()
        ->and(array_keys($stage['columns']))->toBe(['lead', 'won'])
        ->and($stage['group_field'])->toBe('content');

    $invalid = KanbanConfiguration::for(new Core07InvalidBoardResource);
    expect($invalid['enabled'])->toBeFalse()
        ->and($invalid['valid'])->toBeFalse()
        ->and($invalid['columns'])->toBe([]);

    $disabled = KanbanConfiguration::for(new Core07DisabledBoardResource);
    expect($disabled['enabled'])->toBeFalse()
        ->and($disabled['valid'])->toBeTrue();

    $listOnly = KanbanConfiguration::for(new Core07ListOnlyResource);
    expect($listOnly['enabled'])->toBeFalse();
});

test('Kanban renders declared columns and real resource card fields', function () {
    Core07StatusBoardResource::create([
        'title' => 'Zulu deal',
        'content' => 'Created first',
        'status' => 'draft',
    ]);

    Core07StatusBoardResource::create([
        'title' => 'Alpha deal',
        'content' => 'A real customer opportunity',
        'status' => 'draft',
    ]);

    Core07StatusBoardResource::create([
        'title' => 'Beta deal',
        'content' => 'Already reviewed',
        'status' => 'reviewed',
    ]);

    $component = livewire(Table::class, [
        'model' => new Core07StatusBoardResource,
        'settings' => ['default_view' => 'kanban'],
    ]);

    $component
        ->assertSet('currentView', 'kanban')
        ->assertSeeInOrder(['Reviewed', 'Draft', 'Published'])
        ->assertSee('Alpha deal')
        ->assertSee('A real customer opportunity')
        ->assertSee('Beta deal')
        ->assertSeeHtml('wire:key="kanban-card-');
});

test('Kanban uses the resource configured group field for rendering and mutation', function () {
    $card = Core07StageBoardResource::create([
        'title' => 'Qualified lead',
        'content' => 'lead',
        'status' => 'draft',
    ]);

    livewire(Table::class, [
        'model' => new Core07StageBoardResource,
        'settings' => ['default_view' => 'kanban'],
    ])
        ->assertSee('Qualified lead')
        ->assertSeeInOrder(['Lead', 'Won'])
        ->call('updateCardStatus', $card->getKey(), 'won')
        ->assertHasNoErrors();

    expect($card->fresh()->content)->toBe('won')
        ->and($card->fresh()->status)->toBe('draft');
});

test('view switching persists only supported views and invalid preferences fall back safely', function () {
    $resource = new Core07StatusBoardResource;

    livewire(Table::class, ['model' => $resource])
        ->call('switchView', 'kanban')
        ->assertSet('currentView', 'kanban')
        ->assertSeeHtml('aura-table-kanban-view');

    expect($this->user->getOption('table_view.'.$resource->getType()))->toBe('kanban');

    $this->user->updateOption('table_view.'.$resource->getType(), 'forged-view');

    livewire(Table::class, ['model' => $resource])
        ->assertSet('currentView', 'list')
        ->assertSeeHtml('aura-table-list-view');

    $listOnly = new Core07ListOnlyResource;
    $this->user->updateOption('table_view.'.$listOnly->getType(), 'kanban');

    livewire(Table::class, ['model' => $listOnly])
        ->assertSet('currentView', 'list')
        ->call('switchView', 'kanban')
        ->assertSet('currentView', 'list');
});

test('saved Kanban preferences can only reorder and hide declared columns', function () {
    $resource = new Core07StatusBoardResource;
    $this->user->updateOption('kanban_statuses.'.$resource->getType(), [
        'forged' => ['value' => 'Forged', 'visible' => true],
        'draft' => ['value' => 'Forged Draft Label', 'visible' => false],
        'reviewed' => ['visible' => true],
    ]);

    $component = livewire(Table::class, [
        'model' => $resource,
        'settings' => ['default_view' => 'kanban'],
    ])->assertSet('kanbanStatuses', [
        'draft' => ['value' => 'Draft', 'color' => 'gray', 'visible' => false],
        'reviewed' => ['value' => 'Reviewed', 'color' => 'green', 'visible' => true],
        'published' => ['value' => 'Published', 'color' => 'blue', 'visible' => true],
    ]);

    $component
        ->call('reorderKanbanStatuses', 'published', 0)
        ->assertSet('kanbanStatuses', [
            'published' => ['value' => 'Published', 'color' => 'blue', 'visible' => true],
            'draft' => ['value' => 'Draft', 'color' => 'gray', 'visible' => false],
            'reviewed' => ['value' => 'Reviewed', 'color' => 'green', 'visible' => true],
        ])
        ->call('reorderKanbanStatuses', 'forged', 0)
        ->assertStatus(422);
});

test('configured Kanban group mutation still enforces the resource policy', function () {
    Gate::policy(Core07StageBoardResource::class, Core07DenyUpdatePolicy::class);
    $card = Core07StageBoardResource::create([
        'title' => 'Protected lead',
        'content' => 'lead',
        'status' => 'draft',
    ]);

    livewire(Table::class, [
        'model' => new Core07StageBoardResource,
        'settings' => ['default_view' => 'kanban'],
    ])
        ->call('updateCardStatus', $card->getKey(), 'won')
        ->assertForbidden();

    expect($card->fresh()->content)->toBe('lead');
});

test('Kanban mutations reject destinations excluded from the board contract', function () {
    $card = Core07StageBoardResource::create([
        'title' => 'Qualified lead',
        'content' => 'lead',
    ]);

    livewire(Table::class, [
        'model' => new Core07StageBoardResource,
        'settings' => ['default_view' => 'kanban'],
    ])
        ->call('updateCardStatus', $card->getKey(), 'lost')
        ->assertStatus(422);

    expect($card->fresh()->content)->toBe('lead');
});

test('Kanban mutations reject disabled and invalid board contracts', function () {
    $disabledCard = Core07DisabledBoardResource::create([
        'title' => 'Disabled board card',
        'status' => 'draft',
    ]);
    $invalidCard = Core07InvalidBoardResource::create([
        'title' => 'Invalid board card',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['model' => new Core07DisabledBoardResource])
        ->call('updateCardStatus', $disabledCard->getKey(), 'reviewed')
        ->assertStatus(422);

    livewire(Table::class, ['model' => new Core07InvalidBoardResource])
        ->call('updateCardStatus', $invalidCard->getKey(), 'reviewed')
        ->assertStatus(422);

    expect($disabledCard->fresh()->status)->toBe('draft')
        ->and($invalidCard->fresh()->status)->toBe('draft');
});
