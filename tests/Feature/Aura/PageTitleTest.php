<?php

use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

test('the page title is escaped exactly once', function (string $template) {
    $html = Blade::render($template.'<x-aura::layout.app><p>Content</p></x-aura::layout.app>', ['title' => 'Q&A </title><script>alert(1)</script>']);

    expect($html)->toContain('<title>Q&amp;A &lt;/title&gt;&lt;script&gt;alert(1)&lt;/script&gt; • ')
        ->not->toContain('&amp;amp;')
        ->not->toContain('<script>alert(1)</script>');
})->with([
    'inline section' => "@section('title', \$title)",
    'section block' => "@section('title'){{ \$title }}@endsection",
]);

test('a page without title shows only the app name', function () {
    $html = Blade::render('<x-aura::layout.app><p>Content</p></x-aura::layout.app>');

    expect($html)->toMatch('#<title>[^•<]+</title>#');
});
