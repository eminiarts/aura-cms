<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\RouteTarget;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;

class EditorChromeResource extends Resource
{
    public static ?string $slug = 'editor-chrome';

    public static string $type = 'EditorChrome';

    public function createHeaderTitle(): string
    {
        return 'Start an editor chrome record';
    }

    public function createReturnRoute(): RouteTarget|string
    {
        return new RouteTarget('editor-chrome.create-return', [
            'section' => 'contacts',
            'view' => 'compact',
            'page' => 2,
        ]);
    }

    public function editHeaderTitle(): string
    {
        return 'Revise this editor chrome record';
    }

    public function editReturnRoute(): RouteTarget|string
    {
        return new RouteTarget('editor-chrome.edit-return', [
            'section' => 'companies',
            'view' => 'board',
            'page' => 3,
        ]);
    }

    public static function getFields(): array
    {
        return [];
    }
}

class MissingReturnRouteEditorChromeResource extends Resource
{
    public static ?string $slug = 'missing-return-route-editor-chrome';

    public static string $type = 'MissingReturnRouteEditorChrome';

    public function createReturnRoute(): RouteTarget|string
    {
        return 'editor-chrome.missing-create-return';
    }

    public function editReturnRoute(): RouteTarget|string
    {
        return 'editor-chrome.missing-edit-return';
    }

    public static function getFields(): array
    {
        return [];
    }
}

class MaliciousTitleEditorChromeResource extends Resource
{
    public static ?string $slug = 'malicious-title-editor-chrome';

    public static string $type = 'MaliciousTitleEditorChrome';

    public function createHeaderTitle(): string
    {
        return 'Create <script>alert("create-xss")</script>';
    }

    public function editHeaderTitle(): string
    {
        return 'Edit <script>alert("edit-xss")</script>';
    }

    public static function getFields(): array
    {
        return [];
    }
}

class ExternalReturnRouteEditorChromeResource extends Resource
{
    public static ?string $slug = 'external-return-route-editor-chrome';

    public static string $type = 'ExternalReturnRouteEditorChrome';

    public function createReturnRoute(): RouteTarget|string
    {
        return 'https://attacker.example/leave-aura';
    }

    public static function getFields(): array
    {
        return [];
    }
}

beforeEach(function () {
    Route::get('/editor-chrome/default-return', fn (): string => 'default return')
        ->name('aura.post.index');
    Route::get('/editor-chrome/create-return/{section}', fn (string $section): string => $section)
        ->name('editor-chrome.create-return');
    Route::get('/editor-chrome/edit-return/{section}', fn (string $section): string => $section)
        ->name('editor-chrome.edit-return');
    app('router')->getRoutes()->refreshNameLookups();

    $this->actingAs($this->user = createSuperAdmin());
});

test('default editor chrome preserves typed resource copy and named index destinations', function () {
    $post = new Post;

    expect($post->createHeaderTitle())->toBe('Create Post')
        ->and($post->createReturnRoute())->toBe('aura.post.index')
        ->and($post->createReturnUrl())->toBe(route('aura.post.index'))
        ->and($post->editHeaderTitle())->toBe('Edit Post')
        ->and($post->editReturnRoute())->toBe('aura.post.index')
        ->and($post->editReturnUrl())->toBe(route('aura.post.index'))
        ->and((string) (new ReflectionMethod(Post::class, 'createHeaderTitle'))->getReturnType())->toBe('string')
        ->and((string) (new ReflectionMethod(Post::class, 'editHeaderTitle'))->getReturnType())->toBe('string')
        ->and((string) (new ReflectionMethod(Post::class, 'createReturnRoute'))->getReturnType())->toContain(RouteTarget::class)
        ->and((string) (new ReflectionMethod(Post::class, 'createReturnRoute'))->getReturnType())->toContain('string')
        ->and((string) (new ReflectionMethod(Post::class, 'editReturnRoute'))->getReturnType())->toContain(RouteTarget::class)
        ->and((string) (new ReflectionMethod(Post::class, 'editReturnRoute'))->getReturnType())->toContain('string');
});

test('resource overrides create and edit chrome with route parameters and query state', function () {
    Aura::fake();
    Aura::setModel(new EditorChromeResource);

    $record = EditorChromeResource::create([
        'title' => 'Existing record',
        'type' => EditorChromeResource::$type,
    ]);

    $createReturnUrl = route('editor-chrome.create-return', [
        'section' => 'contacts',
        'view' => 'compact',
        'page' => 2,
    ]);
    $editReturnUrl = route('editor-chrome.edit-return', [
        'section' => 'companies',
        'view' => 'board',
        'page' => 3,
    ]);

    $this->get(route('aura.editor-chrome.create'))
        ->assertOk()
        ->assertSee('Start an editor chrome record')
        ->assertSee('href="'.e($createReturnUrl).'"', false);

    $this->get(route('aura.editor-chrome.edit', ['id' => $record->getKey()]))
        ->assertOk()
        ->assertSee('Revise this editor chrome record')
        ->assertSee('href="'.e($editReturnUrl).'"', false);
});

test('missing create and edit return routes render an unlinked breadcrumb', function () {
    Aura::fake();
    $resource = new MissingReturnRouteEditorChromeResource;
    Aura::setModel($resource);

    $record = MissingReturnRouteEditorChromeResource::create([
        'title' => 'Existing record',
        'type' => MissingReturnRouteEditorChromeResource::$type,
    ]);

    expect(Route::has('editor-chrome.missing-create-return'))->toBeFalse()
        ->and(Route::has('editor-chrome.missing-edit-return'))->toBeFalse();

    $this->get(route('aura.missing-return-route-editor-chrome.create'))
        ->assertOk()
        ->assertSee($resource->getPluralName());

    $this->get(route('aura.missing-return-route-editor-chrome.edit', ['id' => $record->getKey()]))
        ->assertOk()
        ->assertSee($resource->getPluralName());
});

test('return route strings are treated only as named routes', function () {
    $resource = new ExternalReturnRouteEditorChromeResource;

    expect($resource->createReturnUrl())->toBeNull();
});

test('create and edit title hooks are escaped at every rendered sink', function () {
    Aura::fake();
    Aura::setModel(new MaliciousTitleEditorChromeResource);

    $record = MaliciousTitleEditorChromeResource::create([
        'title' => 'Existing record',
        'type' => MaliciousTitleEditorChromeResource::$type,
    ]);

    $createTitle = 'Create <script>alert("create-xss")</script>';
    $editTitle = 'Edit <script>alert("edit-xss")</script>';

    $this->get(route('aura.malicious-title-editor-chrome.create'))
        ->assertOk()
        ->assertSee(e($createTitle), false)
        ->assertDontSee($createTitle, false);

    $this->get(route('aura.malicious-title-editor-chrome.edit', ['id' => $record->getKey()]))
        ->assertOk()
        ->assertSee(e($editTitle), false)
        ->assertDontSee($editTitle, false);
});

test('breadcrumb titles escape plain strings and preserve explicit Htmlable content', function () {
    $plainTitle = '<strong>Untrusted</strong>';

    $this->blade('<x-aura::breadcrumbs.li :title="$title" />', ['title' => $plainTitle])
        ->assertSee(e($plainTitle), false)
        ->assertDontSee($plainTitle, false);

    $this->blade('<x-aura::breadcrumbs.li :title="$title" />', [
        'title' => new HtmlString('<strong>Trusted</strong>'),
    ])->assertSee('<strong>Trusted</strong>', false);
});

test('editor chrome overrides do not bypass resource authorization', function () {
    Aura::fake();
    Aura::setModel(new EditorChromeResource);

    $record = EditorChromeResource::create([
        'title' => 'Protected record',
        'type' => EditorChromeResource::$type,
    ]);

    $this->actingAs(User::factory()->create());

    $this->get(route('aura.editor-chrome.create'))->assertForbidden();
    $this->get(route('aura.editor-chrome.edit', ['id' => $record->getKey()]))->assertForbidden();
});
