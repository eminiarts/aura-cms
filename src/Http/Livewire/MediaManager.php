<?php

namespace Eminiarts\Aura\Http\Livewire;

use Eminiarts\Aura\Resources\Attachment;
use LivewireUI\Modal\ModalComponent;

class MediaManager extends ModalComponent
{
    public $field;

    public $fieldSlug;

    public $selected = [];

    // Listen for select Attachment
    protected $listeners = ['selectedRows' => 'selectAttachment', 'tableMounted', 'updateField' => 'updateField'];

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }

    public function mount($slug, $selected)
    {
        $this->selected = $selected;
        $this->fieldSlug = $slug;
    }

    public function render()
    {
        return view('aura::livewire.media-manager');
    }

    public function select()
    {
        // Emit update Field
        $this->emit('updateField', [
            'slug' => $this->fieldSlug,
            'value' => $this->selected,
        ]);

        // If selected is deffered, emit event to table to update selected, then emit back to updateField

        // Close Modal
        $this->closeModal();
    }

    // Select Attachment
    public function selectAttachment($ids)
    {
        ray('selectAttachment', $ids);
        $this->selected = $ids;
    }

    public function tableMounted()
    {
        if ($this->selected) {
            $this->emit('selectedRows', $this->selected);
        }
    }

    public function updated()
    {
        $this->emit('selectedRows', $this->selected);
    }

    public function updateField($field)
    {
        if ($field['slug'] == $this->fieldSlug) {
            $this->selected = $field['value'];
            $this->emit('selectedRows', $this->selected);
        }
    }
}
