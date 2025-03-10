<?php

namespace Aura\Base\Tests\Factories;

use Aura\Base\Tests\Resources\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Aura\Base\Tests\Resources\Post>
 */
class PostFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Post::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'title' => $name = $this->faker->name(),
            'slug' => str($name)->slug(),
            'content' => $this->faker->paragraph(),
            'type' => 'Post',
            'user_id' => 1,
            'team_id' => 1,
            'parent_id' => null,
            'order' => null,

        ];
    }
}
