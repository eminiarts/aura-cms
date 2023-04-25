Generate user friendly documentation for our project Aura CMS that includes explanations and descriptions of all functionality. Please create an outline about the complete documentation in general before continuing with specific topics. Write everything in markdown. 

I will provide you with more information about Aura CMS: It is build on Laravel, Livewire, AlpineJS and TailwindCSS. It requires 8.1. 

- Resources: We have a Resource model that can be used for blog posts, news, events, etc. The posts can be categorized and tagged. The concept is based on WordPress' Posts and Taxonomies. A Resource only has a title, a slug, a description and a body (default WordPress fields). All other fields are added by the user like you would add custom fields with ACF in Wordpress (conceptually). This is why we have a posts and postmeta table. The postmeta table is used to store all additional fields. This means that a Resource can have many postmeta entries. The postmeta entries are used to store additional fields like a date, a location, a price, etc.
- Posttype Builder: The Posttype Builder is a tool that allows the user to create custom posttypes. A posttype is a custom Resource. The user can add custom fields to the posttype. The user can also add custom taxonomies to the posttype. In the beginning we encourage to use the posts table for a Resource. When ever you are done with all fields, you can generate a migration file that will create a new table for the posttype. This way you can use the Resource with a custom Table. This is useful when you have a lot of data and want to optimize the database.
- Taxonomies: Taxonomies are the method of classifying content and data. When you use a taxonomy you’re grouping similar things together. The taxonomy refers to the sum of those groups. As with Post Types, there are a number of default taxonomies, and you can also create your own.ies 
- Fields: We have a lot of different fields that you can use on the Resources. These are the fields that we have: Advanced Select, BelongsTo, BelongsToMany, Boolean, Checkbox, Code, Color, Date, Datetime, Email, Embed, Field, File, Group, HasMany, HasOne, HasOneOfMany, Heading, HorizontalLine, Image, Jobs, chp Json, LivewireComponent, Number, Panel, Password, Permissions, Phone, Radio, Repeater, Select, SelectRelation, Slug, Tab, Tabs, Tags, Text, Textarea, Time, View, ViewValue,Wysiwyg. Every Field needs to be documented.
- Actions: A resource can have bulkActions or actions defined that you can trigger on the resource. For example: You can create an action that will send an email to all users that are selected. You can also create an action that will delete all selected users. 
- Table: The table is the default view of a resource. You can customize the table by adding a custom View for the row. There are multiple places where you can override the default table view. You can override the table view for a specific resource, you can override the table view for a specific posttype or you can override the table view for all resources. The table also includes a search and custom filters. The filters are based on the fields that are defined on the resource. 
- Teams, Users, Roles and Permissions: Aura is multitenant by default. You can create teams and invite users to the team. The users can have different roles on the team. The roles can be defined by the user. The user can also define permissions for the roles. You can disable multitenant if you want to use Aura as a single tenant application. Permissions can be created for all resources.
- Media Library: AuraCMS has a media library that allows you to upload files and images. These files and images can be used in the resources.
- Flows: will be documented later.

Resource Fields: I will show you some example fields, so you have an understanding on how a Resource can be structured:

// getFields() method of the User Resource
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

// getFields() method of the Post Resource
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
                 'slug' => 'slug2',
                 'based_on' => 'text',
             ],
             [
                 'name' => 'Bild',
                 'type' => 'Eminiarts\\Aura\\Fields\\Image',
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
               [
                   'name' => 'Color',
                   'type' => 'Eminiarts\\Aura\\Fields\\Color',
                   'validation' => '',
                   'conditional_logic' => [
                   ],
                   'slug' => 'color',
                   'on_index' => true,
                   'on_forms' => true,
                   'on_view' => true,
                   'format' => 'hex',
               ],
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
               [
                   'name' => 'Team',
                   'slug' => 'team_id',
                   'type' => 'Eminiarts\\Aura\\Fields\\BelongsTo',
                   'resource' => 'Eminiarts\\Aura\\Resources\\Team',
                   'validation' => '',
                   'conditional_logic' => [
                       [
                           'field' => 'role',
                           'operator' => '==',
                           'value' => 'super_admin',
                       ],
                   ],
                   'wrapper' => '',
                   'on_index' => true,
                   'on_forms' => true,
                   'on_view' => true,
               ],
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
             [
                 'name' => 'Created at',
                 'slug' => 'created_at',
                 'type' => 'Eminiarts\\Aura\\Fields\\Date',
                 'validation' => '',
                 'enable_time' => true,
                 'conditional_logic' => [],
                 'wrapper' => '',
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
             [
                 'name' => 'Updated at',
                 'slug' => 'updated_at',
                 'type' => 'Eminiarts\\Aura\\Fields\\Date',
                 'validation' => '',
                 'conditional_logic' => [],
                 'wrapper' => '',
                 'enable_time' => true,
                 'on_index' => true,
                 'on_forms' => true,
                 'on_view' => true,
             ],
         ];
     }

// getFields() of the Team Resource
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

// getFields of the Attachment Resource
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
            [
                'name' => 'Created at',
                'slug' => 'created_at',
                'type' => 'Eminiarts\\Aura\\Fields\\Date',
                'validation' => '',
                'enable_time' => true,
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Updated at',
                'slug' => 'updated_at',
                'type' => 'Eminiarts\\Aura\\Fields\\Date',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'enable_time' => true,
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '50',
                ],
            ],
        ];
    }

I will now also give you the code of the Resource, so you can get a better understanding of the CMS:

```php
<?php

namespace Eminiarts\Aura;

use Illuminate\Support\Str;
use Aura\Flows\Resources\Flow;
use Eminiarts\Aura\Resources\User;
use Eminiarts\Aura\Traits\SaveTerms;
use Eminiarts\Aura\Traits\InputFields;
use Illuminate\Database\Eloquent\Model;
use Eminiarts\Aura\Traits\AuraTaxonomies;
use Eminiarts\Aura\Traits\SaveMetaFields;
use Eminiarts\Aura\Traits\AuraModelConfig;
use Eminiarts\Aura\Models\Scopes\TeamScope;
use Eminiarts\Aura\Models\Scopes\TypeScope;
use Eminiarts\Aura\Traits\InitialPostFields;
use Eminiarts\Aura\Traits\InteractsWithTable;
use Eminiarts\Aura\Traits\SaveFieldAttributes;
use Aura\Flows\Jobs\TriggerFlowOnCreatePostEvent;
use Aura\Flows\Jobs\TriggerFlowOnUpdatePostEvent;
use Aura\Flows\Jobs\TriggerFlowOnDeletedPostEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasTimestamps;

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

    protected $appends = ['fields'];
    // protected $hidden = ['meta'];

    protected $fillable = ['title', 'content', 'type', 'status', 'fields', 'slug', 'user_id', 'parent_id', 'order', 'taxonomies', 'terms', 'team_id', 'first_taxonomy', 'created_at', 'updated_at', 'deleted_at'];

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

        // Not sure if this is the best way to do this
        return $this->displayFieldValue($key, $value);

        // For now
        if ($value === null) {
            return $this->displayFieldValue($key);
        }

        if ($value === null && ! property_exists($this, $key)) {
            return $this->meta->$key;
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
        return $this->hasMany(self::class, 'post_parent');
    }

    public function getBulkActions()
    {
        return $this->bulkActions;
    }

    public function getExcerptAttribute()
    {
        return $this->stripShortcodes($this->post_excerpt);
    }

    public function getFieldsAttribute()
    {
        // if $this->usesMeta is false, then we don't want to load the meta
        if ($this->usesMeta() && optional($this)->meta) {
            $meta = $this->meta->pluck('value', 'key');

            // Cast Attributes
            $meta = $meta->map(function ($meta, $key) {
                $class = $this->fieldClassBySlug($key);

                if ($class && method_exists($class, 'get')) {
                    return $class->get($class, $meta);
                }

                return $meta;
            });
        }


        $defaultValues = $this->getFieldSlugs()->mapWithKeys(fn ($value, $key) => [$value => null])->map(fn ($value, $key) => $meta[$key] ?? $value)->map(function ($value, $key) {
            // if the value is in $this->hidden, set it to null
            if (in_array($key, $this->hidden)) {
                return;
            }

            // if there is a function get{Slug}Field on the model, use it
            $method = 'get'.Str::studly($key).'Field';

            if (method_exists($this, $method)) {
                return $this->{$method}();
            }

            $class = $this->fieldClassBySlug($key);

            if ($class && isset($this->{$key}) && method_exists($class, 'get')) {
                return $class->get($class, $this->{$key});
            }

            // if $this->{$key} is set, then we want to use that
            if (isset($this->{$key})) {
                return $this->{$key};
            }

            // if $this->attributes[$key] is set, then we want to use that
            if (isset($this->attributes[$key])) {
                return $this->attributes[$key];
            }
        });

        return $defaultValues->merge($meta ?? [])->filter(function ($value, $key) {

            if (! in_array($key, $this->getAccessibleFieldKeys())) {
                return false;
            }
            return true;

        });
    }

    /**
     * Gets the featured image if any
     * Looks in meta the _thumbnail_id field.
     *
     * @return string
     */
    public function getImageAttribute()
    {
        if ($this->thumbnail and $this->thumbnail->attachment) {
            return $this->thumbnail->attachment->guid;
        }
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

            if (in_array($method, $modelMethods) && ($this->{$method}() instanceof \Illuminate\Database\Eloquent\Relations\Relation)) {
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
        return $this->belongsTo(self::class, 'post_parent');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function revision()
    {
        return $this->hasMany(self::class, 'post_parent')
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
        if(! $this->getWidgets()) {
            return;
        }

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
        if (! static::$customTable) {
            static::addGlobalScope(new TypeScope());
        }

        static::addGlobalScope(new TeamScope());

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait AuraModelConfig
{
    public array $actions = [];

    public array $bulkActions = [];

    public static $customTable = false;

    public static $globalSearch = true;

    public array $metaFields = [];

    public static $pluralName = null;

    public static $singularName = null;

    public static $taxonomy = false;

    public array $taxonomyFields = [];

    public static bool $usesMeta = true;

    public static $contextMenu = true;

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

    protected static ?int $sort = 1000;

    protected static bool $title = false;

    protected static string $type = 'Resource';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->baseFillable = $this->getFillable();

        // Merge fillable fields from fields
        $this->mergeFillable($this->getFieldSlugs()->toArray());
    }

    /**
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        // Title is a special case, for now
        if ($key == 'title') {
            return $this->getAttributeValue($key);
        }

        $value = parent::__get($key);

        if ($value) {
            return $value;
        }

        return $this->displayFieldValue($key, $value);
    }

    public function createUrl()
    {
        return route('aura.post.create', [$this->getType()]);
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

    public function editUrl()
    {
        if ($this->getType() && $this->id) {
            return route('aura.post.edit', ['slug' => $this->getType(), 'id' => $this->id]);
        }
    }

    public function getActions()
    {
        return $this->actions;
    }

    public function getBaseFillable()
    {
        return $this->baseFillable;
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

    public static function getName(): ?string
    {
        return static::$name;
    }

    public static function getNextId()
    {
        $model = new static();

        $query = "show table status like '".$model->getTable()."'";

        $statement = DB::select($query);

        return $statement[0]->Auto_increment;
    }

    public static function getPluralName(): string
    {
        return str(static::$type)->plural();
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
        if (in_array($key, $this->getAccessibleFieldKeys())) {
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
        if (in_array($key, $this->getAccessibleFieldKeys())) {
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
        return $this->hasMany(Meta::class, 'post_id')
        ;
    }

    public function navigation()
    {
        return [
            'icon' => $this->icon(),
            'resource' => get_class($this),
            'type' => $this->getType(),
            'name' => $this->getName() ?? str($this->getType())->plural(),
            'slug' => $this->getSlug(),
            'sort' => $this->getSort(),
            'group' => $this->getGroup(),
            'dropdown' => $this->getDropdown(),
            'showInNavigation' => $this->getShowInNavigation(),
        ];
    }

    public function pluralName(): string
    {
        return static::$pluralName ?? Str::plural($this->singularName());
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

    public function singularName(): string
    {
        return static::$singularName ?? Str::title(static::$slug);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function title()
    {
        return $this->getType()." (#{$this->id})";
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

    public function viewUrl()
    {
        if ($this->getType() && $this->id) {
            return route('aura.post.view', ['slug' => $this->getType(), 'id' => $this->id]);
        }
    }

    public static function getSort(): ?int
    {
        return static::$sort;
    }

    public static function getContextMenu()
    {
        return static::$contextMenu;
    }
}

```

Trait: InputFields.php
```php
<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\ConditionalLogic;
use Eminiarts\Aura\Pipeline\ApplyTabs;
use Eminiarts\Aura\Pipeline\MapFields;
use Eminiarts\Aura\Pipeline\AddIdsToFields;
use Eminiarts\Aura\Pipeline\TransformSlugs;
use Eminiarts\Aura\Pipeline\FilterEditFields;
use Eminiarts\Aura\Pipeline\FilterViewFields;
use Eminiarts\Aura\Pipeline\FilterCreateFields;
use Eminiarts\Aura\Pipeline\BuildTreeFromFields;
use Eminiarts\Aura\Pipeline\RemoveValidationAttribute;
use Eminiarts\Aura\Pipeline\ApplyParentConditionalLogic;
use Eminiarts\Aura\Pipeline\ApplyParentDisplayAttributes;

trait InputFields
{
    use InputFieldsHelpers;
    use InputFieldsTable;
    use InputFieldsValidation;

    public function createFields()
    {
        // Apply Conditional Logic of Parent Fields
        return $this->sendThroughPipeline($this->fieldsCollection(), [
            ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
            ApplyParentDisplayAttributes::class,
            FilterCreateFields::class,
            BuildTreeFromFields::class,
        ]);
    }

    public function displayFieldValue($key, $value = null)
    {
        // Check Conditional Logic if the field should be displayed
        if (! $this->shouldDisplayField($key)) {
            return;
        }

        $studlyKey = str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $key)));

        // If there is a get{key}Field() method, use that
        if ($value && method_exists($this, 'get'.ucfirst($studlyKey).'Field')) {
            return $this->{'get'.ucfirst($key).'Field'}($value);
        }

        // Maybe delete this one?
        if (optional($this->fieldBySlug($key))['display'] && $value) {
            return $this->fieldBySlug($key)['display']($value);
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
            ApplyParentDisplayAttributes::class,
            FilterEditFields::class,
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

    public function getAccessibleFieldKeys()
    {
        // Apply Conditional Logic of Parent Fields
        $fields = $this->sendThroughPipeline($this->fieldsCollection(), [
            ApplyTabs::class,
            MapFields::class,
            AddIdsToFields::class,
            ApplyParentConditionalLogic::class,
        ]);

        // Get all input fields
        return $fields
            ->filter(function ($field) {
                return $field['field']->isInputField();
                // return in_array($field['field']->type, ['input', 'repeater', 'group']);
            })
            ->pluck('slug')
            ->filter(function ($field) {
                return $this->shouldDisplayField($field);
            })->toArray();
    }

    public function getFieldsBeforeTree($fields = null)
    {
        // If fields is set and is an array, create a collection
        if ($fields && is_array($fields)) {
            $fields = collect($fields);
        }

        if (! $fields) {
            $fields = $this->fieldsCollection();
        }

        return $this->sendThroughPipeline($fields, [
            MapFields::class,
            AddIdsToFields::class,
            TransformSlugs::class,
            ApplyParentConditionalLogic::class,
        ]);
    }

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

    public function shouldDisplayField($key)
    {
        // Check Conditional Logic if the field should be displayed
        return ConditionalLogic::checkCondition($this, $this->fieldBySlug($key));
    }

    public function taxonomyFields()
    {
        return $this->mappedFields()->filter(function ($field) {
            if ($field['field']->isTaxonomyField()) {
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
            ApplyParentDisplayAttributes::class,
            FilterViewFields::class,
            BuildTreeFromFields::class,
        ]);
    }

}
```

InputfieldHelpers.php
```php
<?php

namespace Eminiarts\Aura\Traits;

use Eminiarts\Aura\Pipeline\ApplyGroupedInputs;
use Illuminate\Pipeline\Pipeline;

trait InputFieldsHelpers
{
    public function fieldBySlug($slug)
    {
        return $this->fieldsCollection()->firstWhere('slug', $slug);
    }

    public function fieldClassBySlug($slug)
    {
        if (optional($this->fieldBySlug($slug))['type']) {
            return app($this->fieldBySlug($slug)['type']);
        }

        return false;
    }

    public function fieldsCollection()
    {
        return collect($this->getFields());
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
        return $this->inputFields()->pluck('slug');
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

    public function mappedFieldBySlug($slug)
    {
        return $this->mappedFields()->firstWhere('slug', $slug);
    }

    public function mappedFields()
    {
        return $this->fieldsCollection()->map(function ($item) {
            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        });
    }

    public function sendThroughPipeline($fields, $pipes)
    {
        return app(Pipeline::class)
        ->send($fields)
        ->through($pipes)
        ->thenReturn();
    }
}

```