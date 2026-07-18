<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\BelongsToMany;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany as EloquentBelongsToMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

/**
 * A component-like stub exposing the two properties BelongsToMany::queryFor
 * reads: the field config and the parent record (the record being viewed).
 */
function belongsToManyComponent(array $field, $parent = null): object
{
    return new class($field, $parent)
    {
        public $field;

        public $parent;

        public function __construct($field, $parent)
        {
            $this->field = $field;
            $this->parent = $parent;
        }
    };
}

/**
 * The Teams-tab field exactly as the User resource declares it, stripped of the
 * closure keys the has-many view unsets before handing it to the table.
 */
function userTeamsField(): array
{
    $field = collect(User::getFields())->firstWhere('slug', 'teams');
    unset($field['conditional_logic'], $field['relation']);

    return $field;
}

/**
 * Mount the embedded table exactly as resources/views/components/fields/
 * has-many.blade.php does for the on_view Teams tab: the target resource as the
 * model, the viewed record as the parent.
 */
function mountTeamsTabTable(User $parent): Testable
{
    return Livewire::test('aura::table', [
        'model' => app(Team::class),
        'field' => userTeamsField(),
        'settings' => [
            'filters' => false,
            'global_filters' => false,
            'header_before' => false,
            'header_after' => false,
            'settings' => false,
            'search' => false,
            'selectable' => false,
        ],
        'parent' => $parent,
        'disabled' => true,
    ]);
}

/** Attach a user to a team with a fresh Team Role (records a Membership). */
function attachMembership(User $user, Team $team): void
{
    $role = Role::factory()->create(['team_id' => $team->id]);
    $user->roles()->attach($role->id, ['team_id' => $team->id]);
}

describe('BelongsToMany Field Configuration', function () {
    test('has correct properties', function () {
        $field = new BelongsToMany;

        expect($field->optionGroup)->toBe('Relationship Fields')
            ->and($field->edit)->toBe('aura::fields.has-many')
            ->and($field->type)->toBe('relation')
            ->and($field->group)->toBeTrue();
    });

    test('is treated as a relation field', function () {
        expect((new BelongsToMany)->isRelation())->toBeTrue();
    });
});

describe('BelongsToMany Field Query Scoping', function () {
    // A dedicated, framework-scope-free many-to-many pair proves the fix is
    // generic (not hardcoded to User/Team) and free of TeamScope side effects.
    beforeEach(function () {
        Schema::create('btm_gadgets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        Schema::create('btm_widgets', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        Schema::create('btm_gadget_widget', function (Blueprint $table) {
            $table->foreignId('btm_gadget_id');
            $table->foreignId('btm_widget_id');
        });
    });

    afterEach(function () {
        Schema::dropIfExists('btm_gadget_widget');
        Schema::dropIfExists('btm_widgets');
        Schema::dropIfExists('btm_gadgets');
    });

    test('parentless context returns the target query unchanged', function () {
        BtmWidget::create();
        BtmWidget::create();
        BtmWidget::create();

        // Old behavior keyed a where('user_id', ...) off the *table* model — the
        // very bug. With no parent there is nothing to scope by, so the field
        // lists the full target set.
        $component = belongsToManyComponent(['slug' => 'widgets']);

        $scoped = (new BelongsToMany)->queryFor(BtmWidget::query(), $component);

        expect($scoped->count())->toBe(3);
    });

    test('scopes the target to the parent\'s related records for a non-User/Team pair', function () {
        $gadget = BtmGadget::create();

        $a = BtmWidget::create();
        $b = BtmWidget::create();
        BtmWidget::create(); // unrelated

        $gadget->widgets()->attach([$a->id, $b->id]);

        $component = belongsToManyComponent(['slug' => 'widgets'], $gadget);

        $scoped = (new BelongsToMany)->queryFor(BtmWidget::query(), $component);

        expect($scoped->pluck('id')->sort()->values()->all())
            ->toBe([$a->id, $b->id]);
    });

    test('parent without a matching relation yields no rows', function () {
        BtmWidget::create();
        BtmWidget::create();

        // Parent present, but no `widgets` relation on it — show nothing rather
        // than leaking the full set.
        $component = belongsToManyComponent(['slug' => 'widgets'], BtmWidget::create());

        $scoped = (new BelongsToMany)->queryFor(BtmWidget::query(), $component);

        expect($scoped->count())->toBe(0);
    });
});

describe('BelongsToMany Field Teams Tab (teams on)', function () {
    beforeEach(function () {
        if (! config('aura.teams')) {
            $this->markTestSkipped('The Teams tab is a teams-on feature.');
        }
    });

    test('lists exactly the teams the user belongs to', function () {
        $member = User::factory()->create();

        $t1 = Team::factory()->createQuietly(['user_id' => $this->user->id]);
        $t2 = Team::factory()->createQuietly(['user_id' => $this->user->id]);
        Team::factory()->createQuietly(['user_id' => $this->user->id]); // not a member

        attachMembership($member, $t1);
        attachMembership($member, $t2);

        mountTeamsTabTable($member->refresh())->assertViewHas('rows', function ($rows) use ($t1, $t2) {
            return collect($rows->items())->pluck('id')->sort()->values()->all()
                === collect([$t1->id, $t2->id])->sort()->values()->all();
        });
    });

    test('a user in no teams shows an empty list', function () {
        $member = User::factory()->create();

        Team::factory()->createQuietly(['user_id' => $this->user->id]);
        Team::factory()->createQuietly(['user_id' => $this->user->id]);

        mountTeamsTabTable($member->refresh())->assertViewHas('rows', function ($rows) {
            return count($rows->items()) === 0;
        });
    });

    test('viewing user A then user B never leaks A\'s teams into B\'s tab', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $teamA = Team::factory()->createQuietly(['user_id' => $this->user->id]);
        $teamB = Team::factory()->createQuietly(['user_id' => $this->user->id]);

        attachMembership($userA, $teamA);
        attachMembership($userB, $teamB);

        mountTeamsTabTable($userA->refresh())->assertViewHas('rows', function ($rows) use ($teamA) {
            return collect($rows->items())->pluck('id')->all() === [$teamA->id];
        });

        mountTeamsTabTable($userB->refresh())->assertViewHas('rows', function ($rows) use ($teamB) {
            return collect($rows->items())->pluck('id')->all() === [$teamB->id];
        });
    });

    test('the user view page renders the Teams tab for a member', function () {
        $member = User::factory()->create();

        // The member must share the acting admin's current team, otherwise
        // TeamScope hides the record from the non-Global-Admin admin and the
        // view 404/403s before the tab ever renders.
        $currentTeam = Team::findOrFail($this->user->current_team_id);
        attachMembership($member, $currentTeam);
        attachMembership($member, Team::factory()->createQuietly(['user_id' => $this->user->id]));

        Livewire::test(View::class, ['id' => $member->id, 'slug' => 'user'])
            ->assertOk()
            ->assertSee($member->name);
    });
});

/**
 * Minimal Eloquent models backing the generic (non-User/Team) many-to-many
 * scoping tests. Their tables are created/dropped per test above.
 */
class BtmWidget extends Model
{
    protected $guarded = [];

    protected $table = 'btm_widgets';
}

class BtmGadget extends Model
{
    protected $guarded = [];

    protected $table = 'btm_gadgets';

    public function widgets(): EloquentBelongsToMany
    {
        return $this->belongsToMany(BtmWidget::class, 'btm_gadget_widget', 'btm_gadget_id', 'btm_widget_id');
    }
}
