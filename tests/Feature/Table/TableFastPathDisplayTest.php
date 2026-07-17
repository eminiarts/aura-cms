<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Instrumented test resources
|--------------------------------------------------------------------------
|
| Each overrides getFieldsWithoutConditionalLogic() (the "full accessor")
| with an invocation counter so tests can assert exactly how often the whole
| fields collection is built while a table renders.
*/

// Posts-table resource whose only visible column is a plain meta-backed Text
// field without conditional logic — the fast path should apply.
class FastPathPlainPost extends Resource
{
    public static int $fullAccessorCalls = 0;

    public static $singularName = 'FastPlain';

    public static ?string $slug = 'fast-path-plain';

    public static string $type = 'FastPathPlainPost';

    public static function getFields()
    {
        return [
            ['name' => 'Headline', 'slug' => 'headline', 'type' => 'Aura\\Base\\Fields\\Text', 'validation' => '', 'conditional_logic' => [], 'on_index' => true],
        ];
    }

    public function getFieldsWithoutConditionalLogic()
    {
        static::$fullAccessorCalls++;

        return parent::getFieldsWithoutConditionalLogic();
    }
}

// Posts-table resource whose visible column carries conditional logic — the
// fast path must NOT apply, so display() falls back to the full accessor.
class FastPathConditionalPost extends Resource
{
    public static int $fullAccessorCalls = 0;

    public static $singularName = 'FastCond';

    public static ?string $slug = 'fast-path-conditional';

    public static string $type = 'FastPathConditionalPost';

    public static function getFields()
    {
        return [
            [
                'name' => 'Secret',
                'slug' => 'secret',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'on_index' => true,
                // Non-empty conditional logic (visible to super admins) forces
                // the full-accessor fallback in display().
                'conditional_logic' => [
                    ['field' => 'role', 'operator' => '==', 'value' => 'super_admin'],
                ],
            ],
        ];
    }

    public function getFieldsWithoutConditionalLogic()
    {
        static::$fullAccessorCalls++;

        return parent::getFieldsWithoutConditionalLogic();
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

afterEach(function () {
    Aura::clear();
});

/*
|--------------------------------------------------------------------------
| Direct display() — deterministic proof of the fast path
|--------------------------------------------------------------------------
*/

test('display() of a plain field never builds the full fields collection', function () {
    Aura::fake();
    Aura::setModel(new FastPathPlainPost);

    $post = FastPathPlainPost::create(['headline' => 'Hello']);

    FastPathPlainPost::$fullAccessorCalls = 0;

    expect($post->display('headline'))->toContain('Hello');
    expect($post->display('id'))->toBe(e($post->id));

    // Neither the input-field cell nor the id cell should build every field.
    expect(FastPathPlainPost::$fullAccessorCalls)->toBe(0);
});

test('display() of a conditional-logic field falls back to the full accessor', function () {
    Aura::fake();
    Aura::setModel(new FastPathConditionalPost);

    $post = FastPathConditionalPost::create(['secret' => 'Classified']);

    FastPathConditionalPost::$fullAccessorCalls = 0;

    expect($post->display('secret'))->toContain('Classified');

    // The conditional-logic cell must resolve through the full accessor.
    expect(FastPathConditionalPost::$fullAccessorCalls)->toBe(1);
});

test('fast path output equals the legacy full-accessor output', function () {
    Aura::fake();
    Aura::setModel(new FastPathPlainPost);

    $post = FastPathPlainPost::create(['headline' => '<b>Bold</b>']);

    // The value the full accessor would have produced for this cell.
    $legacy = $post->displayFieldValue('headline', $post->fields['headline'] ?? null);

    expect($post->display('headline'))->toBe($legacy);
});

/*
|--------------------------------------------------------------------------
| Table render — CPU budget assertions
|--------------------------------------------------------------------------
*/

test('a table of N rows with a plain column does not build the fields collection per row', function () {
    $model = new FastPathPlainPost;
    Aura::fake();
    Aura::setModel($model);

    $rowCount = 6;

    collect(range(1, $rowCount))->each(function ($i) {
        FastPathPlainPost::create(['headline' => 'Row '.$i]);
    });

    $component = livewire(Table::class, ['query' => null, 'model' => $model])->set('perPage', 100);

    // Reset the counter right before a single controlled render.
    FastPathPlainPost::$fullAccessorCalls = 0;

    $component->call('$refresh');

    // Fast path: neither the plain column nor the id column builds every field.
    expect(FastPathPlainPost::$fullAccessorCalls)->toBe(0);

    // Output is unchanged.
    $component->assertSee('Row 1');
    $component->assertSee('Row '.$rowCount);
});

test('a table of N rows with a conditional-logic column falls back once per row', function () {
    $model = new FastPathConditionalPost;
    Aura::fake();
    Aura::setModel($model);

    $rowCount = 6;

    collect(range(1, $rowCount))->each(function ($i) {
        FastPathConditionalPost::create(['secret' => 'Row '.$i]);
    });

    $component = livewire(Table::class, ['query' => null, 'model' => $model])->set('perPage', 100);

    FastPathConditionalPost::$fullAccessorCalls = 0;

    $component->call('$refresh');

    // Fallback: exactly one full-accessor build per rendered row (cached per
    // model instance), not more.
    expect(FastPathConditionalPost::$fullAccessorCalls)->toBe($rowCount);

    $component->assertSee('Row 1');
    $component->assertSee('Row '.$rowCount);
});
