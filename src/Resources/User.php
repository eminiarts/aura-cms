<?php

namespace Eminiarts\Aura\Resources;

use App\Models\Post;
use App\Models\UserMeta;
use App\Aura\Resources\Resource;

class User extends Post
{
    protected $table = 'users';

    public static string $type = 'User';

    public static ?string $slug = 'user';

    protected static ?string $group = 'Users';

    public static ?int $sort = 1;

    protected static bool $title = false;

    public function getTitleAttribute()
    {
        return $this->email;
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public static function getFields()
    {
        return [
            '.name' => [
                'label' => 'Name',
                'name' => 'Name',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            '.email' => [
                'label' => 'Email',
                'name' => 'Email',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'required|email',
                'on_index' => true,
                'display' => function ($value) {
                    return "<a class='font-bold text-sky-500' href='mailto:" . $value ."'>" . $value ."</a>";
                },
                'slug' => 'email',
                'style' => [
                    'width' => '100',
                ],
            ],
            '.roles' => [
                'name' => 'Roles',
                'slug' => 'roles',
                'id' => 159,
                'posttype' => 'App\\Aura\\Resources\\Role',
                'type' => 'App\\Aura\\Fields\\SelectRelation',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => true,
                'on_forms' => true,
                'in_view' => true,
            ],
            '.panel' => [
                'name' => 'Posts',
                'slug' => 'posts',
                'id' => 155,
                'type' => 'App\\Aura\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'in_view' => true,
            ],
            'posts' => [
                'name' => 'Posts',
                'slug' => 'posts',
                'id' => 149,
                'type' => 'App\\Aura\\Fields\\HasMany',
                'posttype' => 'App\\Aura\\Resources\\Post',
                'validation' => '',
                'conditional_logic' => '',
                'has_conditional_logic' => false,
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => false,
                'in_view' => true,
            ],
        ];
    }

    public function actions()
    {
        return [
            'edit'
        ];
    }

    public function icon()
    {
        return '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>';
    }

    /**
    * The "booted" method of the model.
    *
    * @return void
    */
    protected static function booted()
    {
        static::saving(function ($user) {
            // dd('saving', $user, $user->attributes);

            if (isset($user->attributes['fields'])) {
                foreach ($user->attributes['fields'] as $key => $value) {
                    $class = $user->fieldClassBySlug($key);

                    if ($class && method_exists($class, 'set')) {
                        $value = $class->set($value);
                    }

                    if (optional($user->attributes)[$key]) {
                        $user->{$key} = $value;
                    } else {
                        $user->meta()->updateOrCreate(['key' => $key], ['value' => $value]);
                    }
                }

                unset($user->attributes['fields']);
            }


            if (isset($user->attributes['terms'])) {
                // $values = [];

                // foreach ($user->attributes['terms'] as $key => $value) {
                //     if ($key == 'Tag') {
                //         $values[] = str($value)->explode(',')
                //         ->map(fn ($i) => trim($i))
                //         ->map(function ($item) use ($key) {
                //             return Aura::findTaxonomyBySlug($key)::firstOrCreate(['name' => $item])->id;
                //         })->mapWithKeys(fn ($i, $k) => [$i => ['order' => $k]])->toArray()
                //         ;

                //         continue;
                //     }

                //     // Get the Correct Order
                //     $values[] = collect($value)->mapWithKeys(fn ($i, $k) => [$i => ['order' => $k]])->toArray();
                // }


                // $values = collect($values)->mapWithKeys(function ($a) {
                //     return $a;
                // });

                // $user->taxonomies()->sync($values);

                unset($user->attributes['terms']);
            }
        });
    }

    public function getHeaders()
    {
        return $this->indexFields()->pluck('name', 'slug');
    }

    public function meta()
    {
        return $this->hasMany(UserMeta::class, 'user_id');
    }
}
