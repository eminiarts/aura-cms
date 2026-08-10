<?php

namespace Tests\Feature\Fields;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Fields\BelongsTo;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use RuntimeException;

class BelongsToPresentationRow extends Resource
{
    public static ?string $slug = 'presentation-row';

    public static string $type = 'PresentationRow';

    public static bool $usesMeta = false;
}

class BelongsToPresentationUser extends User
{
    public function manager(): EloquentBelongsTo
    {
        return $this->belongsTo(Post::class, 'manager_id');
    }
}

class BelongsToRouteFallbackPost extends Post
{
    public static ?string $slug = 'fallback-post';
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Route::get('/presentation-post/{id}', fn (): string => 'view')
        ->name('aura.post.view');
    Route::get('/presentation-post/{id}/edit', fn (): string => 'edit')
        ->name('aura.post.edit');
    Route::getRoutes()->refreshNameLookups();
});

describe('BelongsTo Field Configuration', function () {
    test('has correct properties', function () {
        $field = new BelongsTo;

        expect($field->optionGroup)->toBe('Relationship Fields')
            ->and($field->edit)->toBe('aura::fields.belongsto')
            ->and($field->view)->toBe('aura::fields.view-value')
            ->and($field->tableColumnType)->toBe('bigInteger')
            ->and($field->type)->toBe('input')
            ->and($field->group)->toBeFalse();
    });

    test('has a resource configuration field', function () {
        $fields = collect((new BelongsTo)->getFields());

        expect($fields->firstWhere('slug', 'resource'))->not->toBeNull();
    });
});

describe('BelongsTo Field Value Handling', function () {
    test('set returns the related id unchanged', function () {
        $field = new BelongsTo;

        expect($field->set(null, [], 5))->toBe(5)
            ->and($field->set(null, [], null))->toBeNull();
    });
});

describe('BelongsTo Field Display', function () {
    test('legacy subclasses receive the raw value in display overrides', function () {
        $field = new class extends BelongsTo
        {
            public mixed $receivedValue = null;

            public function display($field, $value, $model)
            {
                $this->receivedValue = $value;

                return 'custom:'.$value;
            }
        };

        $result = $field->presentValue(
            'raw',
            ['resource' => Post::class],
            $this->user,
            FieldValueContext::View,
        );

        expect((string) $result)->toBe('custom:raw')
            ->and($field->receivedValue)->toBe('raw');
    });

    test('legacy subclasses preserve context when delegating display to the native relationship presenter', function () {
        $related = Post::factory()->create(['title' => 'Delegated Post']);
        $field = new class extends BelongsTo
        {
            public function display($field, $value, $model)
            {
                return parent::display($field, $value, $model);
            }
        };

        $index = $field->presentValue(
            $related->id,
            ['resource' => Post::class],
            $this->user,
        );
        $view = $field->presentValue(
            $related->id,
            ['resource' => Post::class],
            $this->user,
            FieldValueContext::View,
        );
        $export = $field->presentValue(
            $related->id,
            ['resource' => Post::class],
            $this->user,
            FieldValueContext::Export,
        );

        expect((string) $index)->toContain('<a')
            ->and((string) $view)->toContain('<a')
            ->and((string) $export)->toContain('Delegated Post')
            ->and((string) $export)->not->toContain('<a');
    });

    test('prefers the authorized related resource view page', function () {
        $related = Post::factory()->create(['title' => 'Related Post']);

        $field = new BelongsTo;
        $definition = ['resource' => Post::class];

        $html = $field->display($definition, $related->id, $this->user);

        expect($this->user->isSuperAdmin())->toBeTrue()
            ->and((new ResourcePolicy)->view($this->user, $related))->toBeTrue()
            ->and((string) $html)->toContain('<a')
            ->and((string) $html)->toContain(route('aura.post.view', $related->id))
            ->and((string) $html)->not->toContain(route('aura.post.edit', $related->id));
    });

    test('returns the raw value when no resource is configured', function () {
        $field = new BelongsTo;

        expect($field->display(['resource' => null], 5, $this->user))->toBe(5);
    });

    test('returns the raw value when value is empty', function () {
        $field = new BelongsTo;

        expect($field->display(['resource' => User::class], null, $this->user))->toBeNull();
    });

    test('custom display views return sanitized trusted html', function () {
        $directory = storage_path('framework/testing/core10-belongs-to-view');
        File::ensureDirectoryExists($directory);
        File::put(
            $directory.'/relation.blade.php',
            '<span class="related">{!! $value !!}</span><script>alert(2)</script>',
        );
        View::addNamespace('core10-belongs-to', $directory);

        try {
            $result = (new BelongsTo)->display([
                'display_view' => 'core10-belongs-to::relation',
            ], '<img src=x onerror=alert(1)>Related', $this->user);

            expect($result)->toBeInstanceOf(Htmlable::class)
                ->and((string) $result)->toContain('<span')
                ->toContain('Related')
                ->not->toContain('<script')
                ->not->toContain('onerror');
        } finally {
            File::deleteDirectory($directory);
        }
    });

    test('renders an authorized view-only relation as a view link', function () {
        $viewer = createAdmin();
        $viewer->roles()->first()->update(['permissions' => [
            'view-post' => true,
            'viewAny-post' => true,
            'update-post' => false,
            'scope-post' => false,
        ]]);
        $this->actingAs($viewer->refresh());
        $related = Post::factory()->create(['title' => 'Readable Post']);

        $html = (new BelongsTo)->display(['resource' => Post::class], $related->id, $viewer);

        expect((string) $html)->toContain('Readable Post')
            ->toContain(route('aura.post.view', $related->id))
            ->not->toContain(route('aura.post.edit', $related->id));
    });

    test('uses the edit destination only when update is authorized and view is not', function () {
        $editor = createAdmin();
        $editor->roles()->first()->update(['permissions' => [
            'view-post' => false,
            'update-post' => true,
            'scope-post' => false,
        ]]);
        $this->actingAs($editor->refresh());
        $related = Post::factory()->create(['title' => 'Editable Post']);

        $html = (new BelongsTo)->display(['resource' => Post::class], $related->id, $editor);

        expect((string) $html)->toContain($related->title())
            ->toContain("href='".route('aura.post.edit', $related->id)."'")
            ->not->toContain("href='".route('aura.post.view', $related->id)."'");
    });

    test('does not disclose unauthorized relation labels on any presentation surface', function () {
        $viewer = createAdmin();
        $viewer->roles()->first()->update(['permissions' => [
            'view-post' => false,
            'update-post' => false,
            'scope-post' => false,
        ]]);
        $this->actingAs($viewer->refresh());
        $related = Post::factory()->create(['title' => 'Unlinked Post']);
        $resolverCalls = 0;
        $field = new BelongsTo;
        $definition = [
            'resource' => Post::class,
            'label_resolver' => function () use (&$resolverCalls): string {
                $resolverCalls++;

                return (string) Post::query()
                    ->where('title', 'Denied resolver-only lookup')
                    ->value('title');
            },
        ];

        DB::flushQueryLog();
        DB::enableQueryLog();

        $unauthorizedIndex = $field->presentValue($related->id, $definition, $viewer);
        $unauthorizedView = $field->presentValue(
            $related->id,
            $definition,
            $viewer,
            FieldValueContext::View,
        );
        $unauthorizedExport = $field->presentValue(
            $related->id,
            $definition,
            $viewer,
            FieldValueContext::Export,
        );
        $missing = $field->display($definition, 999999, $viewer);
        $resolverQueries = collect(DB::getQueryLog())->filter(
            fn (array $query): bool => in_array('Denied resolver-only lookup', $query['bindings'], true),
        );

        expect((string) $unauthorizedIndex)->toBe((string) $related->id)
            ->and((string) $unauthorizedView)->toBe((string) $related->id)
            ->and((string) $unauthorizedExport)->toBe((string) $related->id)
            ->and((string) $unauthorizedIndex)->not->toContain($related->title())
            ->and((string) $unauthorizedView)->not->toContain($related->title())
            ->and((string) $unauthorizedExport)->not->toContain($related->title())
            ->and((string) $missing)->toBe('999999')
            ->and((string) $missing)->not->toContain('<a')
            ->and($resolverCalls)->toBe(0)
            ->and($resolverQueries)->toHaveCount(0);
    });

    test('treats a constrained eager-loaded null relation as authoritative without a fallback query', function () {
        $related = Post::factory()->create(['title' => 'Hidden Loaded Post']);
        $user = User::factory()->create();
        $row = BelongsToPresentationUser::withoutGlobalScopes()->findOrFail($user->getKey());
        $row->setAttribute('manager_id', $related->id);
        $row->load(['manager' => fn ($query) => $query->whereKey(-1)]);
        $definition = [
            'resource' => Post::class,
            'relation' => 'manager',
            'slug' => 'manager_id',
        ];

        DB::flushQueryLog();
        DB::enableQueryLog();
        $label = (new BelongsTo)->presentValue(
            $related->id,
            $definition,
            $row,
            FieldValueContext::Export,
        );

        expect((string) $label)->toBe((string) $related->id)
            ->and((string) $label)->not->toContain($related->title())
            ->and(DB::getQueryLog())->toHaveCount(0);
    });

    test('does not use an eager-loaded relation whose key differs from the stored value', function () {
        $stored = Post::factory()->create(['title' => 'Stored Post']);
        $stale = Post::factory()->create(['title' => 'Stale Loaded Post']);
        $row = User::factory()->create();
        $row->setRelation('manager', $stale);
        $definition = [
            'resource' => Post::class,
            'relation' => 'manager',
            'slug' => 'manager_id',
        ];

        DB::flushQueryLog();
        DB::enableQueryLog();
        $label = (new BelongsTo)->presentValue(
            $stored->id,
            $definition,
            $row,
            FieldValueContext::Export,
        );

        expect((string) $label)->toBe((string) $stored->id)
            ->and((string) $label)->not->toContain($stored->title())
            ->and((string) $label)->not->toContain($stale->title())
            ->and(DB::getQueryLog())->toHaveCount(0);
    });

    test('supports custom relationship label and link destination resolvers', function () {
        $related = Post::factory()->create(['title' => 'Related Post']);
        $definition = [
            'resource' => Post::class,
            'label_resolver' => fn (mixed $raw, mixed $current): string => $current.' #'.$raw,
            'link_resolver' => fn (Post $record): string => '/posts/'.$record->getKey(),
        ];

        $html = (new BelongsTo)->display($definition, $related->id, $this->user);

        expect((string) $html)->toContain($related->title().' #'.$related->id)
            ->toContain('/posts/'.$related->id);
    });

    test('accepts safe relative custom relationship destinations', function (string $destination) {
        $related = Post::factory()->create(['title' => 'Related Post']);
        $definition = [
            'resource' => Post::class,
            'link_resolver' => fn (): string => $destination,
        ];

        foreach ([FieldValueContext::Index, FieldValueContext::View] as $context) {
            $html = (new BelongsTo)->presentValue($related->id, $definition, $this->user, $context);

            expect((string) $html)
                ->toContain('<a')
                ->toContain("href='".e($destination)."'");
        }
    })->with([
        'root-relative path' => '/posts/1',
        'relative path' => 'posts/1',
        'dot-relative path' => './posts/1',
    ]);

    test('accepts same-origin absolute custom relationship destinations', function () {
        $related = Post::factory()->create(['title' => 'Related Post']);
        $destination = url('/posts/'.$related->id.'?tab=activity#latest');
        $definition = [
            'resource' => Post::class,
            'link_resolver' => fn (): string => $destination,
        ];

        $html = (new BelongsTo)->presentValue($related->id, $definition, $this->user);

        expect((string) $html)
            ->toContain('<a')
            ->toContain("href='".e($destination)."'");
    });

    test('rejects an absolute custom destination with a mismatched origin scheme', function () {
        $related = Post::factory()->create(['title' => 'Related Post']);
        $application = parse_url(url('/'));
        $oppositeScheme = ($application['scheme'] ?? 'http') === 'https' ? 'http' : 'https';
        $destination = $oppositeScheme.'://'.$application['host'].'/posts/'.$related->id;
        $definition = [
            'resource' => Post::class,
            'link_resolver' => fn (): string => $destination,
        ];

        $html = (new BelongsTo)->presentValue($related->id, $definition, $this->user);

        expect((string) $html)
            ->toContain("href='".route('aura.post.view', $related->id)."'")
            ->not->toContain("href='".e($destination)."'");
    });

    test('unsafe custom destinations fall back to the authorized default without reaching exports', function (string $destination) {
        $related = Post::factory()->create(['title' => 'Related Post']);
        $definition = [
            'resource' => Post::class,
            'link_resolver' => fn (): string => $destination,
        ];
        $safeDestination = route('aura.post.view', $related->id);
        $field = new BelongsTo;

        foreach ([FieldValueContext::Index, FieldValueContext::View] as $context) {
            $html = $field->presentValue($related->id, $definition, $this->user, $context);

            expect((string) $html)
                ->toContain("href='".e($safeDestination)."'")
                ->not->toContain("href='".e($destination)."'");
        }

        $export = $field->presentValue(
            $related->id,
            $definition,
            $this->user,
            FieldValueContext::Export,
        );

        expect((string) $export)
            ->toBe($related->title())
            ->not->toContain('<a')
            ->not->toContain($destination);
    })->with([
        'javascript scheme' => 'javascript:alert(document.domain)',
        'mixed-case javascript scheme' => 'JaVaScRiPt:alert(document.domain)',
        'data scheme' => 'data:text/html,<svg onload=alert(document.domain)>',
        'vbscript scheme' => 'VBScript:msgbox(document.domain)',
        'file scheme' => 'file:///etc/passwd',
        'protocol-relative URL' => '//attacker.example/collect',
        'backslash protocol-relative URL' => '\\\\attacker.example\\collect',
        'leading whitespace' => ' javascript:alert(document.domain)',
        'embedded control character' => "java\tscript:alert(document.domain)",
        'entity-obfuscated scheme' => 'java&#x73;cript:alert(document.domain)',
        'entity-obfuscated colon' => 'javascript&colon;alert(document.domain)',
        'cross-origin HTTPS URL' => 'https://example.test/posts/1',
        'cross-origin HTTP URL' => 'http://attacker.example/posts/1',
    ]);

    test('falls through to an authorized edit route when view route generation fails', function () {
        $related = Post::factory()->create(['title' => 'Fallback Route Post']);

        Route::get('/broken-fallback-post/{id}/{missing}', fn (): string => 'view')
            ->name('aura.fallback-post.view');
        Route::get('/fallback-post/{id}/edit', fn (): string => 'edit')
            ->name('aura.fallback-post.edit');
        Route::getRoutes()->refreshNameLookups();

        $html = (new BelongsTo)->display(
            ['resource' => BelongsToRouteFallbackPost::class],
            $related->id,
            $this->user,
        );

        expect((string) $html)
            ->toContain($related->title())
            ->toContain("href='".route('aura.fallback-post.edit', $related->id)."'");
    });

    test('does not swallow unexpected route generation failures', function () {
        $related = Post::factory()->create(['title' => 'Broken Route Post']);
        $field = new class extends BelongsTo
        {
            protected function isSafeLinkDestination(string $destination): bool
            {
                throw new RuntimeException('unexpected route failure');
            }
        };

        expect(fn () => $field->display(['resource' => Post::class], $related->id, $this->user))
            ->toThrow(RuntimeException::class, 'unexpected route failure');
    });

    test('does not disclose relation labels to guests', function () {
        $related = Post::factory()->create(['title' => 'Guest Hidden Post']);
        auth()->logout();
        $resolverCalls = 0;
        $field = new BelongsTo;
        $definition = [
            'resource' => Post::class,
            'label_resolver' => function () use (&$resolverCalls): string {
                $resolverCalls++;

                return (string) Post::query()
                    ->where('title', 'Guest resolver-only lookup')
                    ->value('title');
            },
        ];

        DB::flushQueryLog();
        DB::enableQueryLog();

        foreach ([FieldValueContext::Index, FieldValueContext::View, FieldValueContext::Export] as $context) {
            $result = $field->presentValue($related->id, $definition, $this->user, $context);

            expect((string) $result)
                ->toBe((string) $related->id)
                ->not->toContain($related->title())
                ->not->toContain('<a');
        }

        $resolverQueries = collect(DB::getQueryLog())->filter(
            fn (array $query): bool => in_array('Guest resolver-only lookup', $query['bindings'], true),
        );

        expect($resolverCalls)->toBe(0)
            ->and($resolverQueries)->toHaveCount(0);
    });

    test('an empty custom destination intentionally renders plain text', function () {
        $related = Post::factory()->create(['title' => 'Related Post']);

        $html = (new BelongsTo)->display([
            'resource' => Post::class,
            'link_resolver' => fn (): string => '',
        ], $related->id, $this->user);

        expect((string) $html)->toBe($related->title())
            ->not->toContain('<a');
    });

    test('export context returns the related label without markup', function () {
        $related = Post::factory()->create(['title' => 'Exported Post']);

        $value = (new BelongsTo)->presentValue(
            $related->id,
            ['resource' => Post::class],
            $this->user,
            FieldValueContext::Export,
        );

        expect((string) $value)->toBe($related->title())
            ->and((string) $value)->not->toContain('<a');
    });

    test('uses eager-loaded relation data without a query', function () {
        $related = Post::factory()->create(['title' => 'Loaded Post']);
        $row = User::factory()->create();
        $row->setRelation('manager', $related);
        $definition = [
            'resource' => Post::class,
            'relation' => 'manager',
            'slug' => 'manager_id',
        ];

        DB::flushQueryLog();
        DB::enableQueryLog();
        $label = (new BelongsTo)->presentValue(
            $related->id,
            $definition,
            $row,
            FieldValueContext::Export,
        );

        $relatedQueries = collect(DB::getQueryLog())->filter(
            fn (array $query): bool => in_array(Post::$type, $query['bindings'], true),
        );

        expect((string) $label)->toContain($related->title())
            ->and($relatedQueries)->toHaveCount(0);
    });

    test('preloads relationship labels in one bounded query', function () {
        $related = Post::factory()->count(3)->create();
        $rows = collect([
            new BelongsToPresentationRow,
            new BelongsToPresentationRow,
            new BelongsToPresentationRow,
        ]);

        foreach ($rows as $index => $row) {
            $row->setAttribute('manager_id', $related[$index]->id);
        }

        $definition = [
            'resource' => Post::class,
            'slug' => 'manager_id',
        ];
        $field = new BelongsTo;

        DB::flushQueryLog();
        DB::enableQueryLog();
        $field->preloadPresentation(new Collection($rows->all()), $definition);
        $queriesAfterPreload = DB::getQueryLog();
        $relatedQueries = collect($queriesAfterPreload)->filter(
            fn (array $query): bool => in_array(Post::$type, $query['bindings'], true),
        );

        foreach ($rows as $index => $row) {
            expect((string) $field->presentValue($related[$index]->id, $definition, $row))
                ->toContain(e($related[$index]->title()));
        }

        expect($relatedQueries)->toHaveCount(1)
            ->and(DB::getQueryLog())->toHaveCount(count($queriesAfterPreload));
    });
});

describe('BelongsTo Field Resolve', function () {
    test('valuesForApi returns id/title pairs for the resource', function () {
        User::factory()->count(2)->create();

        $field = new BelongsTo;
        $results = $field->valuesForApi(User::class, null);

        expect($results)->toBeArray()
            ->and($results)->not->toBeEmpty()
            ->and($results[0])->toHaveKeys(['id', 'title']);
    });
});
