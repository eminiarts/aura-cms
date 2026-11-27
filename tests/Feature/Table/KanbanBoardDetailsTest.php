<?php

use Aura\Base\Fields\BelongsTo;
use Aura\Base\Fields\Date;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Status;
use Aura\Base\Fields\Text;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Support\KanbanConfiguration;

use function Pest\Livewire\livewire;

class BoardDetailsDealResource extends Resource
{
    public static ?string $slug = 'board-details-deal';

    public static string $type = 'BoardDetailsDeal';

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => Text::class],
            ['name' => 'Stage', 'slug' => 'stage', 'type' => Status::class, 'options' => [
                ['key' => 'lead', 'value' => 'Lead', 'color' => 'bg-gray-100 text-gray-800'],
                ['key' => 'won', 'value' => 'Won', 'color' => 'bg-green-100 text-green-800'],
            ]],
            ['name' => 'Amount', 'slug' => 'amount', 'type' => Number::class, 'prefix' => 'CHF', 'thousands_separator' => ','],
            ['name' => 'Owner', 'slug' => 'owner', 'type' => BelongsTo::class, 'resource' => User::class],
            ['name' => 'Close', 'slug' => 'close', 'type' => Date::class, 'format' => 'Y-m-d'],
        ];
    }

    public function kanbanSettings(): array
    {
        return [
            'enabled' => true,
            'group_field' => 'stage',
            'card_title' => 'title',
            'column_total' => 'amount',
            'card_fields' => ['amount', 'close', 'owner', 'missing'],
        ];
    }
}

class BoardDetailsPlainResource extends BoardDetailsDealResource
{
    public static ?string $slug = 'board-details-plain';

    public static string $type = 'BoardDetailsPlain';

    public function kanbanSettings(): array
    {
        return ['enabled' => true, 'group_field' => 'stage', 'card_title' => 'title'];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('board details are opt-in and unknown card fields are dropped', function () {
    $config = KanbanConfiguration::for(new BoardDetailsDealResource);

    expect($config['enabled'])->toBeTrue()
        ->and($config['column_total'])->toBe('amount')
        ->and($config['card_fields'])->toBe(['amount', 'close', 'owner']);

    $plain = KanbanConfiguration::for(new BoardDetailsPlainResource);

    expect($plain['column_total'])->toBeNull()
        ->and($plain['card_fields'])->toBe([]);
});

test('the board sums each column and shows the card fields', function () {
    BoardDetailsDealResource::create(['title' => 'Alpha', 'stage' => 'lead', 'amount' => 1200, 'owner' => $this->user->id, 'close' => now()->addDays(3)->toDateString()]);
    BoardDetailsDealResource::create(['title' => 'Beta', 'stage' => 'lead', 'amount' => 800]);

    livewire(Table::class, ['model' => new BoardDetailsDealResource, 'settings' => ['default_view' => 'kanban']])
        ->assertSet('currentView', 'kanban')
        ->assertSeeHtml('data-kanban-column-total')
        ->assertSeeHtml("<span class=\"aura-num-unit\">CHF</span>\u{00A0}2,000")
        ->assertSee(now()->addDays(3)->format('M j'))
        ->assertSeeHtml('title="'.$this->user->name.'"');
});

test('a board without the keys shows no sums', function () {
    BoardDetailsPlainResource::create(['title' => 'Alpha', 'stage' => 'lead', 'amount' => 1200]);

    livewire(Table::class, ['model' => new BoardDetailsPlainResource, 'settings' => ['default_view' => 'kanban']])
        ->assertSee('Alpha')
        ->assertDontSeeHtml('data-kanban-column-total');
});

test('a related list keeps its own default view instead of the index preference', function () {
    $this->user->updateOption('table_view.BoardDetailsDeal', 'kanban');

    livewire(Table::class, ['model' => new BoardDetailsDealResource])
        ->assertSet('currentView', 'kanban');

    livewire(Table::class, ['model' => new BoardDetailsDealResource, 'parent' => $this->user, 'settings' => ['default_view' => 'list']])
        ->assertSet('currentView', 'list');
});
