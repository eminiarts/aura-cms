<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Boolean;
use Aura\Base\Fields\Json;
use Aura\Base\Fields\Text;
use Aura\Base\Fields\Wysiwyg;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Livewire\Resource\View as ResourceView;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

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
            [
                'name' => 'Nested',
                'type' => Json::class,
                'slug' => 'nested',
                'on_index' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Legacy date',
                'type' => 'Aura\\Base\\Fields\\Date',
                'slug' => 'legacy_date',
                'format' => 'Y-m-d',
            ],
            [
                'name' => 'Legacy datetime',
                'type' => 'Aura\\Base\\Fields\\Datetime',
                'slug' => 'legacy_datetime',
                'format' => 'Y-m-d H:i:s',
                'input_timezone' => 'UTC',
                'display_timezone' => 'UTC',
                'storage_timezone' => 'UTC',
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

        expect((string) $result)->toBe($html);
    });

    test('Wysiwyg::sanitize strips dangerous handlers but keeps safe formatting', function () {
        $clean = Wysiwyg::sanitize('<img src=x onerror=alert(1)><p>Hello <strong>World</strong></p>');

        expect($clean)
            ->not->toContain('onerror')
            ->toContain('<p>Hello <strong>World</strong></p>');
    });

    test('Boolean field keeps its icon markup raw', function () {
        $result = (new Boolean)->display(['slug' => 'active'], true, new XssResourceModel);

        expect((string) $result)->toContain('<svg');
    });

    test('a boolean flag cannot make a plain string trusted HTML', function () {
        $field = new class extends Text
        {
            public bool $rawHtmlDisplay = true;
        };

        $result = $field->displayValue('<b>bold</b>', ['slug' => 'text'], new XssResourceModel);

        expect((string) $result)->toBe('&lt;b&gt;bold&lt;/b&gt;');
    });

    test('a custom field must return Htmlable to opt into trusted markup', function () {
        $field = new class extends Text
        {
            public function display($field, $value, $model): Htmlable
            {
                return new HtmlString('<b>trusted</b>');
            }
        };

        $result = $field->displayValue('ignored', ['slug' => 'text'], new XssResourceModel);

        expect($result)->toBeInstanceOf(Htmlable::class)
            ->and((string) $result)->toBe('<b>trusted</b>');
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

describe('wysiwyg edit view rendering', function () {
    test('a stored script payload is sanitized before the edit view renders it raw', function () {
        Aura::fake();
        Aura::registerResources([XssResourceModel::class]);
        Aura::setModel(new XssResourceModel);

        $post = XssResourceModel::create([
            'type' => 'XssModel',
            'text' => 'ok',
            'body' => '<img src=x onerror=alert(document.cookie)><p>Hello</p>',
        ]);

        livewire(Edit::class, ['slug' => 'xssmodel', 'id' => $post->id])
            ->assertDontSee('onerror=alert', false)
            ->assertSee('<p>Hello</p>', false);
    });
});

describe('temporal picker edit rendering', function () {
    test('legacy date and datetime payloads are data rather than Alpine source', function () {
        Aura::fake();
        Aura::registerResources([XssResourceModel::class]);
        Aura::setModel(new XssResourceModel);

        $datePayload = "', [(window.__auraXss=1)]: '";
        $datetimePayload = "Quotes '\"; </script>; &quot; &amp;; \\\\; Zürich 雪; {\"nested\":[\"</script>\",\"'\"]}";
        $post = XssResourceModel::create([
            'type' => 'XssModel',
            'text' => 'ok',
            'legacy_date' => '2026-08-09',
            'legacy_datetime' => '2026-08-09 12:30:00',
        ]);

        DB::table('meta')
            ->where('metable_id', $post->id)
            ->where('key', 'legacy_date')
            ->update(['value' => $datePayload]);
        DB::table('meta')
            ->where('metable_id', $post->id)
            ->where('key', 'legacy_datetime')
            ->update(['value' => $datetimePayload]);

        $html = livewire(Edit::class, ['slug' => 'xssmodel', 'id' => $post->id])->html();
        $document = new DOMDocument;
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);
        $xpath = new DOMXPath($document);

        foreach (['legacy_date' => $datePayload, 'legacy_datetime' => $datetimePayload] as $slug => $payload) {
            $picker = $xpath->query('//*[@data-aura-datetime-picker="'.$slug.'"]')->item(0);

            expect($picker)->not->toBeNull();

            $alpineData = $picker->getAttribute('x-data');
            $alpineInit = $picker->getAttribute('x-init');

            expect($alpineData)
                ->toBe('auraDatetimePicker')
                ->not->toContain($payload)
                ->not->toContain('</script>')
                ->and($alpineInit)
                ->toBe('')
                ->not->toContain($payload)
                ->not->toContain('window.__auraXss');

            expect(json_decode($picker->getAttribute('data-picker-options'), true, flags: JSON_THROW_ON_ERROR)['defaultDate'])
                ->toBe($payload);
        }
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

    test('nested stored payloads are escaped on index view and export surfaces', function () {
        Aura::fake();
        Aura::setModel(new XssResourceModel);

        $payload = [
            'safe',
            ['<img src=x onerror=alert(1)>', '<script>alert(2)</script>'],
        ];
        $post = XssResourceModel::create([
            'type' => 'XssModel',
            'text' => 'safe',
            'nested' => $payload,
        ]);

        livewire(Table::class, ['query' => null, 'model' => $post])
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertDontSee('<script>alert(2)</script>', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false);

        livewire(ResourceView::class, ['slug' => 'xssmodel', 'id' => $post->id])
            ->assertDontSee('<img src=x onerror=alert(1)>', false)
            ->assertDontSee('<script>alert(2)</script>', false)
            ->assertSee('&lt;script&gt;alert(2)&lt;\/script&gt;', false);

        $export = (string) $post->exportFieldValue('nested');

        expect($export)
            ->not->toContain('<img')
            ->not->toContain('<script>')
            ->toContain('&lt;img src=x onerror=alert(1)&gt;')
            ->toContain('&lt;script&gt;alert(2)&lt;\/script&gt;');
    });
});
