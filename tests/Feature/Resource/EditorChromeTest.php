<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Support\Facades\Route;

class EditorChromeResource extends Resource
{
    public static ?string $slug = 'editor-chrome';

    public static string $type = 'EditorChrome';

    public function createHeaderTitle()
    {
        return 'Start an editor chrome record';
    }

    public function createReturnRoute()
    {
        return 'editor-chrome.create-return';
    }

    public function editHeaderTitle()
    {
        return 'Revise this editor chrome record';
    }

    public function editReturnRoute()
    {
        return 'editor-chrome.edit-return';
    }

    public static function getFields()
    {
        return [];
    }
}

beforeEach(function () {
    Route::get('/editor-chrome/create-return', fn () => 'create return')
        ->name('editor-chrome.create-return');
    Route::get('/editor-chrome/edit-return', fn () => 'edit return')
        ->name('editor-chrome.edit-return');

    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new EditorChromeResource);
});

test('default editor chrome preserves resource copy and index destinations', function () {
    $post = new Post;

    expect($post->createHeaderTitle())->toBe('Create Post')
        ->and($post->createReturnRoute())->toBe('aura.post.index')
        ->and($post->editHeaderTitle())->toBe('Edit Post')
        ->and($post->editReturnRoute())->toBe('aura.post.index');
});

test('resource overrides create and edit editor chrome without replacing views', function () {
    $record = EditorChromeResource::create([
        'title' => 'Existing record',
        'type' => EditorChromeResource::$type,
    ]);

    $this->get(route('aura.editor-chrome.create'))
        ->assertOk()
        ->assertSee('Start an editor chrome record')
        ->assertSee(route('editor-chrome.create-return'));

    $this->get(route('aura.editor-chrome.edit', ['id' => $record->getKey()]))
        ->assertOk()
        ->assertSee('Revise this editor chrome record')
        ->assertSee(route('editor-chrome.edit-return'));
});

test('editor chrome overrides do not bypass resource authorization', function () {
    $record = EditorChromeResource::create([
        'title' => 'Protected record',
        'type' => EditorChromeResource::$type,
    ]);

    $this->actingAs(User::factory()->create());

    $this->get(route('aura.editor-chrome.create'))->assertForbidden();
    $this->get(route('aura.editor-chrome.edit', ['id' => $record->getKey()]))->assertForbidden();
});
