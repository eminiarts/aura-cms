<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Attachment;
use LivewireUI\Modal\ModalComponent;

class MediaManager extends ModalComponent
{
    public $field;

    public $fieldSlug;

    public $model;

    public $selected = [];

    // Listen for select Attachment
    protected $listeners = [
        'selectedRows' => 'selectAttachment',
        'tableMounted',
        'updateField' => 'updateField',
    ];

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }

    public function mount($slug, $selected, $test)
    {
        ray('mount MediaManager', $slug, $selected, $test);
        $this->selected = $selected;
        $this->fieldSlug = $slug;

        $this->field = app($this->model)->fieldBySlug($this->fieldSlug);

        // ray('mount media manager', app($this->model), $this->fieldSlug, $this->field);
    }

    public function render()
    {
        return view('aura::livewire.media-manager');
    }

    public function select()
    {
        // Emit update Field
        $this->dispatch('updateField', [
            'slug' => $this->fieldSlug,
            'value' => $this->selected,
        ]);

        ray('select', $this->fieldSlug, $this->selected);

        // If selected is deffered, emit event to table to update selected, then emit back to updateField

        // Close Modal
        $this->closeModal();
    }

    // Select Attachment
    public function selectAttachment($ids)
    {
        $this->selected = $ids;
    }

    public function tableMounted()
    {
        if ($this->selected) {
            ray('mediamanager tableMounted selectedRows', $this->selected);
            $this->dispatch('selectedRows', $this->selected);
        }
    }

    public function updated()
    {
        ray('mediamanager updated selectedRows', $this->selected);
        $this->dispatch('selectedRows', $this->selected);
    }

    public function updateField($field)
    {
        if ($field['slug'] == $this->fieldSlug) {
            $this->selected = $field['value'];
            ray('mediamanager updateField selectedRows', $this->selected);
            $this->dispatch('selectedRows', $this->selected);
        }
    }
}
