<?php

namespace Aura\Base\Tests\Fixtures\RecordLayout;

use Aura\Base\Resource;
use Livewire\Component;

class TestPanel extends Component
{
    public bool $inModal = false;

    public Resource $model;

    public function render(): string
    {
        return '<section data-test-record-panel="'.e(static::class).'" data-modal="'.($this->inModal ? 'true' : 'false').'">'
            .e($this->model->title()).'</section>';
    }
}
