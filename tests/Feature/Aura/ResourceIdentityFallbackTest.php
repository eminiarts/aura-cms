<?php

use Aura\Base\Resource;

class ResourceWithoutSlugOrName extends Resource
{
    public static string $type = 'ResourceWithoutSlugOrName';
}

class ResourceWithCustomIcon extends Resource
{
    protected static ?string $icon = '<svg id="custom-icon"></svg>';
}

test('a resource without $slug and $name falls back to the class basename', function () {
    expect(ResourceWithoutSlugOrName::getSlug())->toBe('resourcewithoutslugorname');
});

test('an explicit $name still wins over the class basename', function () {
    $resource = new class extends Resource
    {
        protected static ?string $name = 'My Fancy Thing';
    };

    expect($resource::getSlug())->toBe('my-fancy-thing');
});

test('getIcon() returns the declared $icon', function () {
    expect((new ResourceWithCustomIcon)->getIcon())->toBe('<svg id="custom-icon"></svg>');
});

test('getIcon() falls back to the default icon when $icon is not set', function () {
    expect((new ResourceWithoutSlugOrName)->getIcon())->toContain('<svg class="w-5 h-5"');
});
