<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;
use Illuminate\Support\Facades\URL;

class NavigationCacheLateResource extends Resource
{
    public static ?string $slug = 'navigation-cache-late';

    public static string $type = 'NavigationCacheLate';
}

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

test('registering a resource invalidates the cached navigation', function () {
    $slugs = fn () => Aura::navigation()->flatten(1)->pluck('slug');

    expect($slugs())->not->toContain('navigation-cache-late');

    Aura::registerRoutes('navigation-cache-late', NavigationCacheLateResource::class);
    app('router')->getRoutes()->refreshNameLookups();

    Aura::registerResources([NavigationCacheLateResource::class]);

    expect($slugs())->toContain('navigation-cache-late');
});

test('navigation cached for one host does not leak its urls to another host', function () {
    $optionRoute = fn () => Aura::navigation()->flatten(1)->firstWhere('slug', 'option')['route'];

    URL::forceRootUrl('http://localhost');
    expect($optionRoute())->toStartWith('http://localhost/');

    URL::forceRootUrl('http://127.0.0.1:8000');
    expect($optionRoute())->toStartWith('http://127.0.0.1:8000/');
});
