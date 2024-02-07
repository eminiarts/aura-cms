<?php

namespace Eminiarts\Aura\Models;

use Eminiarts\Aura\Resources\Option;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Traits\AuraModelConfig;
use Eminiarts\Aura\Traits\InputFields;
use Eminiarts\Aura\Traits\InteractsWithTable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    // Aura
    use AuraModelConfig;
    use HasApiTokens;
    use HasFactory;
    use Impersonate;
    use InputFields;
    use InteractsWithTable;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        // 'profile_photo_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * Determine if the user belongs to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function belongsToTeam($team)
    {
        if (is_null($team)) {
            return false;
        }

        return $this->teams->contains(function ($t) use ($team) {
            return $t->id === $team->id;
        });
    }

    public function canBeImpersonated()
    {
        return ! $this->resource->isSuperAdmin();
    }

    public function canImpersonate()
    {
        return $this->resource->isSuperAdmin();
    }

    public function clearCachedOption($option)
    {
        $option = 'user.'.$this->id.'.'.$option;

        Cache::forget($option);
    }

    // Reset to default create Method from Laravel
    public static function create($fields)
    {
        $model = new static();

        return tap($model->newModelInstance($fields), function ($instance) {
            $instance->save();
        });
    }

    /**
     * Get the current team of the user's context.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentTeam()
    {
        if (! config('aura.teams')) {
            return;
        }

        if (is_null($this->current_team_id) && $this->id) {
            $this->switchTeam($this->personalTeam());
        }

        return $this->belongsTo(config('aura.resources.team'), 'current_team_id');
    }

    public function deleteOption($option)
    {
        $option = 'user.'.$this->id.'.'.$option;

        Option::whereName($option)->delete();

        Cache::forget($option);

    }

    public function getOption($option)
    {
        $option = 'user.'.$this->id.'.'.$option;

        // If there is a * at the end of the option name, it means that it is a wildcard
        // and we need to get all options that match the wildcard
        if (substr($option, -1) == '*') {
            $o = substr($option, 0, -1);

            // Cache
            $options = Cache::remember($option, now()->addHour(), function () use ($o) {
                return Option::where('name', 'like', $o.'%')->get();
            });

            // Map the options, set the key to the option name (everything after last dot ".") and the value to the option value
            return $options->mapWithKeys(function ($item, $key) {
                return [str($item->name)->afterLast('.')->toString() => $item->value];
            });
        }

        // Cache
        $model = Cache::remember($option, now()->addHour(), function () use ($option) {
            return Option::whereName($option)->first();
        });

        if ($model) {
            return $model->value;
        }
    }

    public function getOptionBookmarks()
    {
        // Cache
        $option = Cache::remember('user.'.$this->id.'.bookmarks', now()->addHour(), function () {
            return Option::whereName('user.'.$this->id.'.bookmarks')->first();
        });

        if ($option) {
            return $option->value;
        }

        return [];
    }

    public function getOptionColumns($slug)
    {
        // Cache
        $option = Cache::remember('user.'.$this->id.'.columns.'.$slug, now()->addHour(), function () use ($slug) {
            return Option::whereName('user.'.$this->id.'.columns.'.$slug)->first();
        });

        if ($option) {
            return $option->value;
        }

        return [];
    }

    public function getOptionSidebar()
    {
        // Cache
        $option = Cache::remember('user.'.$this->id.'.sidebar', now()->addHour(), function () {
            return Option::whereName('user.'.$this->id.'.sidebar')->first();
        });

        if ($option) {
            return $option->value;
        }

        return [];
    }

    public function getOptionSidebarToggled()
    {
        // Cache
        $option = Cache::remember('user.'.$this->id.'.sidebarToggled', now()->addHour(), function () {
            return Option::whereName('user.'.$this->id.'.sidebarToggled')->first();
        });

        if ($option) {
            return $option->value;
        }

        return true;
    }

    public function getTeams()
    {
        if (! config('aura.teams')) {
            return;
        }

        // Return cached teams with meta
        return Cache::remember('user.'.$this->id.'.teams', now()->addHour(), function () {
            return $this->teams()->with('meta')->get();
        });
    }

    public function indexQuery($query)
    {
        // Query where user_meta key = roles and team_id = auth()->user()->current_team_id
        if (config('aura.teams')) {
            return $query->whereHas('meta', function ($query) {
                $query->where('key', 'roles')->where('team_id', auth()->user()->current_team_id);
            });
        }

        return $query->whereHas('meta', function ($query) {
            $query->where('key', 'roles');
        });
    }

    /**
     * Determine if the given team is the current team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function isCurrentTeam($team)
    {
        return $team->id === $this->currentTeam->id;
    }

    /**
     * Determine if the user owns the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function ownsTeam($team)
    {
        if (is_null($team)) {
            return false;
        }

        return $this->id == $team->{$this->getForeignKey()};
    }

    public function resource()
    {
        // Return \Eminiarts\Aura\Resources\User for this user
        if (config('aura.resources.user')) {
            return $this->hasOne(config('aura.resources.user'), 'id', 'id');
        } else {
            return $this->hasOne(\Eminiarts\Aura\Resources\User::class, 'id', 'id');
        }

        // Cache the resource so we don't have to query the database every time
        return Cache::remember('user.resource.'.$this->id, now()->addHour(), function () {
            return \Eminiarts\Aura\Resources\User::find($this->id);
        });
    }

    /**
     * Switch the user's context to the given team.
     *
     * @param  mixed  $team
     * @return bool
     */
    public function switchTeam($team)
    {
        if (! $this->belongsToTeam($team)) {
            return false;
        }

        $this->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        $this->setRelation('currentTeam', $team);

        return true;
    }

    /**
     * Get all of the teams the user belongs to.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class, 'user_meta', 'user_id', 'team_id')->wherePivot('key', 'roles');
    }

    public function updateOption($option, $value)
    {
        $option = 'user.'.$this->id.'.'.$option;

        Option::updateOrCreate(['name' => $option], ['value' => $value]);

        ray('updateOption', $option, $value)->red();

        // Clear the cache
        Cache::forget($option);
    }
}
