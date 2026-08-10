<?php

namespace Aura\Base\Resources;

use Aura\Base\Aura;
use Aura\Base\Models\Post;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\SoftDeletes;

class Option extends Resource
{
    use SoftDeletes;

    public const EVERYONE_TEAM_ID = 0;

    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'option';

    public static string $type = 'Option';

    protected $casts = [
        'value' => 'array',
    ];

    protected $fillable = ['name', 'value', 'team_id'];

    protected static ?string $group = 'Aura';

    protected $table = 'options';

    public static function byName($name)
    {
        return static::where('name', $name)->first();
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => 'required',
                'on_index' => false,
                'slug' => 'value',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Logo',
                'type' => 'Aura\\Base\\Fields\\Image',
                'on_create' => false,
                'on_edit' => false,
                'on_index' => false,
                'slug' => 'logo',
            ],
            [
                'name' => 'Logo Darkmode',
                'type' => 'Aura\\Base\\Fields\\Image',
                'on_create' => false,
                'on_edit' => false,
                'on_index' => false,
                'slug' => 'logo-darkmode',
            ],
        ];
    }

    public function getIcon()
    {
        return view('aura::components.icon.option')->render();
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public static function isEveryoneTeamId(mixed $teamId): bool
    {
        return $teamId !== null
            && $teamId !== ''
            && (string) $teamId === (string) self::EVERYONE_TEAM_ID;
    }

    /**
     * Persist logical null as JSON `null` because the option value column is
     * intentionally non-nullable and SQL null means no stored row to callers.
     */
    public function setValueAttribute(mixed $value): void
    {
        $this->attributes['value'] = json_encode(
            $value,
            JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        );
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        if (config('aura.teams')) {
            static::addGlobalScope(app(TeamScope::class));
        }

        static::saving(function ($option) {

            if (config('aura.teams') && ! isset($option->team_id) && auth()->user()) {
                $option->team_id = auth()->user()->current_team_id;
            }

            // unset post attributes
            unset($option->title);
            unset($option->content);
            unset($option->user_id);
            unset($option->type);
        });

        static::created(fn (Option $option) => $option->invalidateCacheScopes());
        static::updated(fn (Option $option) => $option->invalidateCacheScopes(includeOriginal: true));
        static::deleted(fn (Option $option) => $option->invalidateCacheScopes(includeOriginal: true));
        static::registerModelEvent(
            'restored',
            fn (Option $option) => $option->invalidateCacheScopes(includeOriginal: true),
        );
    }

    protected function invalidateCacheScopes(bool $includeOriginal = false): void
    {
        $connection = $this->getConnection();

        if (! config('aura.teams')) {
            Aura::clearGlobalOptionCache($connection);
            User::clearOptionCacheForScope('global', $connection);

            return;
        }

        $teamIds = collect([$this->getAttribute('team_id')]);

        if ($includeOriginal) {
            $teamIds->push($this->getRawOriginal('team_id'));
        }

        if ($teamIds->contains(fn ($teamId): bool => $teamId === null
            || $teamId === ''
            || self::isEveryoneTeamId($teamId))) {
            Aura::clearGlobalOptionCache($connection);
        }

        $teamIds
            ->filter(fn ($teamId): bool => $teamId !== null
                && $teamId !== ''
                && ! self::isEveryoneTeamId($teamId))
            ->unique()
            ->each(function ($teamId) use ($connection): void {
                Team::clearOptionCacheForTeam($teamId, $connection);
                User::clearOptionCacheForScope($teamId, $connection);
            });
    }
}
