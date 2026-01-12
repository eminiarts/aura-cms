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

    public function select($selectedValues = null)
    {
        // Use passed values from Alpine if available (more reliable than entangle sync)
        // Ensure we have an array of strings for consistency
        $selected = collect($selectedValues ?? $this->selected)
            ->map(fn ($id) => (string) $id)
            ->values()
            ->toArray();

        $slug = $this->fieldSlug;

        // Log for debugging
        logger()->info('MediaManager::select()', [
            'slug' => $slug,
            'selected' => $selected,
        ]);

        // Dispatch the updateField event globally to ALL Livewire components
        // In Livewire 3, dispatch() without ->to() broadcasts to all listening components
        $this->dispatch('updateField', [
            'slug' => $slug,
            'value' => $selected,
        ]);

        // NOTE: Do NOT dispatch closeModal here!
        // The modal must be closed from Alpine AFTER this Livewire call completes
        // Otherwise the component is destroyed while events are still being processed,
        // causing "Component not found" errors
    }

    #[On('selectedRows')]
    public function selectAttachment($ids)
    {
        // Only sync initial selection, not ongoing changes to prevent circular updates
        if (! $this->initialSelectionDone) {
            $this->selected = collect($ids)->map(fn ($id) => (string) $id)->values()->toArray();
            $this->initialSelectionDone = true;
        }
    }

    #[On('tableMounted')]
    public function tableMounted()
    {
        // Sync initial selection to the table when it mounts
        if ($this->selected && ! $this->initialSelectionDone) {
            $this->dispatch('selectedRows', collect($this->selected)->map(fn ($id) => (string) $id)->values()->toArray());
            $this->initialSelectionDone = true;
        }
    }

    // Removed updated() method to prevent circular updates
    // The entangle directive handles syncing automatically

    #[On('updateField')]
    public function updateField($field)
    {
        // Only update if this is our field
        if ($field['slug'] == $this->fieldSlug) {
            $this->selected = collect($field['value'])->map(fn ($id) => (string) $id)->values()->toArray();
            // Don't dispatch selectedRows here to prevent circular updates
        }
    }
}
