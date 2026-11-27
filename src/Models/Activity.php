<?php

namespace Aura\Base\Models;

use Aura\Base\Fields\BelongsTo as BelongsToField;
use Aura\Base\Fields\Date;
use Aura\Base\Fields\Datetime;
use Aura\Base\Fields\Number;
use Aura\Base\Fields\Select;
use Aura\Base\Fields\Status;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

/**
 * One entry in a record's history (Aura\Base\Traits\RecordsActivity): the record was created or
 * deleted, or one tracked field changed from `old` to `new` (raw stored values).
 *
 * @property int $id
 * @property int|null $team_id
 * @property int|null $user_id
 * @property string $subject_type
 * @property int $subject_id
 * @property string $event
 * @property string|null $field
 * @property string|null $old
 * @property string|null $new
 * @property Carbon|null $created_at
 */
class Activity extends Model
{
    public const UPDATED_AT = null;

    protected $casts = ['created_at' => 'datetime'];

    protected $guarded = [];

    protected $table = 'aura_activities';

    /**
     * A stored value as the record page shows it: an option as its tag (a status with its dot), a
     * date in the field's display format, a number with its unit, a relation as its chip. HTML.
     */
    public static function displayValue($subject, string $slug, ?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $field = $subject->fieldBySlug($slug) ?? [];
        $instance = $subject->fieldClassBySlug($slug);

        // A field's own display view (a priority's bar mark) renders the stored value, not the current one.
        if (! empty($field['display_view'])) {
            return view($field['display_view'], ['row' => $subject, 'field' => $field, 'value' => $value])->render();
        }

        if ($instance instanceof Select || $instance instanceof Status) {
            $options = collect($instance instanceof Select ? $instance->options($subject, $field) : ($field['options'] ?? []));
            $option = $options->isNotEmpty() && is_array($options->first())
                ? $options->firstWhere('key', $value)
                : ($options->has($value) ? ['value' => $options->get($value)] : null);
            $label = e(__($option['value'] ?? $value));

            if (empty($option['color'])) {
                return '<span class="aura-tag">'.$label.'</span>';
            }

            $dot = $instance instanceof Status ? '<svg class="aura-tag-dot" viewBox="0 0 6 6" aria-hidden="true"><circle cx="3" cy="3" r="3" /></svg>' : '';

            return '<span class="aura-tag aura-tag-status '.e($option['color']).'">'.$dot.$label.'</span>';
        }

        if ($instance instanceof Date || $instance instanceof Datetime) {
            try {
                $format = $field['display_format'] ?? ($instance instanceof Datetime ? 'M j, Y H:i' : 'M j, Y');

                return e(Carbon::parse($value)->format($format));
            } catch (\Throwable) {
                return e($value);
            }
        }

        if ($instance instanceof Number) {
            return (string) Number::unitMarkup($field, (string) $instance->display($field, $value, $subject));
        }

        if ($instance instanceof BelongsToField) {
            return (string) $instance->display($field, $value, $subject);
        }

        return e($value);
    }

    /** The table comes from a published migration stub; without it nothing is recorded or shown. */
    public static function tableExists(): bool
    {
        try {
            return Schema::hasTable((new static)->getTable());
        } catch (\Throwable) {
            return false;
        }
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('aura.resources.user'), 'user_id');
    }
}
