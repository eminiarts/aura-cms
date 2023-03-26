<?php

use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Taxonomies\Tag;

uses()->group('current');

test('Aura::getTaxonomies()', function () {
    $taxonomies = Aura::getTaxonomies();

    expect($taxonomies)->toBeArray();
    expect($taxonomies)->toHaveCount(2);
    expect($taxonomies)->toContain('Eminiarts\\Aura\\Taxonomies\\Tag');
    expect($taxonomies)->toContain('Eminiarts\\Aura\\Taxonomies\\Category');
});

test('taxonomy folder in app gets loaded correctly', function () {
    // Todo: add test
})->todo();

test('Aura::findTaxonomyBySlug()', function () {
    $tag = Aura::findTaxonomyBySlug('Tag');

    expect($tag)->toBeInstanceOf(Tag::class);
});
