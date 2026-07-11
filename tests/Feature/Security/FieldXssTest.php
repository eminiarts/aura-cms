<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Text;
use Aura\Base\Fields\Wysiwyg;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;

use function Pest\Livewire\livewire;

/**
 * Regression tests for stored XSS via unescaped field values.
 *
 * A user who can edit a resource must not be able to inject markup (e.g.
 * <script>) into a plain Text field that then executes in a viewer's
 * session. Scalar field values must be HTML-escaped end to end, while
 * fields that intentionally emit markup (Wysiwyg, Boolean, ...) keep
 * rendering their HTML raw.
 */
class XssResourceModel extends Resource
{
    public static $singularName = 'Xss Model';

    public static ?string $slug = 'xssmodel';

    public static string $type = 'XssModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Text',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'text',
                'on_index' => true,
            ],
            [
                'name' => 'Body',
                'type' => 'Aura\\Base\\Fields\\Wysiwyg',
                'slug' => 'body',
                'on_index' => true,
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Field::display escaping', function () {
    test('Text field escapes a script payload', function () {
        $result = (new Text)->display(['slug' => 'text'], '<script>alert(1)</script>', new XssResourceModel);

        expect($result)
            ->not->toContain('<script>')
            ->toBe('&lt;script&gt;alert(1)&lt;/script&gt;');
    });

    test('Text field escapes HTML attributes and quotes', function () {
        $result = (new Text)->display(['slug' => 'text'], '"><img src=x onerror=alert(1)>', new XssResourceModel);

        expect($result)
            ->not->toContain('<img')
            ->not->toContain('onerror=alert(1)>');
    });

    test('Wysiwyg field keeps its HTML raw', function () {
        $html = '<p>Hello <strong>World</strong></p>';

        $result = (new Wysiwyg)->display(['slug' => 'body'], $html, new XssResourceModel);

        expect($result)->toBe($html);
    });

    test('Boolean field keeps its icon markup raw', function () {
        $result = (new Boolean)->display(['slug' => 'active'], true, new XssResourceModel);

        expect($result)->toContain('<svg');
    });

    test('a custom field can opt into raw HTML via rawHtmlDisplay', function () {
        $field = new class extends Text
        {
            public bool $rawHtmlDisplay = true;
        };

        $result = $field->display(['slug' => 'text'], '<b>bold</b>', new XssResourceModel);

        expect($result)->toBe('<b>bold</b>');
    });
});

describe('model->display escaping', function () {
    test('scalar text is escaped and wysiwyg stays raw through model display', function () {
        Aura::fake();
        Aura::setModel(new XssResourceModel);

        $post = XssResourceModel::create([
            'type' => 'XssModel',
            'text' => '<script>alert(1)</script>',
            'body' => '<p>Trusted <em>markup</em></p>',
        ]);

        expect((string) $post->display('text'))
            ->not->toContain('<script>')
            ->toBe('&lt;script&gt;alert(1)&lt;/script&gt;');

        expect((string) $post->display('body'))->toBe('<p>Trusted <em>markup</em></p>');
    });
});

describe('table row rendering', function () {
    test('a script payload in a Text column is rendered escaped, not raw', function () {
        Aura::fake();
        Aura::setModel(new XssResourceModel);

        $post = XssResourceModel::create([
            'type' => 'XssModel',
            'text' => '<script>alert(1)</script>',
        ]);

        livewire(Table::class, ['query' => null, 'model' => $post])
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    });
});
