<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotCollision;
use Aura\Base\Livewire\ComponentSlots\LivewireCollisionInspector;
use Aura\Base\Livewire\Resource\View;
use Aura\Base\Preferences\PreferenceContext;
use Aura\Base\Preferences\PreferenceDefinition;
use Aura\Base\Preferences\PreferenceManager;
use Aura\Base\Preferences\PreferenceRegistry;
use Aura\Base\Preferences\PreferenceScope;
use Aura\Base\Preferences\PreferenceValueType;
use Aura\Base\RecordLayout\DefinesRecordLayoutPanels;
use Aura\Base\RecordLayout\InvalidRecordLayoutPanel;
use Aura\Base\RecordLayout\RecordLayoutPanel;
use Aura\Base\RecordLayout\RecordLayoutPanelValidator;
use Aura\Base\RecordLayout\RecordLayoutRegion;
use Aura\Base\RecordLayout\RecordLayoutRegistry;
use Aura\Base\RecordLayout\RecordLayoutResolver;
use Aura\Base\RecordLayout\RegisteredRecordLayoutPanel;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Fixtures\RecordLayout\SecondPanel;
use Aura\Base\Tests\Fixtures\RecordLayout\TestPanel;
use Aura\Base\Tests\Fixtures\RecordLayout\ThirdPanel;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\Livewire;

class Core25InvalidInputPanel extends Component
{
    public string $inModal = '';

    public string $model = '';
}

class Core25ConflictingMountPanel extends Component
{
    public bool $inModal = false;

    public Resource $model;

    public function mount(string $model, int $inModal): void {}
}

class Core25DuplicatePanelResource extends Resource implements DefinesRecordLayoutPanels
{
    public static string $type = 'Core25DuplicatePanelResource';

    public static function recordLayoutPanels(): array
    {
        return [
            new RecordLayoutPanel('duplicate', RecordLayoutRegion::MainContent, TestPanel::class),
            new RecordLayoutPanel('duplicate', RecordLayoutRegion::RightSidebar, TestPanel::class),
        ];
    }
}

function core25Registry(): RecordLayoutRegistry
{
    $registry = new RecordLayoutRegistry(
        app(LivewireCollisionInspector::class),
        app(PreferenceRegistry::class),
        app(RecordLayoutPanelValidator::class),
    );
    app()->instance(RecordLayoutRegistry::class, $registry);
    app()->forgetInstance(RecordLayoutResolver::class);

    return $registry;
}

function core25Post(): Post
{
    $post = Post::create([
        'title' => 'Composable record',
        'content' => 'Core 25',
        'type' => Post::getType(),
        'status' => 'publish',
    ]);

    Aura::fake();
    Aura::setModel(new Post);

    return $post;
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('the default record view remains unchanged when no panels are registered', function () {
    core25Registry();
    $post = core25Post();

    Livewire::test(View::class, ['slug' => 'post', 'id' => $post->id])
        ->assertSee('Composable record')
        ->assertSeeHtml('space-x-2')
        ->assertDontSeeHtml('flex-wrap gap-4')
        ->assertDontSeeHtml('data-record-layout="page"');
});

test('multiple plugin panels render in deterministic order across stable regions', function () {
    $registry = core25Registry();
    $registry->register('zeta/activity', [
        new RecordLayoutPanel('third', RecordLayoutRegion::MainContent, ThirdPanel::class, order: 20),
    ]);
    $registry->register('alpha/summary', [
        new RecordLayoutPanel('second', RecordLayoutRegion::MainContent, SecondPanel::class, order: 10),
        new RecordLayoutPanel('first', RecordLayoutRegion::LeftSummary, TestPanel::class),
    ]);
    $post = core25Post();

    $html = Livewire::test(View::class, ['slug' => 'post', 'id' => $post->id])->html();

    expect($html)->toContain('data-record-layout="page"')
        ->and($html)->toContain('data-record-layout-region="left-summary"')
        ->and(strpos($html, SecondPanel::class))->toBeLessThan(strpos($html, ThirdPanel::class));
});

test('hidden unauthorized and preference-disabled panels fail closed', function () {
    app(PreferenceRegistry::class)->register(new PreferenceDefinition(
        key: 'record-layout.test-panel',
        type: PreferenceValueType::Boolean,
        default: true,
        scopes: [PreferenceScope::User, PreferenceScope::Team],
        resourceAware: true,
    ));

    $registry = core25Registry();
    $registry->register('acme/panels', [
        new RecordLayoutPanel('hidden', RecordLayoutRegion::MainContent, TestPanel::class, visible: false),
        new RecordLayoutPanel('denied', RecordLayoutRegion::MainContent, SecondPanel::class, ability: 'core25-denied'),
        new RecordLayoutPanel(
            'preferred',
            RecordLayoutRegion::MainContent,
            ThirdPanel::class,
            preferenceKey: 'record-layout.test-panel',
        ),
    ]);
    Gate::define('core25-denied', fn (): bool => false);

    app(PreferenceManager::class)->set(
        'record-layout.test-panel',
        false,
        PreferenceScope::User,
        new PreferenceContext(
            (string) config('app.name'),
            $this->user,
            config('aura.teams') ? $this->user->authorizedCurrentTeam() : null,
            Post::getType(),
        ),
        $this->user,
    );

    $post = core25Post();

    Livewire::test(View::class, ['slug' => 'post', 'id' => $post->id])
        ->assertDontSee(TestPanel::class)
        ->assertDontSee(SecondPanel::class)
        ->assertDontSee(ThirdPanel::class)
        ->assertDontSeeHtml('data-record-layout="page"');
});

test('invalid or missing panel components are rejected atomically', function () {
    $registry = core25Registry();

    expect(fn () => $registry->register('acme/panels', [
        new RecordLayoutPanel('valid', RecordLayoutRegion::MainContent, TestPanel::class),
        new RecordLayoutPanel('missing', RecordLayoutRegion::MainContent, 'Acme\\MissingPanel'),
    ]))->toThrow(InvalidRecordLayoutPanel::class);

    expect($registry->panelsFor(new Post))->toBe([]);

    expect(fn () => $registry->register('acme/panels', [
        new RecordLayoutPanel('bad-inputs', RecordLayoutRegion::MainContent, Core25InvalidInputPanel::class),
    ]))->toThrow(InvalidRecordLayoutPanel::class);

    expect(fn () => $registry->register('acme/panels', [
        new RecordLayoutPanel('conflicting-mount', RecordLayoutRegion::MainContent, Core25ConflictingMountPanel::class),
    ]))->toThrow(InvalidRecordLayoutPanel::class);
});

test('resource declarations use the same duplicate and preference invariants', function () {
    $duplicates = core25Registry();

    expect(fn () => $duplicates->captureBaselineState([Core25DuplicatePanelResource::class]))
        ->toThrow(InvalidArgumentException::class);
    expect($duplicates->panelsFor(new Core25DuplicatePanelResource))->toBe([]);

    $preferences = core25Registry();
    $preferences->register('acme/panels', [
        new RecordLayoutPanel(
            'missing-preference',
            RecordLayoutRegion::MainContent,
            TestPanel::class,
            preferenceKey: 'record-layout.missing',
        ),
    ]);

    expect(fn () => $preferences->captureBaselineState())
        ->toThrow(InvalidRecordLayoutPanel::class, 'preference [record-layout.missing] is not registered');
});

test('panel registration is idempotent before boot finalization and closed afterwards', function () {
    $registry = core25Registry();
    $panel = new RecordLayoutPanel('stable', RecordLayoutRegion::MainContent, TestPanel::class);

    $registry->register('acme/panels', [$panel]);
    $registry->register('acme/panels', [$panel]);

    expect($registry->panelsFor(new Post))->toHaveCount(1);

    $registry->captureBaselineState();

    expect(fn () => $registry->register('other/panels', [$panel]))
        ->toThrow(InvalidArgumentException::class);
});

test('panel transports reject even same-class claims through the core collision preflight', function () {
    $registry = core25Registry();
    $panel = new RecordLayoutPanel('claimed', RecordLayoutRegion::MainContent, TestPanel::class);
    $registered = new RegisteredRecordLayoutPanel('acme/panels', $panel);
    Livewire::component($registered->transport(), TestPanel::class);

    $registry->register('acme/panels', [$panel]);

    expect(fn () => $registry->captureBaselineState())
        ->toThrow(ComponentSlotCollision::class);
    expect($registry->panelsFor(new Post))->toBe([]);
});

test('record layout transport ownership survives a fresh registry without trusting replacements', function () {
    $panel = new RecordLayoutPanel('worker-owned', RecordLayoutRegion::MainContent, TestPanel::class);
    $registered = new RegisteredRecordLayoutPanel('acme/panels', $panel);

    $firstRegistry = core25Registry();
    $firstRegistry->register('acme/panels', [$panel]);
    $firstRegistry->captureBaselineState();

    $freshRegistry = core25Registry();
    $freshRegistry->register('acme/panels', [$panel]);
    expect(fn () => $freshRegistry->captureBaselineState())->not->toThrow(ComponentSlotCollision::class);

    Livewire::component($registered->transport(), SecondPanel::class);

    $replacedRegistry = core25Registry();
    $replacedRegistry->register('acme/panels', [$panel]);
    expect(fn () => $replacedRegistry->captureBaselineState())
        ->toThrow(ComponentSlotCollision::class, SecondPanel::class);
});

test('a failed batch preflight does not claim earlier livewire transports', function () {
    $registry = core25Registry();
    $first = new RegisteredRecordLayoutPanel(
        'acme/panels',
        new RecordLayoutPanel('first', RecordLayoutRegion::MainContent, TestPanel::class),
    );
    $claimed = new RegisteredRecordLayoutPanel(
        'acme/panels',
        new RecordLayoutPanel('claimed', RecordLayoutRegion::MainContent, SecondPanel::class),
    );
    Livewire::component($claimed->transport(), SecondPanel::class);

    $registry->register('acme/panels', [$first->panel, $claimed->panel]);

    expect(fn () => $registry->captureBaselineState())
        ->toThrow(ComponentSlotCollision::class);
    expect(fn () => app(LivewireCollisionInspector::class)->assertReservable(
        $first->transport(),
        TestPanel::class,
        static fn (?string $name): ?string => null,
    ))->not->toThrow(ComponentSlotCollision::class);
});

test('shared eager loads and preference reads stay bounded as panels increase', function () {
    $registry = core25Registry();
    $panels = [];

    foreach (range(1, 10) as $index) {
        $panels[] = new RecordLayoutPanel(
            'panel-'.$index,
            RecordLayoutRegion::MainContent,
            TestPanel::class,
            eagerLoad: ['user'],
        );
    }

    $registry->register('acme/panels', $panels);
    $post = core25Post()->fresh();
    DB::flushQueryLog();
    DB::enableQueryLog();

    $layout = app(RecordLayoutResolver::class)->resolve($post);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($layout->panels(RecordLayoutRegion::MainContent))->toHaveCount(10)
        ->and($post->relationLoaded('user'))->toBeTrue()
        ->and(count($queries))->toBeLessThanOrEqual(7);
});

test('a shared preference is resolved once for multiple panels', function () {
    app(PreferenceRegistry::class)->register(new PreferenceDefinition(
        key: 'record-layout.shared-preference',
        type: PreferenceValueType::Boolean,
        default: true,
        scopes: [PreferenceScope::User, PreferenceScope::Team],
        resourceAware: true,
    ));
    $registry = core25Registry();
    $panels = [];

    foreach (range(1, 10) as $index) {
        $panels[] = new RecordLayoutPanel(
            'preferred-'.$index,
            RecordLayoutRegion::MainContent,
            TestPanel::class,
            preferenceKey: 'record-layout.shared-preference',
        );
    }

    $registry->register('acme/panels', $panels);
    $post = core25Post()->fresh();
    DB::flushQueryLog();
    DB::enableQueryLog();

    $layout = app(RecordLayoutResolver::class)->resolve($post);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($layout->panels(RecordLayoutRegion::MainContent))->toHaveCount(10)
        ->and(count($queries))->toBeLessThanOrEqual(35);
});

test('modal record views pass their context to panels', function () {
    $registry = core25Registry();
    $registry->register('acme/panels', [
        new RecordLayoutPanel('modal', RecordLayoutRegion::RightSidebar, TestPanel::class),
    ]);
    $post = core25Post();

    Livewire::test(View::class, ['slug' => 'post', 'id' => $post->id, 'inModal' => true])
        ->assertSeeHtml('data-record-layout="modal"')
        ->assertSeeHtml('data-modal="true"');
});

test('custom-table records resolve the same declarative panel contract', function () {
    $registry = core25Registry();
    $registry->register('acme/panels', [
        new RecordLayoutPanel(
            'custom-table',
            RecordLayoutRegion::MainContent,
            TestPanel::class,
            resources: [User::class],
        ),
    ]);

    Aura::fake();
    Aura::setModel(new User);

    expect($this->user->usesCustomTable())->toBeTrue();

    Livewire::test(View::class, ['slug' => 'user', 'id' => $this->user->getKey()])
        ->assertSee(TestPanel::class)
        ->assertSeeHtml('data-record-layout="page"');
});
