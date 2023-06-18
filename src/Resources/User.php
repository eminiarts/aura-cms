<?php

namespace Eminiarts\Aura\Resources;

use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Database\Factories\UserFactory;
use Eminiarts\Aura\Models\User as UserModel;
use Eminiarts\Aura\Models\UserMeta;
use Eminiarts\Aura\Traits\SaveFieldAttributes;
use Eminiarts\Aura\Traits\SaveMetaFields;
use Eminiarts\Aura\Traits\SaveTerms;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class User extends UserModel
{
    use SaveFieldAttributes;
    use SaveMetaFields;
    use SaveTerms;

    public static ?string $slug = 'user';

    public static ?int $sort = 1;

    public static string $type = 'User';

    protected static ?string $group = 'Aura';

    protected $appends = ['fields'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected static $dropdown = 'Users';

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name', 'email', 'password', 'fields', 'current_team_id',
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

    protected static array $searchable = ['name', 'email'];

    protected $table = 'users';

    protected static bool $title = false;

    protected $with = ['meta'];

    public function actions()
    {
        return [
            'edit',
        ];
    }

    public function getAvatarUrlAttribute()
    {
        if (! $this->avatar) {
            return 'https://ui-avatars.com/api/?name='.$this->getInitials().'';
        }

        // json decode the meta value
        $meta = json_decode($this->avatar);

        // get the attachment from the meta
        $attachments = Attachment::find($meta);

        if ($attachments) {
            $attachment = $attachments->first();

            return $attachment->path('thumbnail');
        }

        return 'https://ui-avatars.com/api/?name='.$this->getInitials().'';
    }

        public function getBulkActions()
        {
            // get all flows with type "manual"

            $flows = Flow::where('trigger', 'manual')
                ->where('options->resource', $this->getType())
                ->get();

            foreach ($flows as $flow) {
                $this->bulkActions['callFlow.'.$flow->id] = $flow->name;
            }

            // dd($this->bulkActions);
            return $this->bulkActions;
        }

    public function getEmailField($value)
    {
        return "<a class='font-bold text-primary-500' href='mailto:".$value."'>".$value.'</a>';
    }

    public static function getFields()
    {
        return [
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'User details',
                'slug' => 'tab-user',
                'global' => true,
            ],
            [
                'name' => 'Personal Infos',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => 'required',
                'slug' => 'user-details',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Avatar',
                'type' => 'Eminiarts\\Aura\\Fields\\Image',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'avatar',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Email',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required|email',
                'on_index' => true,
                'slug' => 'email',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Roles',
                'slug' => 'roles',
                'resource' => 'Eminiarts\\Aura\\Resources\\Role',
                'type' => 'Eminiarts\\Aura\\Fields\\AdvancedSelect',
                'validation' => 'required',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => true,
            ],
            [
                'name' => 'Password',
                'type' => 'Eminiarts\\Aura\\Fields\\Password',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'password',
                'on_index' => false,
                'on_view' => false,
            ],
            [
                'name' => 'Send Welcome Email',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'validation' => '',
                'conditional_logic' => [],
                'on_edit' => false,
                'on_view' => false,
                'on_index' => false,
                'slug' => 'send-welcome-email',
                'instructions' => 'Do you want to inform the user about his account?',
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Notifications',
                'slug' => 'tab-notifications',
                'global' => true,
                'on_view' => false,
            ],
            [
                'name' => 'Notifications',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => 'required',
                'slug' => 'user-notifications-panel',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Notifications via Email',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'notifications_via_email',
            ],
            [
                'name' => 'Notifications via SMS',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'notifications_via_sms',
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Posts',
                'slug' => 'tab-posts',
                'global' => true,
            ],
            [
                'name' => 'Posts',
                'slug' => 'posts',
                'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
                'resource' => 'Eminiarts\\Aura\\Resources\\Post',
                'validation' => '',
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Teams',
                'slug' => 'tab-Teams',
                'global' => true,
                'conditional_logic' => [
                    function () {
                        return config('aura.teams');
                    },
                ],
            ],
            [
                'name' => 'Teams',
                'slug' => 'teams',
                'type' => 'Eminiarts\\Aura\\Fields\\BelongsToMany',
                'resource' => 'Eminiarts\\Aura\\Resources\\Team',
                'validation' => '',
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'conditional_logic' => [
                    function () {
                        return config('aura.teams');
                    },
                ],
                'on_view' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => '2FA',
                'label' => 'Tab',
                'slug' => '2fa',
                'global' => true,
                'on_view' => false,
            ],
            [
                'name' => '2FA',
                'type' => 'Eminiarts\\Aura\\Fields\\LivewireComponent',
                'component' => 'aura::user-two-factor-authentication-form',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => '2fa',
            ],

        ];
    }

    public function getFieldsAttribute()
    {
        $meta = $this->meta->pluck('value', 'key');

        $defaultValues = $this->inputFields()->pluck('slug')->mapWithKeys(fn ($value, $key) => [$value => null])->map(fn ($value, $key) => $meta[$key] ?? $value)->map(function ($value, $key) {
            // if the value is in $this->hidden, set it to null
            if (in_array($key, $this->hidden)) {
                return;
            }

            // If the value is set on the model, use it
            if (isset($this->attributes[$key])) {
                return $this->attributes[$key];
            }
        });

        if (! $meta->isEmpty()) {
            // Cast Attributes
            $meta = $meta->map(function ($value, $key) {
                // if there is a function get{Slug}Field on the model, use it
                $method = 'get'.Str::studly($key).'Field';

                if (method_exists($this, $method)) {
                    return $this->{$method}($value);
                }

                $class = $this->fieldClassBySlug($key);

                if ($class && method_exists($class, 'get')) {
                    return $class->get($class, $value);
                }

                return $value;
            });
        }

        return $defaultValues->merge($meta);
    }

    public function getIcon()
    {
        return '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>';
    }

    public function getInitials()
    {
        $name = $this->name;
        $words = explode(' ', $name);
        $initials = '';

        foreach ($words as $word) {
            if (strlen($initials) < 2) {
                $initials .= strtoupper(substr($word, 0, 1));
            } else {
                break;
            }
        }

        return $initials;
    }

    public function getRolesField()
    {
        return $this->roles->pluck('id')->toArray();
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public function hasPermissionTo($ability, $post): bool
    {
        $roles = $this->cachedRoles();

        if (! $roles) {
            return false;
        }

        foreach ($roles as $role) {
            $permissions = $role->fields['permissions'];

            // ray('roles', $permissions);

            if (empty($permissions)) {
                continue;
            }

            foreach ($permissions as $permission => $value) {
                if ($permission == $ability.'-'.$post::$slug && $value == true) {
                    return true;
                }
            }
        }

        return false;
    }

    public function hasRole(string $role): bool
    {
        $roles = $this->cachedRoles();

        if (! $roles) {
            return false;
        }

        foreach ($roles as $r) {
            if ($r->slug == $role) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the user has at least one role that is a super admin.
     */
    public function isSuperAdmin(): bool
    {
        $roles = $this->cachedRoles();

        if (! $roles) {
            return false;
        }

        foreach ($roles as $role) {
            if ($role->super_admin) {
                return true;
            }
        }

        return false;

        // Alternative way to do it, but it does a query

        // get Role where meta.key = 'super_admin' and meta.value = '1'
        $roles = $this->roles()->whereHas('meta', function ($query) {
            $query->where('key', 'super_admin')->where('value', '1');
        })->count();

        // return true if $roles count > 0
        return $roles > 0;
    }

    public function meta()
    {
        return $this->hasMany(UserMeta::class, 'user_id');
    }

    public function roles()
    {
        $roles = $this->belongsToMany(Role::class, 'user_meta', 'user_id', 'value')
            ->wherePivot('key', 'roles');

        return config('aura.teams') ? $roles->wherePivot('team_id', $this->current_team_id) : $roles;
    }

    public function setRolesField($value)
    {
        // Save the roles
        if (config('aura.teams')) {
            $this->roles()->syncWithPivotValues($value, ['key' => 'roles', 'team_id' => $this->current_team_id]);
        } else {
            $this->roles()->syncWithPivotValues($value, ['key' => 'roles']);
        }

        // Unset the roles field
        unset($this->attributes['fields']['roles']);

        // Clear Cache 'user.' . $this->id . '.roles'
        Cache::forget('user.'.$this->id.'.roles');

        return $this;
    }

    public function title()
    {
        return $this->name;
    }

      public function widgets()
      {
          return collect($this->getWidgets())->map(function ($item) {
              return $item;
          });
      }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            if (config('aura.teams') && !$user->current_team_id) {
                $user->current_team_id = auth()->user()?->current_team_id;
            }
        });

        static::saving(function ($user) {
            if (isset($user->attributes['fields'])) {
                foreach ($user->attributes['fields'] as $key => $value) {
                    $class = $user->fieldClassBySlug($key);

                    if ($class && method_exists($class, 'set')) {
                        $value = $class->set($value);
                    }

                    if (optional($user->attributes)[$key]) {
                        $user->{$key} = $value;
                    } else {
                        if (config('aura.teams')) {
                            $user->meta()->updateOrCreate(['key' => $key, 'team_id' => $user->current_team_id], ['value' => $value]);
                        } else {
                            $user->meta()->updateOrCreate(['key' => $key], ['value' => $value]);
                        }
                    }
                }

                unset($user->attributes['fields']);
            }

            if (isset($user->attributes['terms'])) {
                unset($user->attributes['terms']);
            }
        });
    }

   protected function cachedRoles(): mixed
   {
       return $this->roles;

       return Cache::remember($this->getCacheKeyForRoles(), now()->addMinutes(60), function () {
           return $this->roles;
       });
   }

    protected function getCacheKeyForRoles(): string
    {
        return $this->current_team_id.'.user.'.$this->id.'.roles';
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
