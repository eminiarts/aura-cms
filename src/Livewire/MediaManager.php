<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Attachment;
use Livewire\Attributes\On;
use Livewire\Component;

class MediaManager extends Component
{
    public $field;

    public $fieldSlug;

    public $initialSelectionDone = false;

    public $modalAttributes;

    public $model;

    public $rowIds = []; // Add this line

    public $selected = [];

    public static function modalClasses(): string
    {
        return 'max-w-7xl';
    }

    public function mount($slug, $selected, $modalAttributes)
    {
        $this->selected = $selected;
        $this->fieldSlug = $slug;
        $this->modalAttributes = $modalAttributes;
        $this->field = app($this->model)->fieldBySlug($this->fieldSlug);
        $this->rowIds = Attachment::pluck('id')->toArray(); // Add this line to populate rowIds
    }

    public function render()
    {
        return view('aura::livewire.media-manager', [
            'rows' => Attachment::paginate(25), // Adjust the number as needed
        ]);
    }

    public function select()
    {
        // Emit update Field
        $this->dispatch('updateField', [
            'slug' => $this->fieldSlug,
            'value' => $this->selected,
        ]);

        $this->dispatch('media-manager-selected');

        // Close Modal
        $this->dispatch('closeModal');
    }

    #[On('selectedRows')]
    public function selectAttachment($ids)
    {
        if (! $this->initialSelectionDone) {
            $this->selected = $ids;
            $this->initialSelectionDone = true;
        }
    }

    #[On('tableMounted')]
    public function tableMounted()
    {
        if ($this->selected && ! $this->initialSelectionDone) {
            $this->dispatch('selectedRows', $this->selected);
            $this->initialSelectionDone = true;
        }
    }

    public function updated($name, $value)
    {
        if ($name === 'selected') {
            $this->dispatch('selectedRows', $this->selected);
        }
    }

    #[On('updateField')]
    public function updateField($field)
    {
        if ($field['slug'] == $this->fieldSlug) {
            $this->selected = $field['value'];
            $this->dispatch('selectedRows', $this->selected);
        }
    }
}
