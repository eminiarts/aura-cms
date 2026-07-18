<?php

namespace Aura\Base\Tests;

use Aura\Base\Facades\Aura;
use Illuminate\Contracts\Http\Kernel;

class BrowserTestCase extends TestCase
{
    protected bool $enableVite = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app[Kernel::class]
            ->prependMiddleware(Browser\Support\ParseMultipartBody::class);

        // Browser tests exercise real resource pages; boot-time route
        // generation has already run, so add this resource's routes now.
        // Re-capture the baseline afterwards: Queue::after triggers
        // Aura::flushState(), which would otherwise drop the registration
        // as soon as any sync job (e.g. thumbnail generation) runs.
        Aura::registerResources([Resources\GalleryPage::class]);
        Aura::registerRoutes('gallery-page');
        Aura::captureBaselineState();

        $this->serveBuiltAssets();
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        // Real browsers make many HTTP requests; the session must survive
        // between them, which the array driver cannot do.
        $app['config']->set('session.driver', 'file');

        // Livewire forces this disk name for temporary uploads while
        // runningUnitTests(), but only component tests fake it — real
        // browser uploads need it to exist.
        $app['config']->set('filesystems.disks.tmp-for-tests', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/disks/tmp-for-tests'),
        ]);

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
