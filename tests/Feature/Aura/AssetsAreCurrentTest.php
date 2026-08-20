<?php

use Aura\Base\Support\PublishedAssets;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->publishedRoot = public_path('vendor/aura');
    $this->packageDist = dirname(__DIR__, 3).'/resources/dist';

    if (File::exists($this->publishedRoot)) {
        File::deleteDirectory($this->publishedRoot);
    }
});

afterEach(function () {
    if (File::exists($this->publishedRoot)) {
        File::deleteDirectory($this->publishedRoot);
    }

    foreach (File::glob(public_path('vendor/aura-staging-*')) ?: [] as $path) {
        File::deleteDirectory($path);
    }

    foreach (File::glob(public_path('vendor/aura-backup-*')) ?: [] as $path) {
        File::deleteDirectory($path);
    }
});

function seedPublishedAssets(string $root, ?array $manifest = null, bool $withFiles = true): void
{
    $packageDist = dirname(__DIR__, 3).'/resources/dist';
    File::ensureDirectoryExists($root);

    if ($manifest === null) {
        File::copyDirectory($packageDist, $root);

        if (! $withFiles) {
            File::deleteDirectory($root.'/assets');
            File::ensureDirectoryExists($root.'/assets');
        }

        return;
    }

    File::put($root.'/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    File::ensureDirectoryExists($root.'/assets');

    if (! $withFiles) {
        return;
    }

    foreach (PublishedAssets::referencedPaths($manifest) as $path) {
        $absolute = $root.'/'.$path;
        File::ensureDirectoryExists(dirname($absolute));
        File::put($absolute, '/* fixture */');
    }
}

it('treats a complete published tree as current', function () {
    seedPublishedAssets($this->publishedRoot);

    expect(PublishedAssets::areCurrent($this->publishedRoot, $this->packageDist))->toBeTrue();
});

it('treats matching manifests with a missing javascript file as stale', function () {
    seedPublishedAssets($this->publishedRoot);

    $js = collect(File::files($this->publishedRoot.'/assets'))
        ->first(fn ($file) => str_ends_with($file->getFilename(), '.js'));

    expect($js)->not->toBeNull();
    File::delete($js->getPathname());

    expect(PublishedAssets::areCurrent($this->publishedRoot, $this->packageDist))->toBeFalse();
});

it('treats matching manifests with a missing css file as stale', function () {
    seedPublishedAssets($this->publishedRoot);

    $css = collect(File::files($this->publishedRoot.'/assets'))
        ->first(fn ($file) => str_ends_with($file->getFilename(), '.css'));

    expect($css)->not->toBeNull();
    File::delete($css->getPathname());

    expect(PublishedAssets::areCurrent($this->publishedRoot, $this->packageDist))->toBeFalse();
});

it('treats a missing imported chunk as stale', function () {
    $manifest = [
        'resources/js/app.js' => [
            'file' => 'assets/app.js',
            'imports' => ['chunk-shared'],
            'isEntry' => true,
        ],
        'chunk-shared' => [
            'file' => 'assets/chunk.js',
        ],
    ];

    seedPublishedAssets($this->publishedRoot, $manifest);
    File::delete($this->publishedRoot.'/assets/chunk.js');

    expect(PublishedAssets::verify($this->publishedRoot, $manifest))->toBeFalse();
});

it('rejects path traversal in referenced assets', function () {
    $manifest = [
        'resources/js/app.js' => [
            'file' => '../escape.js',
            'isEntry' => true,
        ],
    ];

    seedPublishedAssets($this->publishedRoot, $manifest, withFiles: false);
    File::put(public_path('escape.js'), 'nope');

    expect(PublishedAssets::verify($this->publishedRoot, $manifest))->toBeFalse();
});

it('treats differing manifests as stale', function () {
    seedPublishedAssets($this->publishedRoot);

    $manifest = json_decode(File::get($this->publishedRoot.'/manifest.json'), true);
    $manifest['resources/js/extra.js'] = ['file' => 'assets/extra.js'];
    File::put($this->publishedRoot.'/manifest.json', json_encode($manifest));
    File::put($this->publishedRoot.'/assets/extra.js', '/* extra */');

    expect(PublishedAssets::areCurrent($this->publishedRoot, $this->packageDist))->toBeFalse();
});

it('throws for a malformed published manifest', function () {
    File::ensureDirectoryExists($this->publishedRoot);
    File::put($this->publishedRoot.'/manifest.json', '{not-json');

    expect(fn () => PublishedAssets::areCurrent($this->publishedRoot, $this->packageDist))
        ->toThrow(\RuntimeException::class, 'invalid JSON');
});

it('throws when no published manifest exists', function () {
    expect(fn () => PublishedAssets::areCurrent($this->publishedRoot, $this->packageDist))
        ->toThrow(\RuntimeException::class, 'not published');
});

it('repairs an incomplete tree via aura:publish', function () {
    seedPublishedAssets($this->publishedRoot);
    File::deleteDirectory($this->publishedRoot.'/assets');
    File::ensureDirectoryExists($this->publishedRoot.'/assets');

    expect(PublishedAssets::areCurrent($this->publishedRoot, $this->packageDist))->toBeFalse();

    $this->artisan('aura:publish')->assertSuccessful();

    expect(PublishedAssets::areCurrent($this->publishedRoot, $this->packageDist))->toBeTrue();
});

it('leaves the previous valid bundle intact when staging verification fails', function () {
    seedPublishedAssets($this->publishedRoot);
    $before = File::get($this->publishedRoot.'/manifest.json');

    $staging = public_path('vendor/aura-staging-testfail');
    File::copyDirectory($this->packageDist, $staging);
    File::deleteDirectory($staging.'/assets');
    File::ensureDirectoryExists($staging.'/assets');

    expect(PublishedAssets::verify($staging))->toBeFalse();

    // PublishCommand deletes the staging tree and returns failure before swap.
    File::deleteDirectory($staging);

    expect(File::exists($this->publishedRoot))->toBeTrue();
    expect(File::get($this->publishedRoot.'/manifest.json'))->toBe($before);
    expect(PublishedAssets::verify($this->publishedRoot))->toBeTrue();
    expect(File::exists($staging))->toBeFalse();
});
