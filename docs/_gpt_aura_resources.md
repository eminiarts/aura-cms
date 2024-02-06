I will provide you with more information about Aura CMS: It is build on Laravel, Livewire, AlpineJS and TailwindCSS. It requires PHP 8.1. 

- Resources: We have a Resource model that can be used for blog posts, news, events, etc. The posts can be categorized and tagged. The concept is based on WordPress' Posts and Taxonomies. A Resource only has a title, a slug, a description and a body (default WordPress fields). All other fields are added by the user like you would add custom fields with ACF in Wordpress (conceptually). This is why we have a posts and postmeta table. The postmeta table is used to store all additional fields. This means that a Resource can have many postmeta entries. The postmeta entries are used to store additional fields like a date, a location, a price, etc.
- Posttype Builder: The Posttype Builder is a tool that allows the user to create custom posttypes. A posttype is a custom Resource. The user can add custom fields to the posttype. The user can also add custom taxonomies to the posttype. In the beginning we encourage to use the posts table for a Resource. When ever you are done with all fields, you can generate a migration file that will create a new table for the posttype. This way you can use the Resource with a custom Table. This is useful when you have a lot of data and want to optimize the database.
- Taxonomies: Taxonomies are the method of classifying content and data. When you use a taxonomy you’re grouping similar things together. The taxonomy refers to the sum of those groups. As with Post Types, there are a number of default taxonomies, and you can also create your own.ies 
- Fields: We have a lot of different fields that you can use on the Resources. These are the fields that we have: Advanced Select, BelongsTo, BelongsToMany, Boolean, Checkbox, Code, Color, Date, Datetime, Email, Embed, Field, File, Group, HasMany, HasOne, HasOneOfMany, Heading, HorizontalLine, Image, Jobs, chp Json, LivewireComponent, Number, Panel, Password, Permissions, Phone, Radio, Repeater, Select, SelectRelation, Slug, Tab, Tabs, Tags, Text, Textarea, Time, View, ViewValue,Wysiwyg. Every Field needs to be documented.
- Actions: A resource can have bulkActions or actions defined that you can trigger on the resource. For example: You can create an action that will send an email to all users that are selected. You can also create an action that will delete all selected users. 
- Table: The table is the default view of a resource. You can customize the table by adding a custom View for the row. There are multiple places where you can override the default table view. You can override the table view for a specific resource, you can override the table view for a specific posttype or you can override the table view for all resources. The table also includes a search and custom filters. The filters are based on the fields that are defined on the resource. 
- Teams, Users, Roles and Permissions: Aura is multitenant by default. You can create teams and invite users to the team. The users can have different roles on the team. The roles can be defined by the user. The user can also define permissions for the roles. You can disable multitenant if you want to use Aura as a single tenant application. Permissions can be created for all resources.
- Media Library: AuraCMS has a media library that allows you to upload files and images. These files and images can be used in the resources.
- Flows: will be documented later.

Resources: I will show you some examples, so you have an understanding on how a Resource can be structured:

User Resource:

```php
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

    protected static ?string $group = 'Admin';

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

    public function hasAnyRole(array $roles): bool
    {
        $cachedRoles = $this->cachedRoles()->pluck('slug');

        if (! $cachedRoles) {
            return false;
        }

        foreach ($cachedRoles as $role) {
            if (in_array($role, $roles)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission($permission)
    {
        $roles = $this->cachedRoles();

        if (! $roles) {
            return false;
        }

        foreach ($roles as $role) {
            if ($role->super_admin) {
                return true;
            }

            $permissions = $role->fields['permissions'];

            if (empty($permissions)) {
                continue;
            }

            foreach ($permissions as $p => $value) {
                if ($p == $permission && $value == true) {
                    return true;
                }
            }
        }

        return false;
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

        // dump($roles->toArray());

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
            if (config('aura.teams') && ! $user->current_team_id) {
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

```

Post Resource:

```php
<?php

namespace Eminiarts\Aura\Resources;

use Aura\Export\Traits\Exportable;
use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Database\Factories\PostFactory;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Widgets\AvgPostsNumber;
use Eminiarts\Aura\Widgets\PostChart;
use Eminiarts\Aura\Widgets\SumPostsNumber;
use Eminiarts\Aura\Widgets\TotalPosts;

class Post extends Resource
{
    use Exportable;

    public array $actions = [
        'delete' => [
            'label' => 'Delete',
            'icon-view' => 'aura::components.actions.trash',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
            'confirm' => true,
            'confirm-title' => 'Delete Post?',
            'confirm-content' => 'Are you sure you want to delete this post?',
            'confirm-button' => 'Delete',
            'confirm-button-class' => 'ml-3 bg-red-600 hover:bg-red-700',
        ],
        'testAction' => [
            'label' => 'Test Action',
            'class' => 'hover:text-primary-700 text-primary-500 font-bold',
            'confirm' => true,
            'confirm-title' => 'Test Action Post?',
            'confirm-content' => 'Are you sure you want to test Action?',
            'confirm-button' => 'Yup',
        ],
    ];

    public array $bulkActions = [
        'deleteSelected' => 'Delete',
        'multipleExportSelected' => [
            'label' => 'Export',
            'modal' => 'export::export-selected-modal',
        ],
    ];

    public static $fields = [];

    public static ?string $slug = 'post';

    public static ?int $sort = 50;

    public static string $type = 'Post';

    protected $hidden = ['password'];

    public array $widgetSettings = [
        'default' => '30d',
        'options' => [
            '1d' => '1 Day',
            '7d' => '7 Days',
            '30d' => '30 Days',
            '60d' => '60 Days',
            '90d' => '90 Days',
            '180d' => '180 Days',
            '365d' => '365 Days',
            'all' => 'All',
            'ytd' => 'Year to Date',
            'qtd' => 'Quarter to Date',
            'mtd' => 'Month to Date',
            'wtd' => 'Week to Date',
            'last-year' => 'Last Year',
            'last-month' => 'Last Month',
            'last-week' => 'Last Week',
            'custom' => 'Custom',
        ],
    ];

    protected static ?string $group = 'Aura';

    protected static array $searchable = [
        'title',
        'content',
    ];

    public function callFlow($flowId)
    {
        $flow = Flow::find($flowId);
        // dd('callManualFlow', $flow->name);
        $operation = $flow->operation;

        // Create a Flow Log
        $flowLog = $flow->logs()->create([
            'post_id' => $this->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Run the Operation
        $operation->run($this, $flowLog->id);
    }

    public function delete()
    {
        // parent delete
        parent::delete();

        // redirect to index page
        return redirect()->route('aura.post.index', [$this->getType()]);
    }

    public function deleteSelected()
    {
        parent::delete();
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

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'global' => true,
                'conditional_logic' => [
                ],
                'slug' => 'tab1',
            ],
            [
                'name' => 'Panel',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [
                ],
                'slug' => 'panel1',
                'style' => [
                    'width' => '70',
                ],
            ],
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Text',
                'slug' => 'text',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Slug for Test',
                'type' => 'Eminiarts\\Aura\\Fields\\Slug',
                'validation' => 'required|alpha_dash',
                'conditional_logic' => [
                ],
                'slug' => 'slug',
                'based_on' => 'title',
            ],
            [
                'name' => 'Bild',
                'type' => 'Eminiarts\\Aura\\Fields\\Image',
                'max' => 1,
                'upload' => true,
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'image',
            ],
            [
                'name' => 'Password for Test',
                'type' => 'Eminiarts\\Aura\\Fields\\Password',
                'validation' => 'nullable|min:8',
                'conditional_logic' => [
                ],
                'slug' => 'password',
                'hydrate' => function ($set, $model, $state, $get) {
                    dd('hier set');
                },
                'on_index' => false,
                'on_forms' => true,
                'on_view' => false,
            ],
            [
                'name' => 'Number',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'number',
                'on_view' => true,
                'on_forms' => true,
                'on_index' => true,
            ],
            [
                'name' => 'Date',
                'type' => 'Eminiarts\\Aura\\Fields\\Date',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'date',
                'format' => 'y-m-d',
            ],
            [
                'name' => 'Description',
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'description',
                'style' => [
                    'width' => '100',
                ],
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            //  [
            //      'name' => 'Color',
            //      'type' => 'Eminiarts\\Aura\\Fields\\Color',
            //      'validation' => '',
            //      'conditional_logic' => [
            //      ],
            //      'slug' => 'color',
            //      'on_index' => true,
            //      'on_forms' => true,
            //      'on_view' => true,
            //      'format' => 'hex',
            //  ],
            [
                'name' => 'Sidebar',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [
                ],
                'slug' => 'sidebar',
                'style' => [
                    'width' => '30',
                ],
            ],
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Eminiarts\\Aura\\Fields\\Tags',
                'model' => 'Eminiarts\\Aura\\Taxonomies\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Categories',
                'slug' => 'categories',
                'type' => 'Eminiarts\\Aura\\Fields\\Tags',
                'model' => 'Eminiarts\\Aura\\Taxonomies\\Category',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            //  [
            //      'name' => 'Team',
            //      'slug' => 'team_id',
            //      'type' => 'Eminiarts\\Aura\\Fields\\BelongsTo',
            //      'resource' => 'Eminiarts\\Aura\\Resources\\Team',
            //      'validation' => '',
            //      'conditional_logic' => [
            //          [
            //              'field' => 'role',
            //              'operator' => '==',
            //              'value' => 'super_admin',
            //          ],
            //      ],
            //      'wrapper' => '',
            //      'on_index' => true,
            //      'on_forms' => true,
            //      'on_view' => true,
            //  ],
            [
                'name' => 'User',
                'slug' => 'user_id',
                'type' => 'Eminiarts\\Aura\\Fields\\BelongsTo',
                'resource' => 'Eminiarts\\Aura\\Resources\\User',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'name' => 'Attachments',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'global' => true,
                'conditional_logic' => [
                ],
                'slug' => 'tab2',
            ],
            [
                'name' => 'Attachments',
                'slug' => 'attachments',
                'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
                'resource' => 'Eminiarts\\Aura\\Resources\\Attachment',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
            // [
            //     'name' => 'Created at',
            //     'slug' => 'created_at',
            //     'type' => 'Eminiarts\\Aura\\Fields\\Date',
            //     'validation' => '',
            //     'enable_time' => true,
            //     'conditional_logic' => [],
            //     'wrapper' => '',
            //     'on_index' => true,
            //     'on_forms' => true,
            //     'on_view' => true,
            // ],
            // [
            //     'name' => 'Updated at',
            //     'slug' => 'updated_at',
            //     'type' => 'Eminiarts\\Aura\\Fields\\Date',
            //     'validation' => '',
            //     'conditional_logic' => [],
            //     'wrapper' => '',
            //     'enable_time' => true,
            //     'on_index' => true,
            //     'on_forms' => true,
            //     'on_view' => true,
            // ],
        ];
    }

    public function getIcon()
    {
        return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>';
    }

    public static function getWidgets(): array
    {
        return [
            [
                'name' => 'Total Posts Created',
                'slug' => 'total_posts_created',
                'type' => 'Eminiarts\\Aura\\Widgets\\ValueWidget',
                'method' => 'count',
                'cache' => 300,
                'style' => [
                    'width' => '33.33',
                ],
                'conditional_logic' => [],
            ],
            [
                'name' => 'Average Number',
                'slug' => 'average_number',
                'type' => 'Eminiarts\\Aura\\Widgets\\ValueWidget',
                'method' => 'avg',
                'column' => 'number',
                'cache' => 300,
                'style' => [
                    'width' => '33.33',
                ],
                'conditional_logic' => [],
            ],
            [
                'name' => 'Sum Number',
                'slug' => 'sum_number',
                'type' => 'Eminiarts\\Aura\\Widgets\\ValueWidget',
                'method' => 'sum',
                'column' => 'number',
                'goal' => 2000,
                'dailygoal' => false,
                'cache' => 300,
                'style' => [
                    'width' => '33.33',
                ],
                'conditional_logic' => [],
            ],
            [
                'name' => 'Sparkline Bar Chart',
                'slug' => 'sparkline_bar_chart',
                'type' => 'Eminiarts\\Aura\\Widgets\\SparklineBar',
                'cache' => 300,
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],

            [
                'name' => 'Sparkline Area',
                'slug' => 'sparkline_area',
                'type' => 'Eminiarts\\Aura\\Widgets\\SparklineArea',
                'cache' => 300,
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],

            [
                'name' => 'Donut Chart',
                'slug' => 'donut',
                'type' => 'Eminiarts\\Aura\\Widgets\\Donut',
                'cache' => 300,
                // 'values' => function () {
                //     return [
                //         'value1' => 10,
                //         'value2' => 20,
                //         'value3' => 30,
                //     ];
                // },
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],
            [
                'name' => 'Pie Chart',
                'slug' => 'pie',
                'type' => 'Eminiarts\\Aura\\Widgets\\Pie',
                'cache' => 300,
                'column' => 'number',
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],

            [
                'name' => 'Bar Chart',
                'slug' => 'bar',
                'type' => 'Eminiarts\\Aura\\Widgets\\Bar',
                'cache' => 300,
                'column' => 'number',
                'style' => [
                    'width' => '50',
                ],
                'conditional_logic' => [],
            ],
        ];
    }

    public function testAction()
    {
        //dd('hier');
    }

    // public static function getWidgets(): array
    // {
    //     return [
    //         // new TotalPosts(['width' => 'w-full md:w-1/3']),
    //         // new SumPostsNumber(['width' => 'w-full md:w-1/3']),
    //         // new AvgPostsNumber(['width' => 'w-full md:w-1/3']),
    //         new PostChart(['width' => 'w-full md:w-1/3']),
    //     ];
    // }

    public function title()
    {
        return optional($this)->title." (Post #{$this->id})";
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PostFactory::new();
    }
}

```

Team Resource:

```php
<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Database\Factories\TeamFactory;
use Eminiarts\Aura\Models\TeamMeta;
use Eminiarts\Aura\Resource;
use Illuminate\Support\Facades\Cache;

class Team extends Resource
{
    public array $actions = [
        'delete' => [
            'label' => 'Delete',
            'icon-view' => 'aura::components.actions.trash',
            'class' => 'hover:text-red-700 text-red-500 font-bold',
        ],
    ];

    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'team';

    public static string $type = 'Team';

    protected $fillable = [
        'name', 'user_id', 'fields',
    ];

    protected static ?string $group = 'Aura';

    protected $table = 'teams';

    protected static bool $title = false;

    public function clearCachedOption($option)
    {
        $option = 'team.'.$this->id.'.'.$option;

        Cache::forget($option);
    }

    public function customPermissions()
    {
        return [
            'invite-users' => 'Invite users to team',
        ];
    }

    public function deleteOption($option)
    {
        $option = 'team.'.$this->id.'.'.$option;

        Option::whereName($option)->delete();

        Cache::forget($option);
    }

    public static function getFields()
    {
        return [

            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Team',
                'slug' => 'tab-team',
                'global' => true,
            ],
            [
                'name' => 'Team',
                'slug' => 'team-panel',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => [],
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
                'name' => 'Description',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'on_index' => true,
                'slug' => 'description',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'name' => 'Users',
                'slug' => 'tab-users',
                'global' => true,
                'on_create' => false,
            ],
            [
                'name' => 'Users',
                'slug' => 'users',
                'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
                'resource' => 'Eminiarts\\Aura\\Resources\\User',
                'validation' => '',
                'conditional_logic' => [],
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '100',
                    'class' => '!p-4',
                ],
            ],
            [
                'name' => 'Invitations',
                'slug' => 'tab-Invitations',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'global' => true,
                'validation' => '',
                'conditional_logic' => [],
                'on_create' => false,
            ],
            [
                'name' => 'Invitations',
                'slug' => 'Invitations',
                'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
                'resource' => 'Eminiarts\\Aura\\Resources\\TeamInvitation',
                'validation' => '',
                'conditional_logic' => [],
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '100',
                    'class' => '!p-4',
                ],
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15 21V15.6C15 15.0399 15 14.7599 14.891 14.546C14.7951 14.3578 14.6422 14.2049 14.454 14.109C14.2401 14 13.9601 14 13.4 14H10.6C10.0399 14 9.75992 14 9.54601 14.109C9.35785 14.2049 9.20487 14.3578 9.10899 14.546C9 14.7599 9 15.0399 9 15.6V21M19 21V6.2C19 5.0799 19 4.51984 18.782 4.09202C18.5903 3.71569 18.2843 3.40973 17.908 3.21799C17.4802 3 16.9201 3 15.8 3H8.2C7.07989 3 6.51984 3 6.09202 3.21799C5.71569 3.40973 5.40973 3.71569 5.21799 4.09202C5 4.51984 5 5.0799 5 6.2V21M21 21H3M9.5 8H9.51M14.5 8H14.51M10 8C10 8.27614 9.77614 8.5 9.5 8.5C9.22386 8.5 9 8.27614 9 8C9 7.72386 9.22386 7.5 9.5 7.5C9.77614 7.5 10 7.72386 10 8ZM15 8C15 8.27614 14.7761 8.5 14.5 8.5C14.2239 8.5 14 8.27614 14 8C14 7.72386 14.2239 7.5 14.5 7.5C14.7761 7.5 15 7.72386 15 8Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public function getOption($option)
    {
        $option = 'team.'.$this->id.'.'.$option;

        // If there is a * at the end of the option name, it means that it is a wildcard
        // and we need to get all options that match the wildcard
        if (substr($option, -1) == '*') {
            $o = substr($option, 0, -1);

            // Cache
            $options = Cache::remember($option, now()->addHour(), function () use ($o) {
                return Option::where('name', 'like', $o.'%')->orderBy('id')->get();
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

    public static function getWidgets(): array
    {
        return [];
    }

    public function meta()
    {
        return $this->hasMany(TeamMeta::class, 'team_id');
    }

    public function teamInvitations()
    {
        return $this->hasMany(TeamInvitation::class, 'team_id');
    }

    public function title()
    {
        return $this->name;
    }

    public function updateOption($option, $value)
    {
        $option = 'team.'.$this->id.'.'.$option;

        Option::updateOrCreate(['name' => $option], ['value' => $value]);

        Cache::forget($option);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_meta', 'team_id', 'user_id')->wherePivot('key', 'roles');
    }

    protected static function booted()
    {
        static::saving(function ($team) {
            // unset title attribute
            unset($team->title);
            unset($team->content);
            unset($team->type);
            unset($team->team_id);

            if (! $team->user_id && auth()->user()) {
                $team->user_id = auth()->user()->id;
            }
        });

        static::creating(function ($team) {
            // dd('creating', $team);
        });

        static::created(function ($team) {
            if ($user = auth()->user()) {
                // Change the current team id of the user
                $user->current_team_id = $team->id;
                $user->save();
            }

            // Create a Super Admin role for the team
            $role = Role::create([
                'type' => 'Role',
                'title' => 'Super Admin',
                'slug' => 'super_admin',
                'name' => 'Super Admin',
                'description' => 'Super Admin has can perform everything.',
                'super_admin' => true,
                'permissions' => [],
                'user_id' => $user ? $user->id : null,
                'team_id' => $team->id,
            ]);

            // Attach the current user to the team
            if ($user) {

                $team->users()->attach($user->id, [
                    'key' => 'roles',
                    'value' => $role->id,
                ]);

                // Clear cache of Cache('user.'.$this->id.'.teams')
                Cache::forget('user.'.$user->id.'.teams');

            }
        });

        static::deleted(function ($team) {
            // Get all users who had the deleted team as their current team
            $users = User::where('current_team_id', $team->id)->get();

            // Loop through the users and update their current_team_id
            foreach ($users as $user) {
                $firstTeam = $user->teams()->first();
                $user->current_team_id = $firstTeam ? $firstTeam->id : null;
                $user->save();
            }

            // Delete all the team's roles
            // $team->roles()->delete();

            // Delete all the team's metas
            $team->meta()->delete();

            // Delete all the team's invitations
            $team->teamInvitations()->delete();

            // Delete all the team's options
            Option::where('name', 'like', 'team.'.$team->id.'.%')->delete();

            // Clear cache of Cache('user.'.$this->id.'.teams')
            Cache::forget('user.'.auth()->user()->id.'.teams');

            // Redirect to the dashboard
            return redirect()->route('aura.dashboard');
        });

       
    }

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return TeamFactory::new();
    }
}

```

Attachment Resource:

```php
<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Jobs\GenerateImageThumbnail;
use Eminiarts\Aura\Resource;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Attachment extends Resource
{
    use DispatchesJobs;

    public static $contextMenu = false;

    public static ?string $name = 'Media';

    public static ?string $slug = 'attachment';

    public static ?int $sort = 2;

    public static string $type = 'Attachment';

    protected static ?string $group = 'Admin';

    public function defaultPerPage()
    {
        return 25;
    }

    public function defaultTableView()
    {
        return 'grid';
    }

    public function filePath($size = null)
    {
        // Base storage directory
        $basePath = storage_path('app/public');

        if ($size) {
            $relativePath = Str::after($this->url, 'media/');

            return $basePath.'/'.$size.'/'.$relativePath;
        }

        return $basePath.'/'.$this->url;
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Attachment',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'slug' => 'panel1',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Preview',
                'type' => 'Eminiarts\\Aura\\Fields\\Embed',
                'validation' => '',
                'on_index' => false,
                'slug' => 'embed',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Details',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'slug' => 'panel2',
                'style' => [
                    'width' => '50',
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
                'name' => 'Url',
                'type' => 'Eminiarts\\Aura\\Fields\\ViewValue',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'url',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Thumbnail',
                'type' => 'Eminiarts\\Aura\\Fields\\ViewValue',
                'validation' => '',
                'on_index' => false,
                'slug' => 'thumbnail_url',
                'style' => [
                    'width' => '100',
                ],
            ],

            [
                'name' => 'Mime Type',
                'type' => 'Eminiarts\\Aura\\Fields\\ViewValue',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'mime_type',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Size',
                'type' => 'Eminiarts\\Aura\\Fields\\ViewValue',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'size',
                'style' => [
                    'width' => '33',
                ],
            ],
            // [
            //     'name' => 'Created at',
            //     'slug' => 'created_at',
            //     'type' => 'Eminiarts\\Aura\\Fields\\Date',
            //     'validation' => '',
            //     'enable_time' => true,
            //     'conditional_logic' => [],
            //     'wrapper' => '',
            //     'on_index' => true,
            //     'on_forms' => true,
            //     'on_view' => true,
            //     'style' => [
            //         'width' => '50',
            //     ],
            // ],
            // [
            //     'name' => 'Updated at',
            //     'slug' => 'updated_at',
            //     'type' => 'Eminiarts\\Aura\\Fields\\Date',
            //     'validation' => '',
            //     'conditional_logic' => [],
            //     'wrapper' => '',
            //     'enable_time' => true,
            //     'on_index' => true,
            //     'on_forms' => true,
            //     'on_view' => true,
            //     'style' => [
            //         'width' => '50',
            //     ],
            // ],
        ];
    }

    public function getIcon()
    {
        return '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>';
    }

    public function getReadableFilesizeAttribute()
    {
        $bytes = $this->size;

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getReadableMimeTypeAttribute()
    {
        $mimeTypeToReadable = [
            'image/jpeg' => 'JPEG',
            'image/png' => 'PNG',
            'application/pdf' => 'PDF',
            'application/docx' => 'DOCX',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PPTX',
            'application/vnd.ms-excel' => 'XLS',
            'application/vnd.ms-powerpoint' => 'PPT',
            'application/vnd.ms-word' => 'DOC',
            'video/quicktime' => 'MOV',
            'video/mp4' => 'MP4',
            'video/x-msvideo' => 'AVI',
            'video/x-ms-wmv' => 'WMV',
            'audio/mpeg' => 'MP3',
            'audio/mp3' => 'MP3',
            'audio/x-mpeg' => 'MP3',
            'audio/x-mp3' => 'MP3',
            'audio/mpeg3' => 'MP3',
            'audio/x-mpeg3' => 'MP3',
            'audio/mpg' => 'MP3',
            'audio/x-mpg' => 'MP3',
            'audio/x-mpegaudio' => 'MP3',
        ];

        return $mimeTypeToReadable[$this->mime_type] ?? $this->mime_type;
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public static function import($url, $folder = 'attachments')
    {
        // Download the image
        $imageContent = file_get_contents($url);

        // Generate a unique file name
        $fileName = uniqid().'.jpg';

        // Save the image to the desired storage
        $storagePath = "{$folder}/{$fileName}";
        Storage::disk('public')->put($storagePath, $imageContent);

        // Get the image size and mime type
        $imageSize = Storage::disk('public')->size($storagePath);
        $imageMimeType = Storage::disk('public')->mimeType($storagePath);

        // Create a new Attachment instance
        $attachment = self::create([
            'url' => $storagePath,
            'name' => $fileName,
            'title' => $fileName,
            'size' => $imageSize,
            'mime_type' => $imageMimeType,
        ]);

        return $attachment;
    }

    public function isImage()
    {
        return Str::startsWith($this->mime_type, 'image/');
    }

    public function path($size = null)
    {
        if ($size) {
            $url = Str::after($this->url, 'media/');

            $assetPath = 'storage/'.$size.'/'.$url;

            if (file_exists(public_path($assetPath))) {
                return asset($assetPath);
            }
        }

        return asset('storage/'.$this->url);
    }

    public function tableGridView()
    {
        return 'aura::attachment.grid';
    }

    public function tableRowView()
    {
        return 'aura::attachment.row';
    }

    public function thumbnail()
    {
        $mimeTypeToThumbnail = [
            'image/jpeg' => $this->url,
            'image/png' => $this->url,
            'application/pdf' => 'pdf.jpg',
            'application/docx' => 'docx.jpg',
        ];

        return $mimeTypeToThumbnail[$this->mime_type] ?? 'default-thumbnail.jpg';
    }

    public function thumbnail_path()
    {
        return asset('storage/'.$this->thumbnail_url);
    }

    protected static function booted()
    {
        parent::booted();

        static::saved(function (Attachment $attachment) {
            // Check if the attachment has a file

            // Dispatch the GenerateThumbnail job
            if ($attachment->isImage()) {
                GenerateImageThumbnail::dispatch($attachment);
            }
        });
    }
}

```


I will now also give you the code of the Resource, so you can get a better understanding of the CMS:

```php
<?php

namespace Eminiarts\Aura;

use Aura\Flows\Jobs\TriggerFlowOnCreatePostEvent;
use Aura\Flows\Jobs\TriggerFlowOnDeletedPostEvent;
use Aura\Flows\Jobs\TriggerFlowOnUpdatePostEvent;
use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Models\Scopes\TeamScope;
use Eminiarts\Aura\Models\Scopes\TypeScope;
use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Traits\AuraModelConfig;
use Eminiarts\Aura\Traits\AuraTaxonomies;
use Eminiarts\Aura\Traits\InitialPostFields;
use Eminiarts\Aura\Traits\InputFields;
use Eminiarts\Aura\Traits\InteractsWithTable;
use Eminiarts\Aura\Traits\SaveFieldAttributes;
use Eminiarts\Aura\Traits\SaveMetaFields;
use Eminiarts\Aura\Traits\SaveTerms;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Resource extends Model
{
    use AuraModelConfig;
    use AuraTaxonomies;
    use HasFactory;
    use HasTimestamps;

    // Aura
    use InitialPostFields;
    use InputFields;
    use InteractsWithTable;
    use SaveFieldAttributes;
    use SaveMetaFields;
    use SaveTerms;

    public $fieldsAttributeCache;

    protected $appends = ['fields'];

    protected $fillable = ['title', 'content', 'type', 'status', 'fields', 'slug', 'user_id', 'parent_id', 'order', 'taxonomies', 'terms', 'team_id', 'first_taxonomy', 'created_at', 'updated_at', 'deleted_at'];

    protected $hidden = ['meta'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'posts';

    protected $with = ['meta'];

    /**
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        $value = parent::__get($key);

        if ($value) {
            return $value;
        }

        // If the key is in the fields array, then we want to return that
        if (is_null($value) && isset($this->fields[$key])) {
            return $this->fields[$key];
        }

        return $value;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function attachment()
    {
        return $this->hasMany(self::class, 'post_parent')
            ->where('post_type', 'attachment');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany(get_class($this), 'parent_id');
    }

    public function getBulkActions()
    {
        // get all flows with type "manual"

        // $flows = Flow::where('trigger', 'manual')
        //     ->where('options->resource', $this->type)
        //     ->get();

        // foreach ($flows as $flow) {
        //     $this->bulkActions['callManualFlow'] = $flow->name;
        // }

        // dd($this->bulkActions);
        return $this->bulkActions;
    }

    /**
     * @return string
     */
    public function getExcerptAttribute()
    {
        return $this->stripShortcodes($this->post_excerpt);
    }

    public function clearFieldsAttributeCache()
    {
        $this->fieldsAttributeCache = null;
    }

    public function getFieldsAttribute()
    {
        if (! isset($this->fieldsAttributeCache)) {
            $meta = $this->getMeta();

            $defaultValues = collect($this->inputFieldsSlugs())
                ->mapWithKeys(fn ($value, $key) => [$value => null])
                ->map(fn ($value, $key) => $meta[$key] ?? $value)
                //  ->dd()
                ->map(function ($value, $key) {
                    // if the value is in $this->hidden, set it to null
                    if (in_array($key, $this->hidden)) {
                        // return null;
                    }

                    $class = $this->fieldClassBySlug($key);

                    if ($class && isset($this->{$key}) && method_exists($class, 'get')) {
                        return $class->get($class, $this->{$key});
                    }

                    // if $this->{$key} is set, then we want to use that
                    if (isset($this->{$key})) {
                        return $this->{$key};
                    }

                    if ($class && isset($this->attributes[$key]) && method_exists($class, 'get')) {
                        return $class->get($class, $this->attributes[$key]);
                    }

                    // if $this->attributes[$key] is set, then we want to use that
                    if (isset($this->attributes[$key])) {
                        return $this->attributes[$key];
                    }

                    // if there is a function get{Slug}Field on the model, use it
                    $method = 'get'.Str::studly($key).'Field';

                    if (method_exists($this, $method)) {
                        return $this->{$method}();
                    }

                    $class = $this->fieldClassBySlug($key);



                    if ($class && isset(optional($this)->{$key}) && method_exists($class, 'get')) {
                        dd('get here');
                        return $class->get($class, $this->{$key} ?? null);
                    }

                    return $value;
                });

            $this->fieldsAttributeCache = $defaultValues->filter(function ($value, $key) {
                return $this->shouldDisplayField($this->fieldBySlug($key));
            });
        }

        return $this->fieldsAttributeCache;
    }

    // /**
    //  * Gets the featured image if any
    //  * Looks in meta the _thumbnail_id field.
    //  *
    //  * @return string
    //  */
    // public function getImageAttribute()
    // {
    //     if ($this->thumbnail and $this->thumbnail->attachment) {
    //         return $this->thumbnail->attachment->guid;
    //     }
    // }

    public function getMeta($key = null)
    {
        if ($this->usesMeta() && optional($this)->meta && ! is_string($this->meta)) {
            $meta = $this->meta->pluck('value', 'key');

            // Cast Attributes
            $meta = $meta->map(function ($meta, $key) {
                $class = $this->fieldClassBySlug($key);

                if ($class && method_exists($class, 'get')) {
                    return $class->get($class, $meta);
                }

                return $meta;
            });

            if ($key) {
                return $meta[$key] ?? null;
            }

            return $meta;
        }

        return collect();
    }

    public function getSearchableFields()
    {
        // get input fields and remove the ones that are not searchable
        $fields = $this->inputFields()->filter(function ($field) {
            // if $field is array or undefined, then we don't want to use it
            if (! is_array($field) || ! isset($field['searchable'])) {
                return false;
            }

            return $field['searchable'];
        });

        return $fields;
    }

    // Override isRelation
    public function isRelation($key)
    {
        $modelMethods = get_class_methods($this);

        $possibleRelationMethods = [$key, Str::camel($key)];

        foreach ($possibleRelationMethods as $method) {
            if ($method == 'taxonomy') {
                continue;
            }

            // ray(in_array($method, $modelMethods) && ($this->{$method}() instanceof \Illuminate\Database\Eloquent\Relations\Relation), $method);

            if (in_array($method, $modelMethods) && ($this->{$method}() instanceof \Illuminate\Database\Eloquent\Relations\Relation)) {
                // ray($key);
                return true;
            }
        }

        return false;
    }

    /**
     * Get the jobs for the post.
     */
    public function jobs()
    {
        return $this->hasMany(PostJob::class, 'post_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(get_class($this), 'parent_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function revision()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('post_type', 'revision');
    }

    /**
     * Get the User associated with the Content
     *
     * @return mixed
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function widgets()
    {
        if (! $this->getWidgets()) {
            return;
        }

        return collect($this->getWidgets())->map(function ($item) {
            //$item['widget'] = app($item['type'])->widget($item);

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
        if (! static::$customTable) {
            static::addGlobalScope(new TypeScope());
        }

        static::addGlobalScope(new TeamScope());

        static::creating(function ($model) {
            // if (! $model->team_id) {
            //     $model->team_id = 1;
            // }
        });

        static::saved(function ($model) {
            $model->clearFieldsAttributeCache();
        });

        static::created(function ($post) {
            dispatch(new TriggerFlowOnCreatePostEvent($post));
        });

        static::updated(function ($post) {
            dispatch(new TriggerFlowOnUpdatePostEvent($post));
        });

        static::deleted(function ($post) {
            dispatch(new TriggerFlowOnDeletedPostEvent($post));
        });
    }
}

```

Traits: AuraModelConfig.php
```php
<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\ConditionalLogic;
use Eminiarts\Aura\Models\Meta;
use Eminiarts\Aura\Resources\Team;
use Illuminate\Support\Str;

trait AuraModelConfig
{
    public array $actions = [];

    public array $bulkActions = [];

    public static $contextMenu = true;

    public static $customTable = false;

    public static $editEnabled = true;

    public static $globalSearch = true;

    public array $metaFields = [];

    public static $pluralName = null;

    public static $singularName = null;

    public static $taxonomy = false;

    public array $taxonomyFields = [];

    public static bool $usesMeta = true;

    public static $viewEnabled = true;

    public array $widgetSettings = [
        'default' => '30d',
        'options' => [
            '1d' => '1 Day',
            '7d' => '7 Days',
            '30d' => '30 Days',
            '60d' => '60 Days',
            '90d' => '90 Days',
            '180d' => '180 Days',
            '365d' => '365 Days',
            'all' => 'All',
            'ytd' => 'Year to Date',
            'qtd' => 'Quarter to Date',
            'mtd' => 'Month to Date',
            'wtd' => 'Week to Date',
            'last-year' => 'Last Year',
            'last-month' => 'Last Month',
            'last-week' => 'Last Week',
            'custom' => 'Custom',
        ],
    ];

    protected $baseFillable = [];

    protected static $dropdown = false;

    protected static ?string $group = 'Resources';

    protected static ?string $icon = null;

    protected static ?string $name = null;

    protected static array $searchable = [];

    protected static bool $showInNavigation = true;

    protected static ?string $slug = null;

    protected static ?int $sort = 100;

    protected static bool $title = false;

    protected static string $type = 'Resource';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->baseFillable = $this->getFillable();

        // Merge fillable fields from fields
        // ray()->count();
        // dd($this->inputFieldsSlugs());
        $this->mergeFillable($this->inputFieldsSlugs());
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    // public function __get($key)
    // {
    //     // // Title is a special case, for now
    //     if ($key == 'title') {
    //         return $this->getAttributeValue($key);
    //     }

    //     // Does not work atm
    //     // if ($key == 'roles') {
    //     //     return;
    //     //     return $this->getRolesField();
    //     // }

    //     $value = parent::__get($key);

    //     if ($value) {
    //         return $value;
    //     }

    //     // ray()->count();
    //     return $this->displayFieldValue($key, $value);
    // }

    public function createUrl()
    {
        return route('aura.post.create', [$this->getType()]);
    }

    public function createView()
    {
        return 'aura::livewire.post.create';
    }

    public function display($key)
    {
        if (array_key_exists($key, $this->fields->toArray())) {
            $value = $this->displayFieldValue($key, $this->fields[$key]);

            // if $value is an array, implode it
            if (is_array($value)) {
                return implode(', ', $value);
            }

            return $value;
        }

        if (isset($this->{$key})) {
            $value = $this->{$key};

            // if $value is an array, implode it
            if (is_array($value)) {
                return implode(', ', $value);
            }

            return $value;
        }
    }

    public function editHeaderView()
    {
        return 'aura::livewire.post.edit-header';
    }

    public function editUrl()
    {
        if ($this->getType() && $this->id) {
            return route('aura.post.edit', ['slug' => $this->getType(), 'id' => $this->id]);
        }
    }

    public function editView()
    {
        return 'aura::livewire.post.edit';
    }

    public function viewView()
    {
        return 'aura::livewire.post.view';
    }

    public function getActions()
    {
        if (method_exists($this, 'actions')) {
            return $this->actions();
        }

        if (property_exists($this, 'actions')) {
            return $this->actions;
        }
    }

    public function getBulkActions()
    {
        if (method_exists($this, 'bulkActions')) {
            return $this->bulkActions();
        }

        if (property_exists($this, 'bulkActions')) {
            return $this->bulkActions;
        }
    }

    public function getBaseFillable()
    {
        return $this->baseFillable;
    }

    public static function getContextMenu()
    {
        return static::$contextMenu;
    }

    public static function getDropdown()
    {
        return static::$dropdown;
    }

    public static function getFields()
    {
        return [];
    }

    public static function getGlobalSearch()
    {
        return static::$globalSearch;
    }

    public static function getGroup(): ?string
    {
        return static::$group;
    }

    public function getHeaders()
    {
        $fields = $this->indexFields();

        // Filter $fields based on Conditional Logic for roles
        $fields = $fields->filter(function ($field) {
            return ConditionalLogic::fieldIsVisibleTo($field, auth()->user());
        });

        $fields = $fields->pluck('name', 'slug')
            ->when($this->usesTitle(), function ($collection, $value) {
                return $collection->prepend('title', 'title');
            })
            ->prepend('ID', 'id');

        return $fields;
    }

    public function getIcon()
    {
        return '<svg class="w-5 h-5" viewBox="0 0 18 18" fill="none" stroke="currentColor" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.75 9a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public function getIndexRoute()
    {
        return route('aura.post.index', $this->getSlug());
    }

    public static function getName(): ?string
    {
        return static::$name;
    }

    public static function getPluralName(): string
    {

        return static::$pluralName ?? str(static::$type)->plural();
    }

    public static function getSearchable(): array
    {
        return static::$searchable;
    }

    public static function getShowInNavigation(): bool
    {
        return static::$showInNavigation;
    }

    public static function getSlug(): string
    {
        return static::$slug;
    }

    public static function getSort(): ?int
    {
        return static::$sort;
    }

    public static function getType(): string
    {
        return static::$type;
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public function icon()
    {
        return $this->getIcon();
    }

    public function indexView()
    {
        return 'aura::livewire.post.index';
    }

    public function isAppResource()
    {
        return Str::startsWith(get_class($this), 'App');
    }

    public function isMetaField($key)
    {
        // If field is a taxonomy, it is not a meta field
        if ($this->isTaxonomyField($key)) {
            return false;
        }

        // If the key is in Base fillable, it is not a meta field
        if (in_array($key, $this->getBaseFillable())) {
            return false;
        }

        // If the key is in the fields, it is a meta field
        if (in_array($key, $this->inputFieldsSlugs())) {
            return true;
        }
    }

    public function isNumberField($key)
    {
        if ($this->fieldBySlug($key)['type'] == 'Eminiarts\\Aura\\Fields\\Number') {
            return true;
        }

        return false;
    }

    // Is the Field in the table?
    public function isTableField($key)
    {
        if (in_array($key, $this->getBaseFillable())) {
            return true;
        }

        return false;
    }

    public function isTaxonomy()
    {
        return static::$taxonomy;
    }

    public function isTaxonomyField($key)
    {
        // Check if the Field is a taxonomy 'type' => 'Eminiarts\\Aura\\Fields\\Tags',
        if (in_array($key, $this->inputFieldsSlugs())) {
            $field = $this->fieldBySlug($key);

            // Atm only tags, refactor later
            if (isset($field['type']) && $field['type'] == 'Eminiarts\\Aura\\Fields\\Tags') {
                return true;
            }
        }

        return false;
    }

    public function isVendorResource()
    {
        return ! $this->isAppResource();
    }

    /**
     * Get the Meta Relation
     *
     * @return mixed
     */
    public function meta()
    {
        if(!$this->usesMeta()) {
            return;
        }

        return $this->hasMany(Meta::class, 'post_id');
        //->whereIn('key', $this->inputFieldsSlugs())
    }

    public function navigation()
    {
        return [
            'icon' => $this->icon(),
            'resource' => get_class($this),
            'type' => $this->getType(),
            'name' => $this->pluralName(),
            'slug' => $this->getSlug(),
            'sort' => $this->getSort(),
            'group' => $this->getGroup(),
            'route' => $this->getIndexRoute(),
            'dropdown' => $this->getDropdown(),
            'showInNavigation' => $this->getShowInNavigation(),
        ];
    }

    public function pluralName()
    {
        return Str::plural($this->singularName());

        return __(static::$pluralName ?? Str::plural($this->singularName()));
    }

    public function rowView()
    {
        return 'aura::components.table.row';
    }

    public function saveMetaField(array $metaFields): void
    {
        $this->saveMetaFields($metaFields);
    }

    public function saveMetaFields(array $metaFields): void
    {
        $this->metaFields = array_merge($this->metaFields, $metaFields);
    }

    public function saveTaxonomyFields(array $taxonomyFields): void
    {
        $this->taxonomyFields = array_merge($this->taxonomyFields, $taxonomyFields);
    }

    public function scopeWhereMeta($query, ...$args)
    {
        if (count($args) === 2) {
            $key = $args[0];
            $value = $args[1];

            return $query->whereHas('meta', function ($query) use ($key, $value) {
                $query->where('key', $key)->where('value', $value);
            });
        } elseif (count($args) === 1 && is_array($args[0])) {
            $metaPairs = $args[0];

            return $query->where(function ($query) use ($metaPairs) {
                foreach ($metaPairs as $key => $value) {
                    $query->whereHas('meta', function ($query) use ($key, $value) {
                        $query->where('key', $key)->where('value', $value);
                    });
                }
            });
        }

        return $query;
    }

    public function singularName()
    {
        return static::$singularName ?? Str::title(static::$slug);
    }



    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function title()
    {
        if (optional($this)->id) {
            return $this->getType()." (#{$this->id})";
        }
    }

    public static function usesCustomTable(): bool
    {
        return static::$customTable;
    }

    public static function usesMeta(): string
    {
        return static::$usesMeta;
    }

    public static function usesTitle(): bool
    {
        return static::$title;
    }

    public function viewHeaderView()
    {
        return 'aura::livewire.post.view-header';
    }

    public function viewUrl()
    {
        if ($this->getType() && $this->id) {
            return route('aura.post.view', ['slug' => $this->getType(), 'id' => $this->id]);
        }
    }
}

```

Trait: InputFields.php
```php
<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\ConditionalLogic;
use Eminiarts\Aura\Pipeline\AddIdsToFields;
use Eminiarts\Aura\Pipeline\ApplyParentConditionalLogic;
use Eminiarts\Aura\Pipeline\ApplyParentDisplayAttributes;
use Eminiarts\Aura\Pipeline\ApplyTabs;
use Eminiarts\Aura\Pipeline\BuildTreeFromFields;
use Eminiarts\Aura\Pipeline\DoNotDeferConditionalLogic;
use Eminiarts\Aura\Pipeline\FilterCreateFields;
use Eminiarts\Aura\Pipeline\FilterEditFields;
use Eminiarts\Aura\Pipeline\FilterViewFields;
use Eminiarts\Aura\Pipeline\MapFields;
use Eminiarts\Aura\Pipeline\RemoveClosureAttributes;
use Eminiarts\Aura\Pipeline\RemoveValidationAttribute;
use Eminiarts\Aura\Pipeline\TransformSlugs;

trait InputFields
{
    use InputFieldsHelpers;
    use InputFieldsTable;
    use InputFieldsValidation;

    private $accessibleFieldKeysCache = null;

    public function createFields()
    {
        // Apply Conditional Logic of Parent Fields
        return $this->sendThroughPipeline($this->fieldsCollection(), [
            ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
            DoNotDeferConditionalLogic::class,
            ApplyParentDisplayAttributes::class,
            FilterCreateFields::class,
            BuildTreeFromFields::class,
        ]);
    }

    public function displayFieldValue($key, $value = null)
    {
        // return $value;

        // Check Conditional Logic if the field should be displayed
        if (! $this->shouldDisplayField($this->fieldBySlug($key))) {
            return;
        }

        $studlyKey = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key)));

        // If there is a get{key}Field() method, use that
        if ($value && method_exists($this, 'get'.ucfirst($studlyKey).'Field')) {
            return $this->{'get'.ucfirst($key).'Field'}($value);
        }

        // Maybe delete this one?
        if (optional($this->fieldBySlug($key))['display'] && $value) {
            return $this->fieldBySlug($key)['display']($value, $this);
        }

        if ($value === null && optional(optional($this)->meta)->$key) {
            return optional($this->fieldClassBySlug($key))->display($this->fieldBySlug($key), optional($this->meta)->$key, $this);
        }

        if ($this->fieldClassBySlug($key)) {
            return optional($this->fieldClassBySlug($key))->display($this->fieldBySlug($key), $value, $this);
        }

        return $value;
    }

    public function editFields()
    {
        // Apply Conditional Logic of Parent Fields
        return $this->sendThroughPipeline($this->fieldsCollection(), [
            ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
            DoNotDeferConditionalLogic::class,
            ApplyParentDisplayAttributes::class,
            FilterEditFields::class,
            RemoveClosureAttributes::class,
            BuildTreeFromFields::class,
        ]);
    }

    public function fieldBySlugWithDefaultValues($slug)
    {
        $field = $this->fieldBySlug($slug);

        if (! isset($field)) {
            return;
        }

        $fieldFields = optional($this->mappedFieldBySlug($slug))['field']->getGroupedFields();

        foreach ($fieldFields as $key => $f) {
            // if no key value pair is set, get the default value from the field
            if (! isset($field[$f['slug']]) && isset($f['default'])) {
                $field[$f['slug']] = $f['default'];
            }
        }

        return $field;
    }

    // public function getAccessibleFieldKeys()
    // {
    //     if ($this->accessibleFieldKeysCache === null) {
    //         // Apply Conditional Logic of Parent Fields
    //         $fields = $this->sendThroughPipeline($this->fieldsCollection(), [
    //             ApplyTabs::class,
    //             MapFields::class,
    //             AddIdsToFields::class,
    //             ApplyParentConditionalLogic::class,
    //             DoNotDeferConditionalLogic::class,
    //         ]);

    //         // Get all input fields
    //         $this->accessibleFieldKeysCache = $fields
    //             ->filter(function ($field) {
    //                 return $field['field']->isInputField();
    //             })
    //             ->pluck('slug')
    //             ->filter(function ($field) {
    //                 // return true;
    //                 return $this->shouldDisplayField($this->fieldBySlug($field));
    //             })
    //             ->toArray();
    //     }

    //     return $this->accessibleFieldKeysCache;
    // }

    public function fieldsForView($fields = null, $pipes = null)
    {
        if (! $fields) {
            $fields = $this->mappedFields();
        }

        if (! $pipes) {
            $pipes = [
                ApplyTabs::class,
                MapFields::class,
                AddIdsToFields::class,
                ApplyParentConditionalLogic::class,
                DoNotDeferConditionalLogic::class,
                ApplyParentDisplayAttributes::class,
                FilterViewFields::class,
                RemoveValidationAttribute::class,
                BuildTreeFromFields::class,
            ];
        }

        return $this->sendThroughPipeline($fields, $pipes);
    }

    public function fieldsHaveClosures($fields)
    {
        foreach ($fields as $field) {
            foreach ($field as $value) {
                if (is_array($value)) {
                    if ($this->fieldsHaveClosures([$value])) {
                        return true;
                    }
                } elseif ($value instanceof \Closure) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getFieldsBeforeTree($fields = null)
    {
        $cacheKey = get_class($this).'-getFieldsBeforeTree';

        if (! app()->bound($cacheKey)) {
            // ray()->count();
            // ray($cacheKey);
            // If fields is set and is an array, create a collection
            if ($fields && is_array($fields)) {
                $fields = collect($fields);
            }

            if (! $fields) {
                $fields = $this->fieldsCollection();
            }

            $fieldsBeforeTree = $this->sendThroughPipeline($fields, [
                MapFields::class,
                AddIdsToFields::class,
                TransformSlugs::class,
                ApplyParentConditionalLogic::class,
                DoNotDeferConditionalLogic::class,
            ]);

            app()->singleton($cacheKey, function () use ($fieldsBeforeTree) {
                return $fieldsBeforeTree;
            });

        }

        return app($cacheKey);

    }

    // Used in Posttype
    public function getFieldsForEdit($fields = null)
    {
        if (! $fields) {
            $fields = $this->mappedFields();
        }

        $pipes = [
            // ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            BuildTreeFromFields::class,
        ];

        return $this->sendThroughPipeline($fields, $pipes);
    }

    /**
     * This code is used to render the form fields in the correct order.
     * It applies tabs to the fields, maps the fields, adds ids to the fields,
     * applies the parent conditional logic to the fields, and builds a tree from the fields.
     */
    public function getGroupedFields($fields = null, $pipes = null): array
    {
        // ray()->count();

        // If fields is set and is an array, create a collection
        if ($fields && is_array($fields)) {
            $fields = collect($fields);
        }

        if (! $fields) {
            $fields = $this->fieldsCollection();
        }

        if (! $pipes) {
            $pipes = [
                ApplyTabs::class,
                MapFields::class,
                AddIdsToFields::class,
                ApplyParentConditionalLogic::class,
                DoNotDeferConditionalLogic::class,
                ApplyParentDisplayAttributes::class,
                FilterViewFields::class,
                BuildTreeFromFields::class,
            ];
        }

        return $this->sendThroughPipeline($fields, $pipes);
    }

    public function indexFields()
    {
        return $this->inputFields()->filter(function ($field) {
            if (optional($field)['on_index'] === false) {
                return false;
            }

            return true;
        });
    }

    /**
     * Map to Grouped Fields for the Posttype Builder / Edit Posttype.
     *
     * @param  array  $fields
     * @return array
     */
    public function mapToGroupedFields($fields)
    {
        // ray()->count();

        $fields = collect($fields)->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        });

        return $this->sendThroughPipeline($fields, [
            AddIdsToFields::class,
            BuildTreeFromFields::class,
        ]);
    }

    public function shouldDisplayField($field)
    {
        return ConditionalLogic::shouldDisplayField($this, $field);
    }

    public function taxonomyFields()
    {
        return $this->mappedFields()->filter(function ($field) {
            if (optional(optional($field)['field'])->isTaxonomyField()) {
                return true;
            }

            return false;
        });
    }

    public function viewFields()
    {
        return $this->sendThroughPipeline($this->mappedFields(), [
            ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
            DoNotDeferConditionalLogic::class,
            ApplyParentDisplayAttributes::class,
            FilterViewFields::class,
            BuildTreeFromFields::class,
        ]);
    }
}

```

InputfieldsHelpers.php
```php
<?php

namespace Eminiarts\Aura\Traits;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Facades\Cache;

trait InputFieldsHelpers
{
    protected static $fieldsBySlug = [];

    protected static $fieldClassesBySlug = [];

    protected static $fieldsCollectionCache = [];

    protected static $inputFieldSlugs = [];

    protected static $mappedFields = [];

    public function fieldBySlug($slug)
    {

        // Construct a unique key using the class name and the slug
        $key = get_class($this) . '-' . $slug;

        // If this key exists in the static array, return the cached result
        if (isset(self::$fieldsBySlug[$key])) {
            return self::$fieldsBySlug[$key];
        }

        $result = $this->fieldsCollection()->firstWhere('slug', $slug);

        self::$fieldsBySlug[$key] = $result;

        return $result;
    }

    public function fieldClassBySlug($slug)
    {
        // Construct a unique key using the class name and the slug
        $key = get_class($this) . '-' . $slug;

        // If this key exists in the static array, return the cached result
        if (isset(self::$fieldClassesBySlug[$key])) {
            return self::$fieldClassesBySlug[$key];
        }

        // Otherwise, perform the original operation
        $field = $this->fieldBySlug($slug);
        $result = false;

        if (optional($field)['type']) {
            $result = app($field['type']);
        }

        // Store the result in the static array
        self::$fieldClassesBySlug[$key] = $result;

        // Return the result
        return $result;
    }

    public function fieldsCollection()
    {
        // return collect($this->getFields());
        $class = get_class($this);

        if (isset(self::$fieldsCollectionCache[$class])) {
            return self::$fieldsCollectionCache[$class];
        }

        self::$fieldsCollectionCache[$class] = collect($this->getFields());

        return self::$fieldsCollectionCache[$class];
    }

    public function findBySlug($array, $slug)
    {
        foreach ($array as $item) {
            if ($item['slug'] === $slug) {
                return $item;
            }
            if (isset($item['fields'])) {
                $result = $this->findBySlug($item['fields'], $slug);
                if ($result) {
                    return $result;
                }
            }
        }
    }

    public function getFieldSlugs()
    {
        return $this->fieldsCollection()->pluck('slug');
    }

    public function getFieldValue($key)
    {
        return $this->fieldClassBySlug($key)->get($this->fieldBySlug($key), $this->meta->$key);
    }

    public function groupedFieldBySlug($slug)
    {
        $fields = $this->getGroupedFields();

        return $this->findBySlug($fields, $slug);
    }

    public function inputFields()
    {
        return $this->getFieldsBeforeTree()->filter(fn ($item) => in_array($item['field_type'], ['input', 'repeater', 'group']));
    }

    public function inputFieldsSlugs()
    {
        $class = get_class($this);

        if (isset(self::$inputFieldSlugs[$class])) {
            return self::$inputFieldSlugs[$class];
        }

        self::$inputFieldSlugs[$class] = $this->inputFields()->pluck('slug')->toArray();

        return self::$inputFieldSlugs[$class];
    }

    public function mappedFieldBySlug($slug)
    {
        // dd($this->mappedFields(), $this->newFields);
        return $this->mappedFields()->firstWhere('slug', $slug);
    }

    public function mappedFields()
    {
        // mappedFields
        $class = get_class($this);

        if (isset(self::$mappedFields[$class])) {
            return self::$mappedFields[$class];
        }

        self::$mappedFields[$class] =  $this->fieldsCollection()->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        });

        return self::$mappedFields[$class];
    }

    public function sendThroughPipeline($fields, $pipes)
    {
        // dump('sendThroughPipeline');
        return app(Pipeline::class)
            ->send(clone $fields)
            ->through($pipes)
            ->thenReturn();
    }
}


```


InteractsWithTable.php
```php
<?php

namespace Eminiarts\Aura\Traits;

trait InteractsWithTable
{
    public function defaultPerPage()
    {
        return 10;
    }

    public function defaultTableView()
    {
        return 'list';
    }

    public function showTableSettings()
    {
        return true;
    }

    public function tableGridView()
    {
        return false;
    }

    public function tableRowView()
    {
        return 'attachment.row';
    }

    public function tableView()
    {
        return 'aura::components.table.table';
    }
}

```

SaveFieldAttributes.php
```php
<?php

namespace Eminiarts\Aura\Traits;

trait SaveFieldAttributes
{
    /**
     * Set Fields Attributes
     *
     * Take Fields Attributes and Put all fields from getFieldSlugs() in the Fields Column
     *
     * @param    $post
     * @return void
     */
    protected static function bootSaveFieldAttributes()
    {
        static::saving(function ($post) {
            if (! optional($post->attributes)['fields']) {
                $post->attributes['fields'] = [];
            }


            collect($post->inputFieldsSlugs())->each(function ($slug) use ($post) {
                if (optional($post->attributes)[$slug]) {
                    $class = $post->fieldClassBySlug($slug);

                    // Do not continue if the Field is not found
                    if (! $class) {
                        return;
                    }

                    // Do not set password fields manually, since they would overwrite the hashed password
                    if ($class instanceof \Eminiarts\Aura\Fields\Password) {
                        return;
                    }

                    if (! array_key_exists($slug, $post->attributes['fields'])) {
                        $post->attributes['fields'][$slug] = $post->attributes[$slug];
                    }
                }

                if ($slug == 'title') {
                    return;
                }

                // Dont Unset Field if it is in baseFillable
                if (in_array($slug, $post->baseFillable)) {
                    return;
                }

                // Unset fields from the attributes
                unset($post->attributes[$slug]);
            });
        });
    }
}

```

SaveMetaFields.php
```php
<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\Models\Meta;
use Illuminate\Support\Str;

trait SaveMetaFields
{
    protected static function bootSaveMetaFields()
    {
        static::saving(function ($post) {
            if (isset($post->attributes['fields'])) {
                // dump('saving', $post->attributes['fields']);
                //ray('fields in savemetafields', $post->attributes['fields']);

                foreach ($post->attributes['fields'] as $key => $value) {
                    $class = $post->fieldClassBySlug($key);

                    // Do not continue if the Field is not found
                    if (! $class) {
                        continue;
                    }

                    // if there is a function set{Slug}Field on the model, use it
                    $method = 'set'.Str::studly($key).'Field';

                    if (method_exists($post, $method)) {
                        $post->saveMetaField([$key => $value]);

                        //$post = $post->{$method}($value);

                        continue;
                    }

                    // If the $class is a Password Field and the value is null, continue
                    if ($class instanceof \Eminiarts\Aura\Fields\Password && is_null($value)) {
                        // If the password is available in the $post->attributes, unset it
                        if (isset($post->attributes[$key])) {
                            unset($post->attributes[$key]);
                        }

                        continue;
                    }

                    if (method_exists($class, 'set')) {
                        $value = $class->set($value);
                    }

                    // If the field exists in the $post->getBaseFillable(), it should be safed in the table instead of the meta table
                    if (in_array($key, $post->getBaseFillable())) {
                        $post->attributes[$key] = $value;

                        continue;
                    }

                    // Save the meta field to the model, so it can be saved in the Meta table
                    $post->saveMetaField([$key => $value]);
                }

                unset($post->attributes['fields']);
            }
        });

        static::saved(function ($post) {
            if (isset($post->metaFields)) {
                foreach ($post->metaFields as $key => $value) {
                    // if there is a function set{Slug}Field on the model, use it
                    $method = 'set'.Str::studly($key).'Field';

                    if (method_exists($post, $method)) {
                        $post = $post->{$method}($value);

                        continue;
                    }

                    if($post->usesMeta()) {
                        $post->meta()->updateOrCreate(['key' => $key], ['value' => $value]);
                    }

                }

                // Reload relation
                // $post->load('meta');
            }
        });
    }
}

```

More Context about Aura:

Aura.php
```php
<?php

namespace Eminiarts\Aura;

use Closure;
use Eminiarts\Aura\Models\Scopes\TeamScope;
use Eminiarts\Aura\Resources\Attachment;
use Eminiarts\Aura\Resources\Option;
use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Traits\DefaultFields;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Finder\SplFileInfo;

class Aura
{
    use DefaultFields;

    // public function __construct()
    // {
    //     ray('construct');
    // }

    /**
     * The user model that should be used by Jetstream.
     *
     * @var string
     */
    public static $userModel = User::class;

    protected array $config = [];

    protected array $fields = [];

    protected array $injectViews = [];

    protected array $resources = [];

    protected array $taxonomies = [];

    protected array $widgets = [];

    /**
     * Determine if Aura's published assets are up-to-date.
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public static function assetsAreCurrent()
    {
        if (app()->environment('testing')) {
            return true;
        }

        $publishedPath = public_path('vendor/aura/manifest.json');

        if (! File::exists($publishedPath)) {
            throw new RuntimeException('Aura CMS assets are not published. Please run: php artisan aura:publish');
        }

        return File::get($publishedPath) === File::get(__DIR__.'/../resources/dist/manifest.json');
    }

    public static function checkCondition($model, $field, $post = null)
    {
        return ConditionalLogic::shouldDisplayField($model, $field, $post);
    }

    public function clearConditionsCache()
    {
        return ConditionalLogic::clearConditionsCache();
    }

    public function findResourceBySlug($slug)
    {
        if (in_array($slug, $this->getResources())) {
            return app($slug);
        }

        $resources = collect($this->getResources())->map(function ($resource) {
            return Str::afterLast($resource, '\\');
        });

        $index = $resources->search(function ($item) use ($slug) {
            return Str::slug($item) == Str::slug($slug);
        });

        if ($index !== false) {
            return app($this->getResources()[$index]);
        }
    }

    public function findTaxonomyBySlug($slug)
    {
        $taxonomies = collect($this->getTaxonomies())->map(function ($resource) {
            return Str::afterLast($resource, '\\');
        });

        // ray($taxonomies, $slug);

        $index = $taxonomies->search(function ($item) use ($slug) {
            return Str::slug($item) == Str::slug($slug);
        });

        if ($index !== false) {
            return app($this->getTaxonomies()[$index]);
        }
    }

    public static function findTemplateBySlug($slug)
    {
        return app('Eminiarts\Aura\Templates\\'.str($slug)->title);
    }

    public function getAppFields()
    {
        $path = config('aura.fields.path');

        if (! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, $filter = 'Field', $namespace = config('aura.fields.namespace'));
    }

    public function getAppFiles($path, $filter, $namespace)
    {
        return collect(app(Filesystem::class)->allFiles($path))
            ->map(function (SplFileInfo $file): string {
                return (string) Str::of($file->getRelativePathname())
                    ->replace(['/', '.php'], ['\\', '']);
            })
            ->filter(fn (string $class): bool => $class != $filter)
            ->map(fn ($item) => $namespace.'\\'.$item)
            ->unique()->toArray();
    }

    /**
     * Register the App resources
     *
     * @param  array  $resources
     * @return static
     */
    public function getAppResources()
    {
        $path = config('aura.paths.resources.path');

        if (! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, $filter = 'Resource', $namespace = config('aura.paths.resources.namespace'));
    }

    /**
     * Register the App taxonomies
     *
     * @param  array  $resources
     * @return static
     */
    public function getAppTaxonomies()
    {
        $path = config('aura.taxonomies.path');

        if (! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, $filter = 'Taxonomy', $namespace = config('aura.taxonomies.namespace'));
    }

    public function getAppWidgets()
    {
        $path = config('aura.widgets.path');

        if (! file_exists($path)) {
            return [];
        }

        return $this->getAppFiles($path, $filter = 'Widget', $namespace = config('aura.widgets.namespace'));
    }

    public function getFields(): array
    {
        return array_unique($this->fields);
    }

    public function getGlobalOptions()
    {
        $valueString =
        [
            'app_name' => 'Aura CMS',
            'app_description' => 'Aura CMS',
            'app_url' => 'http://aura.test',
            'app_locale' => 'en',
            'app_timezone' => 'UTC',

            'team_registration' => true,
            'user_invitations' => true,

            'media' => [
                'disk' => 'public',
                'path' => 'media',
                'max_file_size' => 10000,
                'generate_thumbnails' => true,
                'thumbnails' => [
                    [
                        'name' => 'thumbnail',
                        'width' => 600,
                        'height' => 600,
                    ],
                    [
                        'name' => 'medium',
                        'width' => 1200,
                        'height' => 1200,
                    ],
                    [
                        'name' => 'large',
                        'width' => 2000,
                        'height' => 2000,
                    ],
                ],
            ],
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'features' => [
                'teams' => true,
                'users' => true,
                'media' => true,
                'notifications' => true,
                'settings' => true,
                'pages' => true,
                'posts' => true,
                'categories' => true,
                'tags' => true,
                'comments' => true,
                'menus' => true,
                'roles' => true,
                'permissions' => true,
                'activity' => true,
                'backups' => true,
                'updates' => true,
                'support' => true,
                'documentation' => true,
            ],
        ];

        if (config('aura.teams')) {
            return Option::withoutGlobalScopes([TeamScope::class])->firstOrCreate([
                'name' => 'aura-settings',
                'team_id' => 0,
            ], [
                'value' => json_encode($valueString),
                'team_id' => 0,
            ]);
        } else {
            return Option::firstOrCreate([
                'name' => 'aura-settings',
            ], [
                'value' => json_encode($valueString),
            ]);
        }
    }

    public function getOption($name)
    {
        if (config('aura.teams')) {
            return Cache::remember(optional(auth()->user())->current_team_id.'.aura.'.$name, now()->addHour(), function () use ($name) {
                $option = Option::where('name', $name)->first();

                if ($option && is_string($option->value)) {
                    $settings = json_decode($option->value, true);
                } else {
                    $settings = $option->value ?? null;
                }

                return $settings;
            });
        } else {
            return Cache::remember('aura.'.$name, now()->addHour(), function () use ($name) {
                $option = Option::where('name', $name)->first();

                if ($option && is_string($option->value)) {
                    $settings = json_decode($option->value, true);
                } else {
                    $settings = $option->value ?? null;
                }

                return $settings;
            });
        }
    }

    public static function getPath($id)
    {
        return Attachment::find($id)->url;
    }

    public function getResources(): array
    {
        return array_unique($this->resources);
    }

    public function getTaxonomies(): array
    {
        return array_unique($this->taxonomies);
    }

    public function getWidgets(): array
    {
        return array_unique($this->widgets);
    }

    public function injectView(string $name): Htmlable
    {
        $hooks = array_map(
            fn (callable $hook): string => (string) app()->call($hook),
            $this->injectViews[$name] ?? [],
        );

        return new HtmlString(implode('', $hooks));
    }

    public function navigation()
    {
        // Necessary to add TeamIds?

        return Cache::remember('user-'.auth()->id().'-navigation', 3600, function () {

            $resources = collect($this->getResources())->merge($this->getTaxonomies());

            // filter resources by permission and check if user has viewAny permission
            $resources = $resources->filter(function ($resource) {
                $resource = app($resource);

                return auth()->user()->can('viewAny', $resource);
            });

            // If a Resource is overriden, we want to remove the original from the navigation
            $keys = $resources->map(function ($resource) {
                return Str::afterLast($resource, '\\');
            })->reverse()->unique()->reverse()->keys();

            $resources = $resources->filter(function ($value, $key) use ($keys) {
                return $keys->contains($key);
            })
                ->map(fn ($r) => app($r)->navigation())
                ->filter(fn ($r) => $r['showInNavigation'] ?? true)
                ->sortBy('sort');

            $grouped = array_reduce(collect($resources)->toArray(), function ($carry, $item) {
                if ($item['dropdown'] !== false) {
                    if (! isset($carry[$item['dropdown']])) {
                        $carry[$item['dropdown']] = [];
                    }
                    $carry[$item['dropdown']]['group'] = $item['group'];
                    $carry[$item['dropdown']]['dropdown'] = $item['dropdown'];
                    $carry[$item['dropdown']]['items'][] = $item;
                } else {
                    $carry[] = $item;
                }

                return $carry;
            }, []);

            return collect($grouped)->groupBy('group');
        });
    }

    public function option($key)
    {
        return $this->options()[$key] ?? null;
    }

    public function options()
    {
        return Cache::rememberForever('aura-settings', function () {
            $option = Option::withoutGlobalScopes([TeamScope::class])
                ->where('name', 'aura-settings')
                ->when(config('aura.teams'), function ($query, string $role) {
                    $query->where('team_id', 0);
                })
                ->first();

            if ($option && is_string($option->value)) {
                return json_decode($option->value, true);
            } else {
                return [];
            }
        });
    }

    public function registerFields(array $fields): void
    {
        $this->fields = array_merge($this->fields, $fields);
    }

    public function registerInjectView(string $name, Closure $callback): void
    {
        $this->injectViews[$name][] = $callback;
    }

    public function registerResources(array $resources): void
    {
        $this->resources = array_merge($this->resources, $resources);
    }

    public function registerTaxonomies(array $taxonomies): void
    {
        $this->taxonomies = array_merge($this->taxonomies, $taxonomies);
    }

    public function registerWidgets(array $widgets): void
    {
        $this->widgets = array_merge($this->widgets, $widgets);
    }

    public function setOption($key, $value)
    {
        $option = $this->getGlobalOptions();

        if ($option && is_string($option->value)) {
            $settings = json_decode($option->value, true);
        } else {
            $settings = [];
        }

        $settings[$key] = $value;

        $option->value = json_encode($settings);
        $option->save();

        Cache::forget('aura-settings');
    }

    public function taxonomies()
    {
        return $this->getTaxonomies();

        return Cache::remember('aura.taxonomies', now()->addHour(), function () {
            $filesystem = app(Filesystem::class);

            return collect($filesystem->allFiles(app_path('Aura/Taxonomies')))
                ->map(function (SplFileInfo $file): string {
                    return (string) Str::of($file->getRelativePathname())
                        ->replace(['/', '.php'], ['\\', '']);
                })->filter(fn (string $class): bool => $class != 'Taxonomy')->toArray();
        });
    }

    public static function templates()
    {
        return Cache::remember('aura.templates', now()->addHour(), function () {
            $filesystem = app(Filesystem::class);

            $files = collect($filesystem->allFiles(app_path('Aura/Templates')))
                ->map(function (SplFileInfo $file): string {
                    return (string) Str::of($file->getRelativePathname())
                        ->replace(['/', '.php'], ['\\', '']);
                })->filter(fn (string $class): bool => $class != 'Template');

            return $files;
        });
    }

    /**
     * Get the name of the user model used by the application.
     *
     * @return string
     */
    public static function userModel()
    {
        return static::$userModel;
    }

    public static function useUserModel(string $model)
    {
        static::$userModel = $model;

        return new static();
    }

    public function varexport($expression, $return = false)
    {
        if (! is_array($expression)) {
            return var_export($expression, $return);
        }
        $export = var_export($expression, true);
        $export = preg_replace('/^([ ]*)(.*)/m', '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [null, ']$1', ' => ['], $array);
        $array = preg_replace(["/\d+\s=>\s/"], [null], $array);
        $export = implode(PHP_EOL, array_filter(['['] + $array));
        if ((bool) $return) {
            return $export;
        } else {
            echo $export;
        }
    }
}

```

Aura Service Provider

```php
<?php

namespace Eminiarts\Aura;

use Eminiarts\Aura\Commands\AuraCommand;
use Eminiarts\Aura\Commands\CreateAuraPlugin;
use Eminiarts\Aura\Commands\CreateResourceMigration;
use Eminiarts\Aura\Commands\CreateResourcePermissions;
use Eminiarts\Aura\Commands\DatabaseToResources;
use Eminiarts\Aura\Commands\MakeField;
use Eminiarts\Aura\Commands\MakePosttype;
use Eminiarts\Aura\Commands\MakeTaxonomy;
use Eminiarts\Aura\Commands\MakeUser;
use Eminiarts\Aura\Commands\PublishCommand;
use Eminiarts\Aura\Commands\TransformTableToResource;
use Eminiarts\Aura\Facades\Aura;
use Eminiarts\Aura\Http\Livewire\Attachment\Index as AttachmentIndex;
use Eminiarts\Aura\Http\Livewire\Config;
use Eminiarts\Aura\Http\Livewire\BookmarkPage;
use Eminiarts\Aura\Http\Livewire\CreateFlow;
use Eminiarts\Aura\Http\Livewire\CreatePosttype;
use Eminiarts\Aura\Http\Livewire\CreateTaxonomy;
use Eminiarts\Aura\Http\Livewire\EditOperation;
use Eminiarts\Aura\Http\Livewire\EditPosttypeField;
use Eminiarts\Aura\Http\Livewire\GlobalSearch;
use Eminiarts\Aura\Http\Livewire\MediaManager;
use Eminiarts\Aura\Http\Livewire\MediaUploader;
use Eminiarts\Aura\Http\Livewire\Navigation;
use Eminiarts\Aura\Http\Livewire\Notifications;
use Eminiarts\Aura\Http\Livewire\Post\Create;
use Eminiarts\Aura\Http\Livewire\Post\CreateModal;
use Eminiarts\Aura\Http\Livewire\Post\Edit;
use Eminiarts\Aura\Http\Livewire\Post\EditModal;
use Eminiarts\Aura\Http\Livewire\Post\Index;
use Eminiarts\Aura\Http\Livewire\Post\View;
use Eminiarts\Aura\Http\Livewire\Posttype;
use Eminiarts\Aura\Http\Livewire\Table\Table;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Create as TaxonomyCreate;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Edit as TaxonomyEdit;
use Eminiarts\Aura\Http\Livewire\Taxonomy\Index as TaxonomyIndex;
use Eminiarts\Aura\Http\Livewire\Taxonomy\View as TaxonomyView;
use Eminiarts\Aura\Http\Livewire\TeamSettings;
use Eminiarts\Aura\Http\Livewire\User\InviteUser;
use Eminiarts\Aura\Http\Livewire\User\Profile;
use Eminiarts\Aura\Http\Livewire\User\TwoFactorAuthenticationForm;
use Eminiarts\Aura\Policies\ResourcePolicy;
use Eminiarts\Aura\Policies\TeamPolicy;
use Eminiarts\Aura\Policies\UserPolicy;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Widgets\Bar;
use Eminiarts\Aura\Widgets\Donut;
use Eminiarts\Aura\Widgets\Pie;
use Eminiarts\Aura\Widgets\SparklineArea;
use Eminiarts\Aura\Widgets\SparklineBar;
use Eminiarts\Aura\Widgets\ValueWidget;
use Eminiarts\Aura\Widgets\Widgets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class AuraServiceProvider extends PackageServiceProvider
{
    // boot
    public function boot()
    {
        parent::boot();

        // ray('boot');
    }

    public function bootGate()
    {
        if (config('aura.teams')) {
            Gate::policy(Team::class, TeamPolicy::class);
        }

        Gate::policy(Resource::class, ResourcePolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::before(function ($user, $ability) {
            //  if ($ability == 'view' && config('aura.resource-view-enabled') === false) {
            //     return false;
            // }

            // if ($user->resource->isSuperAdmin()) {
            //     return true;
            // }
        });

        return $this;
    }

    public function bootLivewireComponents()
    {
        Livewire::component('app.aura.widgets.post-stats', \Eminiarts\Aura\Widgets\PostStats::class);
        // Livewire::component('app.aura.widgets.total-posts', \Eminiarts\Aura\Widgets\TotalPosts::class);
        Livewire::component('app.aura.widgets.post-chart', \Eminiarts\Aura\Widgets\PostChart::class);
        Livewire::component('app.aura.widgets.sum-posts-number', \Eminiarts\Aura\Widgets\SumPostsNumber::class);
        Livewire::component('app.aura.widgets.avg-posts-number', \Eminiarts\Aura\Widgets\AvgPostsNumber::class);
        Livewire::component('aura::post-index', Index::class);
        Livewire::component('aura::post-create', Create::class);
        Livewire::component('aura::post-create-modal', CreateModal::class);
        Livewire::component('aura::post-edit', Edit::class);
        Livewire::component('aura::post-edit-modal', EditModal::class);
        Livewire::component('aura::post-view', View::class);
        Livewire::component('aura::table', app(Table::class));
        Livewire::component('aura::navigation', Navigation::class);
        Livewire::component('aura::global-search', GlobalSearch::class);
        Livewire::component('aura::bookmark-page', BookmarkPage::class);
        Livewire::component('aura::notifications', Notifications::class);
        Livewire::component('aura::edit-posttype-field', EditPosttypeField::class);
        Livewire::component('aura::media-manager', MediaManager::class);
        Livewire::component('aura::media-uploader', MediaUploader::class);
        Livewire::component('aura::attachment-index', AttachmentIndex::class);
        Livewire::component('aura::user-two-factor-authentication-form', TwoFactorAuthenticationForm::class);
        Livewire::component('aura::create-posttype', CreatePosttype::class);
        Livewire::component('aura::create-taxonomy', CreateTaxonomy::class);
        Livewire::component('aura::edit-posttype', Posttype::class);
        Livewire::component('aura::taxonomy-index', TaxonomyIndex::class);
        Livewire::component('aura::taxonomy-edit', TaxonomyEdit::class);
        Livewire::component('aura::taxonomy-create', TaxonomyCreate::class);
        Livewire::component('aura::taxonomy-view', TaxonomyView::class);
        Livewire::component('aura::team-settings', TeamSettings::class);
        Livewire::component('aura::invite-user', InviteUser::class);
        Livewire::component('aura::config', Config::class);

        Livewire::component('aura::profile', Profile::class);

        // Flows
        Livewire::component('aura::create-flow', CreateFlow::class);
        Livewire::component('aura::edit-operation', EditOperation::class);

        // Widgets
        Livewire::component('aura::widgets', Widgets::class);
        Livewire::component('aura::widgets.value-widget', ValueWidget::class);
        Livewire::component('aura::widgets.sparkline-area', SparklineArea::class);
        Livewire::component('aura::widgets.sparkline-bar', SparklineBar::class);
        Livewire::component('aura::widgets.donut', Donut::class);
        Livewire::component('aura::widgets.pie', Pie::class);
        Livewire::component('aura::widgets.bar', Bar::class);

        return $this;
    }

    /*
    * This class is a Package Service Provider
    *
    * More info: https://github.com/spatie/laravel-package-tools
    */
    public function configurePackage(Package $package): void
    {
        // ray('configuring package');

        $package
            ->name('aura')
            ->hasConfigFile()
            ->hasViews('aura')
            ->hasAssets()
            ->hasRoute('web')
            ->hasMigrations(['create_aura_tables', 'create_flows_table'])
            ->runsMigrations()
            ->hasCommands([
                AuraCommand::class,
                MakePosttype::class,
                MakeTaxonomy::class,
                MakeUser::class,
                CreateAuraPlugin::class,
                MakeField::class,
                PublishCommand::class,
                CreateResourceMigration::class,
                DatabaseToResources::class,
                TransformTableToResource::class,
                CreateResourcePermissions::class,
            ])
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->startWith(function (InstallCommand $command) {
                        $command->info('Hello, thank you for installing Aura!');
                    })
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->copyAndRegisterServiceProviderInApp()
                    ->askToStarRepoOnGitHub('eminiarts/aura-cms');
            });
    }

    public function packageBooted()
    {
        // ray('package booted');
        if ($this->app->runningInConsole()) {
            $this->publishes([
                $this->package->basePath('/../resources/dist') => public_path("vendor/{$this->package->shortName()}"),
                $this->package->basePath('/../resources/public') => public_path("vendor/{$this->package->shortName()}/public"),
            ], "{$this->package->shortName()}-assets");
        }

        Component::macro('notify', function ($message, $type = 'success') {
            $this->dispatchBrowserEvent('notify', ['message' => $message, 'type' => $type]);
        });

        // Search in multiple columns
        Builder::macro('searchIn', function ($columns, $search) {
            return $this->where(function ($query) use ($columns, $search) {
                foreach (Arr::wrap($columns) as $column) {
                    $query->orWhere($column, 'like', '%'.$search.'%');
                    // $query->orWhere($column, 'like', $search . '%');
                }
            });
        });

        // CheckCondition Blade Directive
        Blade::if('checkCondition', function ($model, $field, $post = null) {
            return \Eminiarts\Aura\Aura::checkCondition($model, $field, $post);
        });

        Blade::if('superadmin', function () {
            return auth()->user()->resource->isSuperAdmin();
        });

        Blade::if('local', function () {
            return app()->environment('local');
        });

        Blade::if('production', function () {
            return app()->environment('production');
        });

        // Register the morph map for the resources
        // $resources = Aura::resources()->mapWithKeys(function ($resource) {
        //     return [$resource => 'Eminiarts\Aura\Resources\\'.str($resource)->title];
        // })->toArray();

        $this
            ->bootGate()
            ->bootLivewireComponents();
    }

    public function packageRegistered()
    {
        parent::packageRegistered();

        $this->app->scoped('aura', function (): Aura {
            return new Aura();
        });

        // dd(config('aura.resources.user'));

        Aura::registerResources([
            \Eminiarts\Aura\Resources\Attachment::class,
            \Eminiarts\Aura\Resources\Option::class,
            \Eminiarts\Aura\Resources\Post::class,
            \Eminiarts\Aura\Resources\Permission::class,
            \Eminiarts\Aura\Resources\Role::class,
            // \Eminiarts\Aura\Resources\User::class,
            config('aura.resources.user'),
            // config('aura::resources.user'),
        ]);

        if (config('aura.teams')) {
            Aura::registerResources([
                config('aura.resources.team'),
                config('aura.resources.team-invitation'),
            ]);
        }

        Aura::registerTaxonomies([
            \Eminiarts\Aura\Taxonomies\Tag::class,
            \Eminiarts\Aura\Taxonomies\Category::class,
        ]);

        // Register Fields from src/Fields
        $fields = collect(app('files')->files(__DIR__.'/Fields'))->map(function ($field) {
            return 'Eminiarts\Aura\Fields\\'.str($field->getFilename())->replace('.php', '')->title;
        })->toArray();

        Aura::registerFields($fields);

        // Register App Resources
        Aura::registerResources(Aura::getAppResources());
        Aura::registerTaxonomies(Aura::getAppTaxonomies());
        Aura::registerWidgets(Aura::getAppWidgets());
        Aura::registerFields(Aura::getAppFields());
    }

    public function registeringPackage()
    {
        // ray('registering package');
        //$package->hasRoute('web');
        //$this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }

    protected function getResources(): array
    {
        return config('aura.resources');
    }
}

```

Default Aura Tables - Migration Stub:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_resets');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('team_user');
        Schema::dropIfExists('team_invitations');
        Schema::dropIfExists('taxonomies');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('taxonomy_relations');
        Schema::dropIfExists('options');
        Schema::dropIfExists('user_meta');
        Schema::dropIfExists('team_meta');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('post_job');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_meta');
    }

    public function up()
    {
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->rememberToken();
            if(config('aura.teams')) {
                $table->foreignId('current_team_id')->nullable();
            }
            $table->string('profile_photo_path', 2048)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::dropIfExists('password_resets');

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::dropIfExists('failed_jobs');

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::dropIfExists('personal_access_tokens');

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::dropIfExists('password_reset_tokens');

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        if(config('aura.teams')){
            Schema::create('teams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->index();
                $table->string('name');
                $table->timestamps();
                $table->softDeletes();
            });

            Schema::create('team_user', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id');
                $table->foreignId('user_id');
                $table->string('role')->nullable();
                $table->timestamps();
                $table->unique(['team_id', 'user_id']);
            });

            Schema::create('team_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
                $table->string('email');
                $table->string('role')->nullable();
                $table->timestamps();

                $table->unique(['team_id', 'email']);
            });
              
            Schema::create('team_meta', function (Blueprint $table) {
                $table->id();
                if(config('aura.teams')) {
                    $table->foreignId('team_id')->index();
                }
                $table->string('key')->nullable();
                $table->longText('value')->nullable();
            });
        }

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->text('title')->nullable();
            $table->longText('content')->nullable();
            $table->string('type', 20);
            $table->string('status', 20)->default('publish');
            $table->string('slug')->index();
            $table->foreignId('user_id')->nullable()->index();
            $table->foreignId('parent_id')->nullable()->index();
            $table->integer('order')->nullable();
            if(config('aura.teams')){
                $table->foreignId('team_id')->index();
            }
            $table->timestamps();
            $table->softDeletes();

            if(config('aura.teams')) {
                $table->index(['team_id', 'slug', 'type', 'status', 'created_at', 'id']);
            } else {
                $table->index(['slug', 'type', 'status', 'created_at', 'id']);
            }
        });

        Schema::create('post_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id');
            $table->string('key')->nullable();
            $table->longText('value')->nullable();
        });

        Schema::create('taxonomy_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('taxonomy_id');
            $table->string('key');
            $table->longText('value');
        });

        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('taxonomy');
            $table->longtext('description');
            $table->foreignId('parent');
            $table->bigInteger('count');
            if(config('aura.teams')){
                $table->foreignId('team_id')->index();
            }
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('taxonomy_relations', function (Blueprint $table) {
            $table->morphs('relatable');
            $table->foreignId('taxonomy_id');
            $table->integer('order');
        });

        Schema::create('options', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->longText('value');
            if(config('aura.teams')){
                $table->foreignId('team_id');
            }
            $table->timestamps();

            if(config('aura.teams')) {
                $table->index(['team_id', 'name']);
            } else {
                $table->index(['name']);
            }
        });

        Schema::create('user_meta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('key')->nullable();
            $table->longText('value')->nullable();
            if(config('aura.teams')){
                $table->foreignId('team_id')->index();
            }
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('post_job', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('post_id');
            $table->string('job_id');
            $table->string('job_status');
            $table->timestamps();
        });


        Schema::create('jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }
};

```