<?php

namespace Aura\Base\Models;

use Aura\Base\SavedViews\SavedViewVisibility;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $context_key
 * @property string|null $default_key
 * @property string $name
 * @property int|string $owner_id
 * @property string $resource_type
 * @property int $schema_version
 * @property array<string, mixed> $state
 * @property int|string|null $team_id
 * @property SavedViewVisibility $visibility
 */
class SavedView extends Model
{
    protected $fillable = [
        'context_key',
        'default_key',
        'name',
        'owner_id',
        'resource_type',
        'schema_version',
        'state',
        'team_id',
        'visibility',
    ];

    protected $table = 'aura_saved_views';

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'schema_version' => 'integer',
            'state' => 'array',
            'visibility' => SavedViewVisibility::class,
        ];
    }
}
