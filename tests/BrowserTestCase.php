<?php

namespace Aura\Base\Tests;

class BrowserTestCase extends TestCase
{
    protected bool $enableVite = true;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->app[\Illuminate\Contracts\Http\Kernel::class]
            ->prependMiddleware(Browser\Support\ParseMultipartBody::class);

        $this->serveBuiltAssets();
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
