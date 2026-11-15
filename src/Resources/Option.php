<?php

namespace Aura\Base\Resources;

use Aura\Base\Models\Post;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Option extends Resource
{
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

    // Config rows, not entities: no avatar.
    public function avatarShape(): ?string
    {
        return null;
    }

    public static function byName($name)
    {
        return static::where('name', $name)->first();
    }

    /**
     * A readable label for the stored key, for lists: "user.1.sidebarToggled" is "Sidebar toggled",
     * "user.1.table_view.Attachment" is "Table view · Attachment", "team.1.settings" is "Team settings".
     * The key's first segment is put in words; later segments (resource names) stay as stored.
     * Display only: the key itself is never changed.
     */
    public function displayLabel(): string
    {
        $segments = $this->keySegments();

        if ($segments === []) {
            return (string) $this->name;
        }

        $first = Str::of(Str::snake(array_shift($segments)))->replace(['_', '-'], ' ')->squish()->lower()->ucfirst()->toString();

        // A team's or user's "settings" alone reads as the global settings page.
        if ($first === 'Settings' && $segments === [] && ($scope = $this->displayScope())) {
            $first = __($scope).' '.__('settings');
        }

        return implode(' · ', [$first, ...$segments]);
    }

    /**
     * Whose option this is, read from the key prefix: "User" for user.{id}.*, "Team" for team.{id}.*,
     * null for other keys.
     */
    public function displayScope(): ?string
    {
        return match (true) {
            (bool) preg_match('/^user\.\d+\./', (string) $this->name) => 'User',
            (bool) preg_match('/^team\.\d+\./', (string) $this->name) => 'Team',
            default => null,
        };
    }

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                // The key in words first, its scope, then the stored key in mono (displayLabel()).
                'display_view' => 'aura::components.fields.option-name-index',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Json',
                'validation' => 'required|json',
                // Lists show it as a one-line mono preview (table/row).
                'on_index' => true,
                'slug' => 'value',
                'decode' => false,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                // List only: the record page carries the date in its meta line.
                'name' => 'Updated',
                'type' => 'Aura\\Base\\Fields\\Datetime',
                'slug' => 'updated_at',
                'display_format' => 'M j, Y',
                'on_index' => true,
                'on_forms' => false,
                'on_view' => false,
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

    /**
     * Encode native values without interpreting application strings as JSON.
     *
     * JSON editor input is parsed by the field dehydration step before model
     * assignment. Keeping this mutator encoding-only preserves strings such
     * as "true" and "[]", while representing a native null as JSON `null`
     * for the non-nullable value column.
     */
    public function setValueAttribute($value): void
    {
        $this->attributes['value'] = json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
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

        static::saved(fn ($option) => $option->forgetCachedValue());
        static::deleted(fn ($option) => $option->forgetCachedValue());
    }

    /**
     * Forget the cache entries that readers of this option populate.
     *
     * Options edited directly (e.g. in the admin) bypass the helpers that
     * normally invalidate these keys, so a change would otherwise stay
     * invisible until the cache expires.
     */
    protected function forgetCachedValue(): void
    {
        // On rename, the readers still hold the old name's entries.
        foreach (array_filter(array_unique([(string) $this->getAttribute('name'), (string) $this->getOriginal('name')])) as $name) {
            $this->forgetCachedValueFor($name);
        }
    }

    private function forgetCachedValueFor(string $name): void
    {
        Cache::forget($name);
        Cache::forget('aura.'.$name);

        // Aura::getOption() caches team options as "{teamId}.aura.{key}".
        if (preg_match('/^team\.(\d+)\.(.+)$/', $name, $matches)) {
            Cache::forget($matches[1].'.aura.'.$matches[2]);
        }

        // Wildcard lookups (e.g. "user.1.columns.*") cache under their prefix.
        $segments = explode('.', $name);

        while (count($segments) > 1) {
            array_pop($segments);
            Cache::forget(implode('.', $segments).'.*');
        }
    }

    /**
     * The key's segments without a user.{id} or team.{id} prefix.
     *
     * @return list<string>
     */
    private function keySegments(): array
    {
        $name = (string) preg_replace('/^(user|team)\.\d+\./', '', (string) $this->name);

        return array_values(array_filter(explode('.', $name), fn (string $segment) => $segment !== ''));
    }
}
