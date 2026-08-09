<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Facades\DynamicFunctions;
use Aura\Base\Fields\HasMany;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;

use function Pest\Livewire\livewire;

class Core05MutationResource extends Resource
{
    public array $actions = [
        'deleteRecord' => [
            'label' => 'Delete',
            'ability' => 'delete',
        ],
        'hiddenAction' => [
            'label' => 'Hidden',
            'ability' => 'update',
            'conditional_logic' => [Core05MutationResource::class, 'hideAction'],
        ],
        'missingAction' => [
            'label' => 'Missing',
            'ability' => 'update',
        ],
        'parameterizedAction' => [
            'label' => 'Parameterized',
            'ability' => 'update',
        ],
        'markReviewed' => [
            'label' => 'Mark reviewed',
            'ability' => 'update',
        ],
        'customWithoutAbility' => [
            'label' => 'Custom without ability',
        ],
    ];

    public array $bulkActions = [
        'markBulkReviewed' => [
            'label' => 'Mark reviewed',
            'ability' => 'update',
        ],
    ];

    public static ?string $slug = 'core05-mutation';

    public static string $type = 'Core05Mutation';

    public function customWithoutAbility(): void
    {
        $this->content = 'custom-action-ran';
        $this->save();
    }

    public function deleteRecord(): void
    {
        $this->delete();
    }

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Status',
                'slug' => 'status',
                'type' => 'Aura\\Base\\Fields\\Status',
                'options' => [
                    [
                        'key' => 'draft',
                        'value' => 'Draft',
                        'color' => 'gray',
                    ],
                    [
                        'key' => 'reviewed',
                        'value' => 'Reviewed',
                        'color' => 'green',
                    ],
                ],
            ],
        ];
    }

    public function hiddenAction(): void
    {
        $this->content = 'hidden-action-ran';
        $this->save();
    }

    public static function hideAction(): bool
    {
        return false;
    }

    public function indexQuery(Builder $query, ?Table $table = null): Builder
    {
        return $query->where('title', '!=', 'Excluded by indexQuery');
    }

    public function kanbanQuery($query)
    {
        return $query->where('title', '!=', 'Excluded by kanbanQuery');
    }

    public function markBulkReviewed(): void
    {
        $this->content = 'reviewed-by-bulk-action';
        $this->save();
    }

    public function markReviewed(): void
    {
        $this->content = 'reviewed-by-action';
        $this->save();
    }

    public function parameterizedAction(string $content): void
    {
        $this->content = $content;
        $this->save();
    }
}

class Core05MutationParentResource extends Resource
{
    public static ?string $slug = 'core05-mutation-parent';

    public static string $type = 'Core05MutationParent';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Children',
                'slug' => 'children',
                'type' => HasMany::class,
                'resource' => Core05MutationResource::class,
                'column' => 'parent_id',
            ],
        ];
    }
}

class Core05NoKanbanFieldResource extends Resource
{
    public static ?string $slug = 'core05-no-kanban-field';

    public static string $type = 'Core05NoKanbanField';

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
    Aura::registerResources([
        Core05MutationResource::class,
        Core05MutationParentResource::class,
        Core05NoKanbanFieldResource::class,
    ]);
    Aura::setModel(new Core05MutationResource);
});

test('table action rejects an undeclared model method', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Keep me',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'delete', 'id' => $resource->id])
        ->assertStatus(403);

    expect(Core05MutationResource::find($resource->id))->not->toBeNull();
});

test('table action denies a declared mutation when its policy denies the record', function () {
    $user = createAdmin();
    $user->roles()->first()->update([
        'permissions' => [
            'viewAny-core05-mutation' => true,
            'view-core05-mutation' => true,
            'update-core05-mutation' => false,
        ],
    ]);
    $this->actingAs($user->refresh());

    $resource = Core05MutationResource::create([
        'title' => 'Protected',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'markReviewed', 'id' => $resource->id])
        ->assertStatus(403);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('table action uses the destructive policy ability for a declared delete action', function () {
    $user = createAdmin();
    $user->roles()->first()->update([
        'permissions' => [
            'viewAny-core05-mutation' => true,
            'view-core05-mutation' => true,
            'update-core05-mutation' => true,
            'delete-core05-mutation' => false,
        ],
    ]);
    $this->actingAs($user->refresh());

    $resource = Core05MutationResource::create([
        'title' => 'Not deletable',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'deleteRecord', 'id' => $resource->id])
        ->assertStatus(403);

    expect(Core05MutationResource::find($resource->id))->not->toBeNull();
});

test('table action validates the client-provided record identifier', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Unchanged',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'markReviewed', 'id' => [$resource->id]])
        ->assertHasErrors(['id']);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('table action rejects a declared action whose condition is false', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Hidden action',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'hiddenAction', 'id' => $resource->id])
        ->assertStatus(403);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('table action rejects a declared action without a real model method', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Missing action',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'missingAction', 'id' => $resource->id])
        ->assertStatus(422);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('table action rejects client parameters for a method that requires arguments', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Parameterized action',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', [
            'action' => 'parameterizedAction',
            'id' => $resource->id,
            'parameters' => ['forged-content'],
        ])
        ->assertStatus(422);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('table action runs an authorized declared mutation', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Action target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'markReviewed', 'id' => $resource->id])
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('reviewed-by-action');
});

test('kanban status change denies a record the policy does not allow updating', function () {
    $user = createAdmin();
    $user->roles()->first()->update([
        'permissions' => [
            'viewAny-core05-mutation' => true,
            'view-core05-mutation' => true,
            'update-core05-mutation' => false,
        ],
    ]);
    $this->actingAs($user->refresh());

    $resource = Core05MutationResource::create([
        'title' => 'Protected card',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('updateCardStatus', $resource->id, 'reviewed')
        ->assertStatus(403);

    expect($resource->fresh()->status)->toBe('draft');
});

test('kanban status change rejects a value outside the declared field options', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Validated card',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('updateCardStatus', $resource->id, 'forged-status')
        ->assertHasErrors(['kanbanStatus']);

    expect($resource->fresh()->status)->toBe('draft');
});

test('kanban status change validates client-provided identifiers and values', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Malformed move',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('updateCardStatus', [$resource->id], ['reviewed'])
        ->assertHasErrors(['cardId', 'kanbanStatus']);

    expect($resource->fresh()->status)->toBe('draft');
});

test('kanban status change persists an authorized declared option', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Movable card',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('updateCardStatus', $resource->id, 'reviewed')
        ->assertHasNoErrors();

    expect($resource->fresh()->status)->toBe('reviewed');
});

test('kanban status change rejects a resource without the configured group field', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05NoKanbanFieldResource::create([
        'title' => 'No Kanban field',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('updateCardStatus', $resource->id, 'reviewed')
        ->assertHasErrors(['kanbanField']);

    expect($resource->fresh()->status)->toBe('draft');
});

test('table action cannot resolve a record from another team', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team isolation only applies when teams are enabled.');
    }

    $this->actingAs(createSuperAdmin());

    $otherTeam = foreignTeam();
    $foreignResource = Core05MutationResource::withoutGlobalScopes()->create([
        'title' => 'Other team',
        'content' => 'unchanged',
        'status' => 'draft',
        'team_id' => $otherTeam->id,
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'markReviewed', 'id' => $foreignResource->id])
        ->assertNotFound();

    expect(Core05MutationResource::withoutGlobalScopes()->findOrFail($foreignResource->id)->content)
        ->toBe('unchanged');
});

test('kanban status change cannot resolve a record from another team', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team isolation only applies when teams are enabled.');
    }

    $this->actingAs(createSuperAdmin());

    $otherTeam = foreignTeam();
    $foreignResource = Core05MutationResource::withoutGlobalScopes()->create([
        'title' => 'Other team card',
        'status' => 'draft',
        'team_id' => $otherTeam->id,
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('updateCardStatus', $foreignResource->id, 'reviewed')
        ->assertNotFound();

    expect(Core05MutationResource::withoutGlobalScopes()->findOrFail($foreignResource->id)->status)
        ->toBe('draft');
});

test('table and kanban mutations reject a forged record id', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Existing resource',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $forgedId = $resource->id + 100000;

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'markReviewed', 'id' => $forgedId])
        ->assertNotFound();

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('updateCardStatus', $forgedId, 'reviewed')
        ->assertNotFound();

    $freshResource = $resource->fresh();

    expect($freshResource->content)->toBe('unchanged')
        ->and($freshResource->status)->toBe('draft');
});

test('table action cannot mutate a record excluded by the resource index query', function () {
    $this->actingAs(createSuperAdmin());

    $excluded = Core05MutationResource::create([
        'title' => 'Excluded by indexQuery',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'markReviewed', 'id' => $excluded->id])
        ->assertNotFound();

    expect($excluded->fresh()->content)->toBe('unchanged');
});

test('kanban cannot mutate a record excluded by the resource index query', function () {
    $this->actingAs(createSuperAdmin());

    $excluded = Core05MutationResource::create([
        'title' => 'Excluded by indexQuery',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('updateCardStatus', $excluded->id, 'reviewed')
        ->assertNotFound();

    expect($excluded->fresh()->status)->toBe('draft');
});

test('table action cannot mutate a same-type record outside the parent relationship', function () {
    $this->actingAs(createSuperAdmin());

    $parent = Core05MutationParentResource::create(['title' => 'Parent']);
    $otherParent = Core05MutationParentResource::create(['title' => 'Other parent']);
    $related = Core05MutationResource::create([
        'title' => 'Related',
        'content' => 'unchanged',
        'status' => 'draft',
        'parent_id' => $parent->id,
    ]);
    $unrelated = Core05MutationResource::create([
        'title' => 'Unrelated',
        'content' => 'unchanged',
        'status' => 'draft',
        'parent_id' => $otherParent->id,
    ]);

    livewire(Table::class, [
        'query' => null,
        'model' => new Core05MutationResource,
        'parent' => $parent,
        'field' => $parent->fieldBySlug('children'),
    ])->call('action', ['action' => 'markReviewed', 'id' => $unrelated->id])
        ->assertNotFound();

    livewire(Table::class, [
        'query' => null,
        'model' => new Core05MutationResource,
        'parent' => $parent,
        'field' => $parent->fieldBySlug('children'),
    ])->call('updateCardStatus', $unrelated->id, 'reviewed')
        ->assertNotFound();

    livewire(Table::class, [
        'query' => null,
        'model' => new Core05MutationResource,
        'parent' => $parent,
        'field' => $parent->fieldBySlug('children'),
    ])->set('selected', [$related->id, $unrelated->id])
        ->call('bulkAction', 'markBulkReviewed')
        ->assertHasErrors(['selected']);

    expect($related->fresh()->content)->toBe('unchanged')
        ->and($related->fresh()->status)->toBe('draft')
        ->and($unrelated->fresh()->content)->toBe('unchanged')
        ->and($unrelated->fresh()->status)->toBe('draft');
});

test('table action cannot mutate a record excluded by a declared dynamic query', function () {
    $this->actingAs(createSuperAdmin());

    $visible = Core05MutationResource::create([
        'title' => 'Visible dynamic row',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $excluded = Core05MutationResource::create([
        'title' => 'Excluded dynamic row',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $queryHash = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::query()->whereKey($visible->id)
    );

    livewire(Table::class, ['query' => $queryHash, 'model' => new Core05MutationResource])
        ->call('action', ['action' => 'markReviewed', 'id' => $excluded->id])
        ->assertNotFound();

    expect($excluded->fresh()->content)->toBe('unchanged');
});

test('cosmetic table search does not narrow the mutation authorization scope', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Action target outside search',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->set('search', 'No matching row')
        ->call('action', ['action' => 'markReviewed', 'id' => $resource->id])
        ->assertHasNoErrors();

    expect($resource->fresh()->content)->toBe('reviewed-by-action');
});

test('custom table action without an explicit ability fails closed', function () {
    $this->actingAs(createSuperAdmin());

    $resource = Core05MutationResource::create([
        'title' => 'Custom action target',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => $resource])
        ->call('action', ['action' => 'customWithoutAbility', 'id' => $resource->id])
        ->assertStatus(422);

    expect($resource->fresh()->content)->toBe('unchanged');
});

test('kanban mutation always applies the declared Kanban query scope', function () {
    $this->actingAs(createSuperAdmin());

    $excluded = Core05MutationResource::create([
        'title' => 'Excluded by kanbanQuery',
        'status' => 'draft',
    ]);

    livewire(Table::class, ['query' => null, 'model' => new Core05MutationResource])
        ->call('updateCardStatus', $excluded->id, 'reviewed')
        ->assertNotFound();

    expect($excluded->fresh()->status)->toBe('draft');
});

test('declared dynamic mutation scope cannot be widened through Livewire state tampering', function () {
    $this->actingAs(createSuperAdmin());

    $visible = Core05MutationResource::create([
        'title' => 'Locked visible row',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $excluded = Core05MutationResource::create([
        'title' => 'Locked excluded row',
        'content' => 'unchanged',
        'status' => 'draft',
    ]);
    $restrictedQuery = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::query()->whereKey($visible->id)
    );
    $widenedQuery = DynamicFunctions::add(
        fn (): Builder => Core05MutationResource::query()
    );

    expect(fn () => livewire(Table::class, [
        'query' => $restrictedQuery,
        'model' => new Core05MutationResource,
    ])->set('query', $widenedQuery)
        ->call('action', ['action' => 'markReviewed', 'id' => $excluded->id]))
        ->toThrow(CannotUpdateLockedPropertyException::class);

    expect($excluded->fresh()->content)->toBe('unchanged');
});
