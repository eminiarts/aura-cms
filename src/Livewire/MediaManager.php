<?php

namespace Aura\Base\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\File;
use Aura\Base\Fields\Image;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class MediaManager extends Component
{
    private const MAX_SELECTED_MEDIA = 500;

    #[Locked]
    public $field;

    #[Locked]
    public $fieldSlug;

    public $initialSelectionDone = false;

    #[Locked]
    public $modalAttributes;

    #[Locked]
    public string $resource;

    public $rowIds = [];

    public $selected = [];

    /**
     * Re-resolve every trusted declaration and policy decision at the component
     * boundary. The returned IDs are guaranteed to exist in the attachment
     * model's current global/team scope.
     *
     * @return list<string>
     */
    public function authorizeRequest(mixed $selected = null): array
    {
        $resource = Aura::findResourceBySlug($this->resource);
        $field = $resource?->fieldBySlug($this->fieldSlug);
        $fieldType = is_array($field) ? ($field['type'] ?? null) : null;

        if (
            ! $resource instanceof Resource
            || $resource->getSlug() !== $this->resource
            || ! is_array($field)
            || ($field['slug'] ?? null) !== $this->fieldSlug
            || ! is_string($fieldType)
            || (! is_a($fieldType, Image::class, true) && ! is_a($fieldType, File::class, true))
        ) {
            abort(422, 'The media manager resource field is invalid.');
        }

        Gate::authorize('viewAny', $resource);
        Gate::authorize('viewAny', config('aura.resources.attachment'));

        $this->field = $field;
        $ids = $this->normalizeSelected($selected ?? $this->selected);

        if ($ids === []) {
            return [];
        }

        $attachments = $this->attachmentQuery()->whereKey($ids)->get();

        if ($attachments->count() !== count($ids)) {
            abort(422, 'The selected media are invalid.');
        }

        $attachments->each(static fn (Model $attachment) => Gate::authorize('view', $attachment));

        return $ids;
    }

    public function hydrate(): void
    {
        $this->authorizeRequest();
    }

    public static function modalClasses(): string
    {
        return 'max-w-7xl';
    }

    public function mount(string $resource, string $slug, array $selected, array $modalAttributes): void
    {
        $this->resource = $resource;
        $this->selected = $selected;
        $this->fieldSlug = $slug;
        $this->modalAttributes = $modalAttributes;
        $this->authorizeRequest();
        $this->rowIds = $this->attachmentQuery()->pluck('id')->all();
    }

    public function render(): View
    {
        $this->authorizeRequest();

        return view('aura::livewire.media-manager', [
            'rows' => $this->attachmentQuery()->paginate(25),
        ]);
    }

    public function select(mixed $selectedValues = null): void
    {
        $selected = $this->authorizeRequest($selectedValues ?? $this->selected);

        // Dispatch the updateField event globally to ALL Livewire components
        // Use named parameter 'data' to match the listener's signature: updateField($data)
        $this->dispatch('updateField', data: [
            'slug' => $this->fieldSlug,
            'value' => $selected,
        ]);

        // NOTE: Do NOT dispatch closeModal here!
        // The modal must be closed from Alpine AFTER this Livewire call completes
        // Otherwise the component is destroyed while events are still being processed
    }

    #[On('selectedRows')]
    public function selectAttachment(mixed $ids): void
    {
        $selected = $this->authorizeRequest($ids);

        // Only sync initial selection, not ongoing changes to prevent circular updates
        if (! $this->initialSelectionDone) {
            $this->selected = $selected;
            $this->initialSelectionDone = true;
        }
    }

    #[On('tableMounted')]
    public function tableMounted(): void
    {
        $this->authorizeRequest();

        // Sync initial selection to the table when it mounts
        if ($this->selected && ! $this->initialSelectionDone) {
            $this->dispatch('selectedRows', collect($this->selected)->map(fn ($id) => (string) $id)->values()->toArray());
            $this->initialSelectionDone = true;
        }
    }

    // Removed updated() method to prevent circular updates
    // The entangle directive handles syncing automatically

    #[On('updateField')]
    public function updateField(mixed $data): void
    {
        if (! is_array($data) || ! is_string($data['slug'] ?? null) || ! array_key_exists('value', $data)) {
            abort(422, 'The selected media are invalid.');
        }

        // Only update if this is our field
        if (hash_equals($this->fieldSlug, $data['slug'])) {
            $this->selected = $this->authorizeRequest($data['value']);
            // Don't dispatch selectedRows here to prevent circular updates
        } else {
            $this->authorizeRequest();
        }
    }

    private function attachmentQuery(): Builder
    {
        $attachment = app(config('aura.resources.attachment'));

        abort_unless($attachment instanceof Model, 422, 'The media resource is invalid.');

        return $attachment->newQuery();
    }

    /**
     * @return list<string>
     */
    private function normalizeSelected(mixed $selected): array
    {
        if (! is_array($selected) || count($selected) > self::MAX_SELECTED_MEDIA) {
            abort(422, 'The selected media are invalid.');
        }

        $normalized = [];

        foreach ($selected as $id) {
            if ((! is_int($id) && ! is_string($id)) || (string) $id === '') {
                abort(422, 'The selected media are invalid.');
            }

            $normalized[(string) $id] = (string) $id;
        }

        if (count($normalized) !== count($selected)) {
            abort(422, 'The selected media are invalid.');
        }

        return array_values($normalized);
    }
}
