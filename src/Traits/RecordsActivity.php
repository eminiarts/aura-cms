<?php

namespace Aura\Base\Traits;

use Aura\Base\Fields\BelongsTo;
use Aura\Base\Fields\Date;
use Aura\Base\Fields\Datetime;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Status;
use Aura\Base\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Opt-in record history for a resource: a row in aura_activities when the record is created or
 * deleted, and one per tracked field (activityFields()) whose value changed on save. Every save
 * counts, whichever way it comes: the edit form, a kanban move, an action, code. The record page
 * shows the history as its Activity section.
 *
 * Needs the table from the published create_aura_activities_table migration; without it the
 * trait does nothing. Values are compared as stored, after the save pipeline has written them
 * (meta fields included), so a save that changes nothing records nothing.
 */
trait RecordsActivity
{
    /** Tracked values before the pending save, read from the database; null for a new record. */
    protected ?array $activityBefore = null;

    protected bool $activityCreating = false;

    /** The record's history, newest first. */
    public function activities(): Builder
    {
        return Activity::query()
            ->where('subject_type', $this->getMorphClass())
            ->where('subject_id', $this->getKey())
            ->latest('created_at')
            ->latest('id');
    }

    /**
     * The fields whose changes are recorded. Default: the list's Status, Select, Number, Date and
     * BelongsTo fields (on_index), the values a team follows. Override to track others.
     *
     * @return array<int, string>
     */
    public function activityFields(): array
    {
        $types = [Status::class, Select::class, Number::class, Date::class, Datetime::class, BelongsTo::class];

        return $this->fieldsCollection()
            ->filter(fn ($field) => ! empty($field['slug']) && ($field['on_index'] ?? true) !== false)
            ->filter(function ($field) use ($types) {
                $instance = $this->fieldClassBySlug($field['slug']);

                return $instance && collect($types)->contains(fn ($type) => $instance instanceof $type);
            })
            ->pluck('slug')
            ->values()
            ->all();
    }

    /**
     * Tracked values as comparable strings: arrays as JSON, numbers without trailing zeros, empty as null.
     *
     * @return array<string, string|null>
     */
    public function activityValues(): array
    {
        $values = [];

        foreach ($this->activityFields() as $slug) {
            $values[$slug] = static::normalizeActivityValue($this->{$slug});
        }

        return $values;
    }

    public static function bootRecordsActivity(): void
    {
        static::saving(fn ($model) => $model->snapshotActivity());

        static::deleted(fn ($model) => $model->writeActivity('deleted'));
    }

    /**
     * Called last in the saved step of the save pipeline (SaveFieldAttributes), after meta fields
     * are written, so the comparison sees what the save stored.
     */
    public function recordActivityAfterSave(): void
    {
        if ($this->activityCreating) {
            $this->activityCreating = false;
            $this->writeActivity('created');

            return;
        }

        if ($this->activityBefore === null || ! Activity::tableExists()) {
            return;
        }

        $before = $this->activityBefore;
        $this->activityBefore = null;

        $fresh = static::withoutGlobalScopes()->find($this->getKey());

        if (! $fresh) {
            return;
        }

        foreach ($fresh->activityValues() as $slug => $new) {
            $old = $before[$slug] ?? null;

            if ($old !== $new) {
                $this->writeActivity('updated', $slug, $old, $new);
            }
        }
    }

    public function snapshotActivity(): void
    {
        $this->activityBefore = null;
        $this->activityCreating = ! $this->exists;

        if (! $this->exists || ! Activity::tableExists()) {
            return;
        }

        $this->activityBefore = static::withoutGlobalScopes()->find($this->getKey())?->activityValues();
    }

    public function writeActivity(string $event, ?string $field = null, ?string $old = null, ?string $new = null): void
    {
        if (! Activity::tableExists()) {
            return;
        }

        $user = auth()->user();

        Activity::query()->create([
            'team_id' => $this->getAttribute('team_id') ?? $user?->current_team_id,
            'user_id' => $user?->getKey(),
            'subject_type' => $this->getMorphClass(),
            'subject_id' => $this->getKey(),
            'event' => $event,
            'field' => $field,
            'old' => $old,
            'new' => $new,
            'created_at' => now(),
        ]);
    }

    protected static function normalizeActivityValue(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($value === null || $value === '' || $value === '[]') {
            return null;
        }

        $value = (string) $value;

        // 84500.00 and 84500 are the same amount.
        if (is_numeric($value) && Str::contains($value, '.')) {
            $value = rtrim(rtrim($value, '0'), '.');
        }

        return $value;
    }
}
