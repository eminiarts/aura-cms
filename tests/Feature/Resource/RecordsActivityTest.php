<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Panel;
use Aura\Base\Fields\Status;
use Aura\Base\Fields\Text;
use Aura\Base\Fields\Textarea;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Models\Activity;
use Aura\Base\Resource;
use Aura\Base\Traits\RecordsActivity;
use Illuminate\Support\Facades\Schema;

use function Pest\Livewire\livewire;

class ActivityDealResource extends Resource
{
    use RecordsActivity;

    public static ?string $slug = 'activity-deal';

    public static string $type = 'ActivityDeal';

    public static function getFields(): array
    {
        return [
            ['name' => 'Deal', 'slug' => 'deal-panel', 'type' => Panel::class, 'style' => ['width' => '75']],
            ['name' => 'Title', 'slug' => 'title', 'type' => Text::class],
            ['name' => 'Notes', 'slug' => 'notes', 'type' => Textarea::class],
            ['name' => 'Properties', 'slug' => 'properties', 'type' => Panel::class, 'style' => ['width' => '25']],
            ['name' => 'Stage', 'slug' => 'status', 'type' => Status::class, 'options' => [
                ['key' => 'lead', 'value' => 'Lead', 'color' => 'bg-gray-100 text-gray-800'],
                ['key' => 'won', 'value' => 'Won', 'color' => 'bg-green-100 text-green-800'],
            ]],
            // Stored as meta: the comparison has to see the value the saved step wrote.
            ['name' => 'Amount', 'slug' => 'amount', 'type' => Number::class, 'prefix' => 'CHF'],
        ];
    }

    public function kanbanSettings(): array
    {
        return [
            'enabled' => true,
            'group_field' => 'status',
            'columns' => ['lead', 'won'],
            'card_title' => 'title',
            'show_empty_columns' => true,
        ];
    }

    /** Stage stays in the Properties rail. */
    public function recordHeaderFields(): array
    {
        return ['amount'];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    (require dirname(__DIR__, 3).'/database/migrations/create_aura_activities_table.php.stub')->up();

    Aura::fake();
    Aura::setModel(new ActivityDealResource);
});

test('creating and saving through the edit form records the changed fields', function () {
    $deal = ActivityDealResource::create(['title' => 'Rollout', 'fields' => ['status' => 'lead', 'amount' => 1000]]);

    expect($deal->activities()->pluck('event')->all())->toBe(['created']);

    livewire(Edit::class, ['slug' => 'activity-deal', 'id' => $deal->id])
        ->set('form.fields.status', 'won')
        ->set('form.fields.amount', 1500)
        ->call('save');

    $rows = $deal->activities()->where('event', 'updated')->get()->keyBy('field');

    expect($rows->keys()->sort()->values()->all())->toBe(['amount', 'status'])
        ->and([$rows['status']->old, $rows['status']->new])->toBe(['lead', 'won'])
        ->and([$rows['amount']->old, $rows['amount']->new])->toBe(['1000', '1500'])
        ->and((int) $rows['status']->user_id)->toBe((int) $this->user->id);

    // A save that changes nothing records nothing.
    livewire(Edit::class, ['slug' => 'activity-deal', 'id' => $deal->id])->call('save');

    expect($deal->activities()->count())->toBe(3);
});

test('a kanban move records the stage change', function () {
    $deal = ActivityDealResource::create(['title' => 'Board card', 'fields' => ['status' => 'lead']]);

    livewire(Table::class, ['model' => new ActivityDealResource, 'settings' => ['default_view' => 'kanban']])
        ->call('updateCardStatus', $deal->id, 'won');

    $row = $deal->activities()->where('event', 'updated')->sole();

    expect([$row->field, $row->old, $row->new])->toBe(['status', 'lead', 'won']);
});

test('the record page shows the history in the record template', function () {
    $deal = ActivityDealResource::create(['title' => 'Rollout', 'fields' => ['status' => 'lead']]);
    $deal->update(['fields' => ['status' => 'won']]);

    livewire(View::class, ['slug' => 'activity-deal', 'id' => $deal->id])
        ->assertSeeHtml('data-record-template')
        ->assertSeeHtml('data-record-activity')
        ->assertSeeInOrder(['moved', 'Stage', 'Lead', 'Won', 'created this activity-deal']);
});

test('without the table nothing is recorded and saving still works', function () {
    Schema::drop('aura_activities');

    $deal = ActivityDealResource::create(['title' => 'Quiet', 'fields' => ['status' => 'lead']]);
    $deal->update(['fields' => ['status' => 'won']]);

    expect(Activity::tableExists())->toBeFalse()
        ->and($deal->fresh()->status)->toBe('won');

    livewire(View::class, ['slug' => 'activity-deal', 'id' => $deal->id])
        ->assertDontSeeHtml('data-record-activity');
});
