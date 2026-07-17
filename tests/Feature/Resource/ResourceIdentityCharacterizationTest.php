<?php

/**
 * Characterization tests for the naming / classification / title helpers in
 * AuraModelConfig (design §1 ResourceIdentity, §5 step 4). Pins the EXACT current
 * derivations and fallbacks — including the surprising ones — before extraction.
 *
 * The file uses braced namespaces so one fixture can live under the `App\` root,
 * which is the only way to exercise isAppResource()'s `Str::startsWith(..., 'App')`
 * classification against a real class name.
 */

namespace App\Aura\Resources {
    use Aura\Base\Resource;

    class CharacterizationAppResource extends Resource
    {
        public static ?string $slug = 'char-app';

        public static string $type = 'CharApp';
    }
}

namespace {
    use App\Aura\Resources\CharacterizationAppResource;
    use Aura\Base\Resource;

    // type + slug + name all set explicitly.
    class IdentityFullResource extends Resource
    {
        public static ?string $name = 'Gadget';

        public static ?string $slug = 'gadget';

        public static string $type = 'Gadget';
    }

    // No name, no type override — only slug set (so getSlug() avoids Str::slug(null)).
    class IdentityDefaultsResource extends Resource
    {
        public static ?string $slug = 'identity-defaults';
    }

    // type/slug/name deliberately diverge so the two plural sources disagree.
    class IdentityPluralDivergeResource extends Resource
    {
        public static ?string $name = 'Item';

        public static ?string $slug = 'item';

        public static string $type = 'Category';
    }

    // Name set but slug NOT set: getSlug() derives the slug from the name.
    class IdentitySlugFromNameResource extends Resource
    {
        public static ?string $name = 'Blog Post';
    }

    beforeEach(function () {
        $this->actingAs($this->user = createSuperAdmin());
    });

    test('getType returns the default "Resource" when not overridden', function () {
        expect(IdentityDefaultsResource::getType())->toBe('Resource');
    });

    test('getType returns the configured type', function () {
        expect(IdentityFullResource::getType())->toBe('Gadget');
    });

    test('getName defaults to null and returns the configured name', function () {
        expect(IdentityDefaultsResource::getName())->toBeNull()
            ->and(IdentityFullResource::getName())->toBe('Gadget');
    });

    test('getSlug returns the explicit slug when set', function () {
        expect(IdentityFullResource::getSlug())->toBe('gadget');
    });

    test('getSlug derives the slug from the name when no slug is set', function () {
        expect(IdentitySlugFromNameResource::getSlug())->toBe('blog-post');
    });

    test('getPluralName is derived from the TYPE, not the name or slug', function () {
        // Static getPluralName() uses str($type)->plural().
        expect(IdentityFullResource::getPluralName())->toBe('Gadgets')
            ->and(IdentityDefaultsResource::getPluralName())->toBe('Resources')
            ->and(IdentityPluralDivergeResource::getPluralName())->toBe('Categories');
    });

    test('singularName is derived from the static $slug property (Str::title), not the name', function () {
        // Surprising: singularName() reads the raw static::$slug property directly —
        // it does NOT consult getSlug() nor the name.
        expect((new IdentityFullResource)->singularName())->toBe('Gadget')
            ->and((new IdentityPluralDivergeResource)->singularName())->toBe('Item');
    });

    test('pluralName (instance) is derived from singularName, diverging from getPluralName', function () {
        // Surprising: the instance pluralName() and the static getPluralName() use
        // different sources — singularName()->plural() vs str($type)->plural() — so
        // they can disagree for the same resource.
        expect((new IdentityPluralDivergeResource)->pluralName())->toBe('Items')
            ->and(IdentityPluralDivergeResource::getPluralName())->toBe('Categories');
    });

    test('title returns null for an unsaved model', function () {
        expect((new IdentityFullResource)->title())->toBeNull();
    });

    test('title returns the "Type (#id)" form for a saved model', function () {
        $model = IdentityFullResource::create(['type' => 'Gadget']);

        expect($model->title())->toBe("Gadget (#{$model->id})");
    });

    test('isVendorResource is true (isAppResource false) for a non-App class', function () {
        $model = new IdentityFullResource;

        expect($model->isAppResource())->toBeFalse()
            ->and($model->isVendorResource())->toBeTrue();
    });

    test('isAppResource is true (isVendorResource false) for an App\\ class', function () {
        $model = new CharacterizationAppResource;

        expect($model->isAppResource())->toBeTrue()
            ->and($model->isVendorResource())->toBeFalse();
    });
}
