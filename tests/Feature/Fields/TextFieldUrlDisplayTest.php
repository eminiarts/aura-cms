<?php

use Aura\Base\Fields\Text;
use Aura\Base\Resource;

class TextUrlDisplayModel extends Resource
{
    public static ?string $slug = 'text-url-display';

    public static string $type = 'TextUrlDisplayModel';

    public static function getFields(): array
    {
        return [
            ['name' => 'Website', 'type' => 'Aura\\Base\\Fields\\Text', 'slug' => 'website'],
        ];
    }
}

test('a bare site URL renders as a link showing only the host', function () {
    $html = (new Text)->display(['slug' => 'website'], 'https://www.atlascomponents.de/', new TextUrlDisplayModel);

    expect($html)
        ->toContain("href='https://www.atlascomponents.de/'")
        ->toContain('>atlascomponents.de</a>')
        ->toContain('rel=\'noopener noreferrer\'');
});

test('a URL with a path, query or fragment keeps them and drops only the scheme', function () {
    $html = (new Text)->display(['slug' => 'website'], 'https://www.atlascomponents.de/about?ref=1', new TextUrlDisplayModel);

    expect($html)
        ->toContain("href='https://www.atlascomponents.de/about?ref=1'")
        ->toContain("title='https://www.atlascomponents.de/about?ref=1'")
        ->toContain('>www.atlascomponents.de/about?ref=1</a>');

    expect((new Text)->display(['slug' => 'website'], 'https://example.com/#pricing', new TextUrlDisplayModel))
        ->toContain('>example.com/#pricing</a>');
});

test('a long URL is shortened in the label but kept whole in href and title', function () {
    $url = 'https://example.com/'.str_repeat('segment/', 10);
    $html = (new Text)->display(['slug' => 'website'], $url, new TextUrlDisplayModel);

    expect($html)
        ->toContain("href='{$url}'")
        ->toContain("title='{$url}'")
        ->toContain('...</a>');
});

test('non-http schemes and plain text keep the escaped scalar path', function () {
    expect((new Text)->display(['slug' => 'website'], 'javascript:alert(1)', new TextUrlDisplayModel))->toBe('javascript:alert(1)')
        ->and((new Text)->display(['slug' => 'website'], 'Visit https://example.com today', new TextUrlDisplayModel))->toBe('Visit https://example.com today')
        ->and((new Text)->display(['slug' => 'website'], '<b>x</b>', new TextUrlDisplayModel))->toBe('&lt;b&gt;x&lt;/b&gt;');
});

test('a URL with markup in it is escaped inside the link', function () {
    $html = (new Text)->display(['slug' => 'website'], 'https://example.com/"><script>alert(1)</script>', new TextUrlDisplayModel);

    expect($html)->not->toContain('<script>');
});
