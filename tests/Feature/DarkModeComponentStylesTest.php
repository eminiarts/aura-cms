<?php

use Aura\Base\Livewire\PluginsPage;
use Aura\Base\Livewire\UserTeams;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\Finder\Finder;

use function Pest\Livewire\livewire;

// Dark mode and error states (IVA-1518): every field takes its colours and states from .aura-field,
// chips, toggles, tabs and alerts use component classes with a .dark rule, and the markup carries
// no stray attributes.

function darkModeBuiltCss(): string
{
    $dist = dirname(__DIR__, 2).'/resources/dist';
    $manifest = json_decode(file_get_contents($dist.'/manifest.json'), true);

    return file_get_contents($dist.'/'.$manifest['resources/css/app.css']['file']);
}

function darkModeView(string $path): string
{
    return file_get_contents(dirname(__DIR__, 2).'/resources/views/'.$path);
}

/**
 * The Blade template and data that render a resource field's edit view (as the Text field tests do).
 *
 * @return array{0: string, 1: array<string, mixed>}
 */
function darkModeFieldBlade(array $field): array
{
    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    return [
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []],
    ];
}

/** The opening tag of the first <$tag> element in $html. */
function darkModeTag(string $html, string $tag): string
{
    preg_match('/<'.$tag.'\b[^>]*>/s', $html, $match);

    return $match[0] ?? '';
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('field markup', function () {
    test('the text field input has one id and no stray error attribute', function () {
        $html = (string) $this->withViewErrors([])->blade(...darkModeFieldBlade([
            'name' => 'Title', 'type' => 'Aura\\Base\\Fields\\Text', 'validation' => '', 'conditional_logic' => [], 'slug' => 'title',
        ]));

        $input = darkModeTag($html, 'input');

        expect(preg_match_all('/\sid="/', $input))->toBe(1)
            ->and($input)->toContain('id="resource-field-title"')
            ->and($input)->not->toMatch('/\serror="/')
            ->and($input)->not->toContain('aria-invalid');
    });

    test('the textarea field has no stray error attribute', function () {
        $html = (string) $this->withViewErrors([])->blade(...darkModeFieldBlade([
            'name' => 'Body', 'type' => 'Aura\\Base\\Fields\\Textarea', 'validation' => '', 'conditional_logic' => [], 'slug' => 'body',
        ]));

        expect(darkModeTag($html, 'textarea'))->not->toMatch('/\serror="/')
            ->and(darkModeTag($html, 'textarea'))->toContain('aura-field');
    });

    test('a field with a validation error gets the danger ring hooks and the shared message style', function (string $type, string $tag) {
        $html = (string) $this->withViewErrors(['form.fields.title' => 'The title field is required.'])->blade(...darkModeFieldBlade([
            'name' => 'Title', 'type' => $type, 'validation' => 'required', 'conditional_logic' => [], 'slug' => 'title',
        ]));

        expect($html)->toContain('aura-field-invalid')
            ->and(darkModeTag($html, $tag))->toContain('aria-invalid="true"')
            ->and($html)->toMatch('/<span class="aura-field-error[^"]*">The title field is required\.<\/span>/')
            ->and($html)->not->toContain('text-red-500');
    })->with([
        'text' => ['Aura\\Base\\Fields\\Text', 'input'],
        'textarea' => ['Aura\\Base\\Fields\\Textarea', 'textarea'],
    ]);

    test('input components consume the error prop instead of printing it', function (string $blade, string $tag) {
        $clean = (string) $this->withViewErrors([])->blade($blade);
        $failing = (string) $this->withViewErrors(['slug' => 'Invalid'])->blade($blade);

        expect(darkModeTag($clean, $tag))->not->toMatch('/\serror="/')
            ->and(darkModeTag($clean, $tag))->not->toContain('aria-invalid')
            ->and(darkModeTag($failing, $tag))->toContain('aria-invalid="true"');
    })->with([
        'input.text' => ['<x-aura::input.text error="slug" />', 'input'],
        'input.textarea' => ['<x-aura::input.textarea error="slug"></x-aura::input.textarea>', 'textarea'],
        'input.select' => ['<x-aura::input.select name="slug" error="slug" :options="[\'a\' => \'A\']" />', 'select'],
    ]);

    test('input/wrapper prints the same message style as fields/wrapper', function () {
        $html = (string) $this->withViewErrors(['slug' => 'Invalid slug'])->blade(
            '<x-aura::input.wrapper label="Slug" error="slug"><x-aura::input.text error="slug" /></x-aura::input.wrapper>'
        );

        expect($html)->toContain('aura-field-invalid')
            ->and($html)->toMatch('/<span class="aura-field-error[^"]*">Invalid slug<\/span>/')
            ->and($html)->not->toContain('text-red-600 dark:text-red-400');
    });

    test('field controls leave colours and states to .aura-field', function () {
        $offenders = [];

        foreach (Finder::create()->files()->in(dirname(__DIR__, 2).'/resources/views')->name('*.blade.php') as $file) {
            preg_match_all('/class=(["\'])(.*?)\1/s', $file->getContents(), $matches);

            foreach ($matches[2] as $class) {
                $tokens = preg_split('/\s+/', trim($class));

                if (! in_array('aura-field', $tokens, true)) {
                    continue;
                }

                foreach ($tokens as $token) {
                    if (preg_match('/^(dark:)?((hover|focus|disabled):)*(bg-(white|gray-\d+)|border-gray-\d+|ring-(gray|white)[\w\/-]*|text-gray-\d+)/', $token)) {
                        $offenders[] = $file->getRelativePathname().': '.$token;
                    }
                }
            }
        }

        expect($offenders)->toBe([]);
    });
});

describe('component classes with a dark style', function () {
    test('the shipped CSS has the field, chip, toggle, tab, alert and Tagify dark rules', function () {
        $css = darkModeBuiltCss();

        foreach ([
            '.dark .aura-field.aura-field{',
            '.aura-field-invalid,',
            '.dark .aura-field-error{',
            '.dark .tagify.aura-field{',
            '.dark .aura-chip{',
            '.dark .aura-chip-primary{',
            '.dark .aura-toggle{',
            '.dark .aura-tab{',
            '.dark .aura-alert{',
            '.dark .aura-button-light{',
        ] as $selector) {
            expect($css)->toContain($selector);
        }
    });

    test('advanced-select chips use the chip classes, never bare light utilities', function () {
        foreach (['components/fields/advanced-select.blade.php', 'components/fields/filters/advanced-select.blade.php'] as $view) {
            $source = darkModeView($view);

            expect($source)->toContain('aura-chip-primary draggable-selectmany-item')
                ->and($source)->not->toContain('bg-primary-100 text-primary-800');
        }

        expect(darkModeView('components/fields/advanced-select-index.blade.php'))
            ->toContain('aura-chip flex')
            ->not->toContain('bg-gray-100');
    });

    test('every toggle switch uses .aura-toggle with a strict aria-checked', function () {
        foreach (['components/input/toggle.blade.php', 'components/input/toggle2.blade.php', 'components/fields/boolean.blade.php', 'components/fields/slug.blade.php'] as $view) {
            $source = darkModeView($view);

            expect($source)->toContain('aura-toggle')
                ->and($source)->toMatch("/:aria-checked=\"\\w+ \\? 'true' : 'false'\"/")
                ->and($source)->not->toContain('dark:bg-white/10');
        }
    });

    test('form tabs and resource editor tabs share .aura-tab', function () {
        foreach (['components/fields/tabs.blade.php', 'livewire/resource-editor.blade.php'] as $view) {
            $source = darkModeView($view);

            expect($source)->toContain('aura-tab')
                ->and($source)->toContain('aura-tab-active')
                ->and($source)->not->toContain('dark:hover:text-gray-500');
        }
    });

    test('the 2FA status uses the alert classes', function () {
        $source = darkModeView('profile/two-factor-authentication-form.blade.php');

        expect($source)->toContain('aura-alert aura-alert-success')
            ->and($source)->toContain('aura-alert aura-alert-warning')
            ->and($source)->not->toContain('dark:bg-gray-800');
    });
});

describe('spinner', function () {
    test('the button spinner is drawn in currentColor', function () {
        $html = Blade::render('<x-aura::icon.loading class="mr-1" />');

        expect(substr_count($html, 'fill="currentColor"'))->toBe(2)
            ->and($html)->not->toContain('#E5E7EB')
            ->and($html)->not->toContain('text-white')
            ->and($html)->not->toContain('mr-3')
            ->and($html)->toContain('mr-1')
            ->and($html)->toContain('animate-spin');
    });
});

describe('pages', function () {
    test('the plugins page is a table card with a page header', function () {
        livewire(PluginsPage::class)
            ->assertSeeHtml('<h1 class="mb-2 text-2xl font-semibold">')
            ->assertSeeHtml('aura-table-card aura-radius-panel')
            ->assertSeeHtml('aura-alert aura-radius-control')
            ->assertSeeHtml('whitespace-nowrap rounded-full aura-chip-primary')
            ->assertDontSeeHtml('bg-gray-200')
            ->assertDontSeeHtml('rounded-lg');
    });

    test('the user teams tab uses the standard select and the danger button', function () {
        if (! config('aura.teams')) {
            $this->markTestSkipped('The Membership editor is a teams-on feature.');
        }

        $this->actingAs(createGlobalAdmin());

        $team = Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
        $role = Role::withoutGlobalScopes()->create([
            'type' => 'Role', 'name' => 'Member', 'slug' => 'member-'.uniqid(), 'super_admin' => false, 'permissions' => [], 'team_id' => $team->id,
        ]);
        $viewed = User::factory()->create();
        $viewed->roles()->attach($role->id, ['team_id' => $team->id]);

        $html = livewire(UserTeams::class, ['userId' => $viewed->id])->html();

        preg_match('/<select\b[^>]*dusk="role-select-'.$team->id.'"[^>]*>/s', $html, $select);

        expect($select[0] ?? '')->toContain('aura-field aura-control-sm');
        expect($html)->toContain('dusk="detach-'.$team->id.'"')
            ->and($html)->toContain('text-red-700 bg-red-500/10')
            ->and($html)->not->toContain('class="text-sm border-gray-300 rounded-md');
    });
});
