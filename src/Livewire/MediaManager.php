<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Attachment;
use Livewire\Component;
use Livewire\Attributes\On;

class MediaManager extends Component
{
    public $field;

    public $fieldSlug;

    public $model;

    public $selected = [];

    public $modalAttributes;

    // Listen for select Attachment
    protected $listeners = [
        'selectedRows' => 'selectAttachment',
        'tableMounted',
        'updateField' => 'updateField',
    ];


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

        $this->dispatch('media-manager-selected');

        // Close Modal
        $this->dispatch('closeModal');
    }

    #[On('selectedRows')]
    public function selectAttachment($ids)
    {
        ray('selectAttachment', $ids);
        $this->selected = $ids;
    }

    #[On('tableMounted')]
    public function tableMounted()
    {
        if ($this->selected) {
            $this->dispatch('selectedRows', $this->selected);
        }
    }

    #[On('updateField')]
    public function updateField($field)
    {
        if ($field['slug'] == $this->fieldSlug) {
        ray('updated', $this->selected);
            $this->selected = $field['value'];
            $this->dispatch('selectedRows', $this->selected);
        }
    }
}
