<?php

namespace Aura\Base\Livewire;

use Aura\Base\Livewire\Media\MediaAuthorization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class MediaManager extends Component
{
    public $field;

    public $fieldSlug;

    public $initialSelectionDone = false;

    public $modalAttributes;

    public $model;

    public $rowIds = [];

    public $selected = [];

    public static function modalClasses(): string
    {
        return 'max-w-7xl';
    }

    public function mount($slug, $selected, $modalAttributes = null)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $this->selected = collect($selected ?? [])
            ->map(fn ($id) => (string) $id)
            ->values()
            ->toArray();
        $this->fieldSlug = $slug;
        $this->modalAttributes = $modalAttributes;

        // The resolved field definition becomes Livewire state, so closures
        // (gate-checked `disabled`, `set` hooks) must not travel with it —
        // the source definition still drives the form that opened this modal.
        $this->field = app($this->model)->fieldBySlug($this->fieldSlug);

        if (is_array($this->field)) {
            $this->field = array_filter($this->field, fn ($value) => ! $value instanceof \Closure);
        }

        $this->rowIds = [];

        app(MediaAuthorization::class)->authorizeAttachments($this->selected, $user);
    }

    public function render()
    {
        return view('aura::livewire.media-manager');
    }

    public function select($selectedValues = null)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        $selected = collect($selectedValues ?? $this->selected)
            ->map(fn ($id) => (string) $id)
            ->values()
            ->toArray();

        app(MediaAuthorization::class)->authorizeAttachments($selected, $user);

        $this->dispatch('updateField', data: [
            'slug' => $this->fieldSlug,
            'value' => $selected,
        ]);
    }

    #[On('selectedRows')]
    public function selectAttachment($ids)
    {
        if (! $this->initialSelectionDone) {
            $this->selected = collect($ids)->map(fn ($id) => (string) $id)->values()->toArray();
            $this->initialSelectionDone = true;
        }
    }

    #[On('tableMounted')]
    public function tableMounted()
    {
        if ($this->selected && ! $this->initialSelectionDone) {
            $this->dispatch('selectedRows', collect($this->selected)->map(fn ($id) => (string) $id)->values()->toArray());
            $this->initialSelectionDone = true;
        }
    }

    #[On('updateField')]
    public function updateField($data)
    {
        if ($data['slug'] == $this->fieldSlug) {
            $this->selected = collect($data['value'])->map(fn ($id) => (string) $id)->values()->toArray();
        }
    }
}
