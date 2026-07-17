<?php

use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resources\Attachment;
use Illuminate\Support\Facades\Route;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

/**
 * Attachment uses a custom media-grid table by default. This subclass forces the
 * standard table + row views so row-actions (and viewUrl) are exercised.
 */
class AttachmentWithStandardTable extends Attachment
{
    public function defaultTableView()
    {
        return 'list';
    }

    public function tableComponentView()
    {
        return 'aura::livewire.table';
    }

    public function tableView()
    {
        return 'aura::components.table.list-view';
    }
}

test('attachment view and edit routes are not registered', function () {
    expect(Route::has('aura.attachment.index'))->toBeTrue()
        ->and(Route::has('aura.attachment.view'))->toBeFalse()
        ->and(Route::has('aura.attachment.edit'))->toBeFalse()
        ->and(Route::has('aura.attachment.create'))->toBeFalse();
});

test('attachment url helpers return null when dedicated routes are missing', function () {
    $attachment = Attachment::create([
        'name' => 'Doc.pdf',
        'url' => 'media/doc.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
    ]);

    expect($attachment->viewUrl())->toBeNull()
        ->and($attachment->editUrl())->toBeNull()
        ->and($attachment->createUrl())->toBeNull()
        ->and($attachment->indexUrl())->toBe(route('aura.attachment.index'));
});

test('standard table renders attachment rows without RouteNotFoundException or view link', function () {
    $attachment = Attachment::create([
        'name' => 'Photo.jpg',
        'url' => 'media/photo.jpg',
        'mime_type' => 'image/jpeg',
        'size' => 2048,
    ]);

    $model = new AttachmentWithStandardTable;

    $component = livewire(Table::class, [
        'query' => null,
        'model' => $model,
        'settings' => [
            'default_view' => 'list',
            'actions' => true,
            'create' => false,
            'views' => [
                'list' => 'aura::components.table.list-view',
                'row' => 'aura::components.table.row',
                'table' => 'aura::components.table.index',
            ],
        ],
    ]);

    $html = $component->html();

    expect($html)->toContain((string) $attachment->id)
        // View action uses sr-only "View" text; without a view route it must not render.
        ->and($html)->not->toContain('>'.__('View').'</span>')
        ->and($html)->not->toContain('href="'.route('aura.attachment.index').'/'.$attachment->id.'"');
});
