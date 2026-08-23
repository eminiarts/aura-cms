<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\BelongsTo;
use Aura\Base\Fields\Json;
use Aura\Base\Fields\Roles;
use Aura\Base\Fields\Tags;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Tag;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Blade;

use function Pest\Livewire\livewire;

/**
 * `components/fields/view-value.blade.php` renders `display()` output raw so
 * that producers which intentionally emit markup (Wysiwyg, Boolean icons, the
 * tag pills) keep working. Those producers therefore own the escaping of every
 * database-backed value they interpolate — otherwise a record title becomes
 * stored XSS in every viewer's session.
 */
const XSS_PAYLOAD = '<script>alert(1)</script>';

class MarkupEscapingModel extends Resource
{
    public static ?string $slug = 'markupescaping';

    public static string $type = 'MarkupEscaping';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'polymorphic_relation' => true,
                'on_index' => true,
            ],
        ];
    }

    public function title()
    {
        return $this->title;
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('the Tags display escapes a database-backed tag title', function () {
    $tag = Tag::create(['type' => 'Tag', 'title' => XSS_PAYLOAD, 'name' => XSS_PAYLOAD, 'slug' => 'xss-tag']);

    $field = [
        'slug' => 'tags',
        'resource' => Tag::class,
    ];

    $html = (new Tags)->display($field, [$tag->id], new MarkupEscapingModel);

    expect($html)
        ->not->toContain(XSS_PAYLOAD)
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->toContain('<span');
});

test('the BelongsTo display escapes the related record title', function () {
    Aura::fake();
    Aura::setModel(new MarkupEscapingModel);

    $related = MarkupEscapingModel::create(['type' => 'MarkupEscaping', 'title' => XSS_PAYLOAD]);

    $field = [
        'slug' => 'related',
        'resource' => MarkupEscapingModel::class,
    ];

    $html = (new BelongsTo)->display($field, $related->id, new MarkupEscapingModel);

    expect($html)
        ->not->toContain(XSS_PAYLOAD)
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;')
        ->toContain('<a class=');
});

test('the Roles display escapes a database-backed role name', function () {
    $role = Role::create([
        'type' => 'Role',
        'name' => XSS_PAYLOAD,
        'slug' => 'xss-role',
        'super_admin' => false,
        'permissions' => [],
    ]);

    $user = User::factory()->create();

    if (config('aura.teams')) {
        $user->roles()->attach($role->id, ['team_id' => $this->user->current_team_id]);
        $user->forceFill(['current_team_id' => $this->user->current_team_id])->save();
    } else {
        $user->roles()->attach($role->id);
    }

    $html = (new Roles)->display(['slug' => 'roles'], null, $user->refresh());

    expect($html)
        ->not->toContain(XSS_PAYLOAD)
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
});

test('the Json display escapes stored markup', function () {
    $html = (new Json)->display(['slug' => 'payload'], ['note' => XSS_PAYLOAD], new MarkupEscapingModel);

    // json_encode escapes the closing slash but not the opening tag, so the raw
    // payload still opens a script element when rendered raw.
    expect($html)
        ->not->toContain('<script>')
        ->toContain('&lt;script&gt;');
});

test('a tag title with a script payload is rendered escaped in the table', function () {
    Aura::fake();
    Aura::registerResources([MarkupEscapingModel::class]);
    Aura::setModel(new MarkupEscapingModel);

    $tag = Tag::create(['type' => 'Tag', 'title' => XSS_PAYLOAD, 'name' => XSS_PAYLOAD, 'slug' => 'xss-tag']);

    $record = MarkupEscapingModel::create(['type' => 'MarkupEscaping', 'title' => 'Innocent']);
    $record->update(['tags' => [$tag->id]]);

    livewire(Table::class, ['query' => null, 'model' => $record->refresh()])
        ->assertDontSee(XSS_PAYLOAD, false);
});

test('the breadcrumb renders a database-backed title escaped', function () {
    $html = Blade::render(
        '<x-aura::breadcrumbs.li :title="$title" />',
        ['title' => XSS_PAYLOAD]
    );

    expect($html)
        ->not->toContain(XSS_PAYLOAD)
        ->toContain('&lt;script&gt;alert(1)&lt;/script&gt;');
});
