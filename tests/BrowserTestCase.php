<?php

namespace Aura\Base\Tests;

use Aura\Base\ConditionalLogic;
use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Image;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Services\EmbeddedResourceIncarnationGuard;
use Aura\Base\Tests\Fixtures\ComponentSlots\BrowserGlobalSearch;
use Aura\Base\Tests\Fixtures\ComponentSlots\BrowserMediaManager;
use Aura\Base\Tests\Resources\GalleryPage;
use Aura\Base\Tests\Resources\KanbanBoard;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

class BrowserTestCase extends TestCase
{
    protected bool $enableVite = true;

    protected function setUp(): void
    {
        parent::setUp();

        // Process-static field/condition caches survive both the per-test app
        // refresh and RefreshDatabase. The Feature suite clears them in its
        // afterEach (via Aura::flushState); the browser suite has no such hook,
        // so a stale entry leaks across tests. This bites hardest for
        // conditional_logic: shouldDisplayField() memoizes visibility keyed by
        // Auth::id(), and RefreshDatabase reuses ids — so a field a prior test
        // hid for a non-Global-Admin at id N stays hidden for THIS test's
        // Global Admin who happens to reuse id N. Clear them up front.
        ConditionalLogic::clearConditionsCache();
        Resource::flushFieldCache();

        $this->app[Kernel::class]
            ->prependMiddleware(Browser\Support\ParseMultipartBody::class);

        // Browser tests exercise real resource pages; boot-time route
        // generation has already run, so add this resource's routes now.
        // Re-capture the baseline afterwards: Queue::after triggers
        // Aura::flushState(), which would otherwise drop the registration
        // as soon as any sync job (e.g. thumbnail generation) runs.
        Aura::registerResources([
            Resources\EmbeddedComponentPage::class,
            GalleryPage::class,
            KanbanBoard::class,
        ]);
        Aura::registerRoutes('embedded-component-page');
        Aura::registerRoutes('gallery-page');
        Aura::registerRoutes('kanban-board');
        Aura::captureBaselineState();
        $this->registerComponentSlotAliasesRoute();

        Livewire::component(
            'aura-tests.embedded-field',
            Browser\Support\EmbeddedFieldComponent::class,
        );
        app(EmbeddedResourceIncarnationGuard::class)->install(Resources\EmbeddedComponentPage::class);

        $this->serveBuiltAssets();
    }

    public function refreshDatabase(): void
    {
        $this->baseRefreshDatabase();
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        // Real browsers make many HTTP requests; the session must survive
        // between them, which the array driver cannot do.
        $app['config']->set('session.driver', 'file');

        // A published Aura app points the auth provider at the Aura user model.
        // Feature tests lean on actingAs() (an in-memory instance), but the real
        // login form authenticates through the guard's provider, which then
        // resolves the user from the session on every later request. Without this
        // the guard would hand back a bare Illuminate\Foundation\Auth\User that
        // lacks Aura's methods (isSuperAdmin, permissions, …).
        $app['config']->set('auth.providers.users.model', User::class);

        // Livewire forces this disk name for temporary uploads while
        // runningUnitTests(), but only component tests fake it — real
        // browser uploads need it to exist.
        $app['config']->set('filesystems.disks.tmp-for-tests', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/tmp-for-tests'),
        ]);

        if (filter_var(env('AURA_BROWSER_SLOT_REPLACEMENTS', false), FILTER_VALIDATE_BOOL)) {
            $app['config']->set('aura.component-slots.global-search', BrowserGlobalSearch::class);
            $app['config']->set('aura.component-slots.media-manager', BrowserMediaManager::class);
        }

        if (is_numeric(env('AURA_BROWSER_SELECTION_TTL'))) {
            $app['config']->set('aura.media.security.selection_ttl', (int) env('AURA_BROWSER_SELECTION_TTL'));
        }
    }

    private function registerComponentSlotAliasesRoute(): void
    {
        Route::get('/__aura-component-slot-aliases', function () {
            $actor = auth()->user();

            abort_unless($actor instanceof User, 403);

            $arguments = function (string $ownerComponentId) use ($actor): array {
                $ownerToken = app(MediaOwnerTokenBroker::class)->issue(
                    ownerComponentId: $ownerComponentId,
                    modelClass: GalleryPage::class,
                    modelKey: null,
                    action: 'create',
                    slug: 'gallery',
                    fieldType: Image::class,
                    actor: $actor,
                );

                return [
                    'model' => GalleryPage::class,
                    'slug' => 'gallery',
                    'selected' => [],
                    'ownerToken' => $ownerToken,
                    'modalAttributes' => [
                        'persistent' => false,
                        'modalClasses' => 'max-w-7xl',
                        'slideOver' => false,
                    ],
                ];
            };

            return Blade::render(<<<'BLADE'
                <x-aura::layout.app>
                    <div data-component-slot-aliases>
                        <section data-slot-alias="global-colon">
                            @livewire('aura::global-search', [], key('global-colon'))
                        </section>
                        <section data-slot-alias="global-dot">
                            @livewire('aura.base.livewire.global-search', [], key('global-dot'))
                        </section>
                        <section data-slot-alias="media-colon">
                            @livewire('aura::media-manager', $mediaColonArguments, key('media-colon'))
                        </section>
                        <section data-slot-alias="media-dot">
                            @livewire('aura.base.livewire.media-manager', $mediaDotArguments, key('media-dot'))
                        </section>
                    </div>
                </x-aura::layout.app>
                BLADE, [
                'mediaColonArguments' => $arguments('browser-media-colon-owner'),
                'mediaDotArguments' => $arguments('browser-media-dot-owner'),
            ], deleteCachedView: true);
        });
    }

    /**
     * Mirror what `artisan vendor:publish --tag=aura-assets` does, via
     * symlinks into the isolated testbench public path, so the browser
     * gets the package's real CSS/JS.
     */
    private function serveBuiltAssets(): void
    {
        $base = dirname(__DIR__).'/resources';
        $target = public_path('vendor/aura');

        if (is_dir($target)) {
            return;
        }

        @mkdir($target, 0755, true);

        @symlink($base.'/dist/assets', $target.'/assets');
        @symlink($base.'/dist/manifest.json', $target.'/manifest.json');
        @symlink($base.'/libs', $target.'/libs');
        @symlink($base.'/public', $target.'/public');
    }
}
