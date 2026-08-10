<?php

use Aura\Base\Fields\Select;
use Aura\Base\Fields\Status;
use Aura\Base\Fields\Text;
use Aura\Base\Livewire\Resource\Index;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
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

class Core07HideEmptyBoardResource extends Core07StatusBoardResource
{
    public static ?string $slug = 'core07-hide-empty-board';

    public static string $type = 'Core07HideEmptyBoard';

    public function kanbanSettings(): array
    {
        return array_replace(parent::kanbanSettings(), [
            'show_empty_columns' => false,
        ]);
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
        ->assertSeeInOrder(['Alpha deal', 'Zulu deal'])
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
        ->call('moveKanbanCard', $card->getKey(), 0, 'won')
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

    expect($this->user->getOption('table_view.'.$listOnly->getType()))->toBe('kanban');
});

test('unknown resource input produces a controlled not found response', function () {
    livewire(Index::class, ['slug' => 'definitely-not-a-resource'])
        ->assertNotFound();
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

test('empty column visibility and malformed contracts fail safely', function () {
    Core07HideEmptyBoardResource::create([
        'title' => 'Only visible column',
        'content' => 'One card',
        'status' => 'draft',
    ]);

    $hideEmpty = livewire(Table::class, [
        'model' => new Core07HideEmptyBoardResource,
        'settings' => ['default_view' => 'kanban'],
    ])->assertSet('currentView', 'kanban');

    expect(substr_count($hideEmpty->html(), 'class="kanban-column'))->toBe(1);

    livewire(Table::class, [
        'model' => new Core07InvalidBoardResource,
        'settings' => ['default_view' => 'kanban'],
    ])
        ->assertSet('currentView', 'list')
        ->assertSeeHtml('aura-table-list-view');
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

test('Kanban does not render records from another team', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team isolation only applies when teams are enabled.');
    }

    $foreignTeam = foreignTeam();
    Core07StatusBoardResource::createForTeamForSystem($foreignTeam->getKey(), [
        'title' => 'Foreign card',
        'content' => 'Must stay hidden',
        'status' => 'draft',
        'user_id' => $foreignTeam->user_id,
    ]);

    livewire(Table::class, [
        'model' => new Core07StatusBoardResource,
        'settings' => ['default_view' => 'kanban'],
    ])->assertDontSee('Foreign card');
});
