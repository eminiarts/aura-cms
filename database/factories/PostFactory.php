<?php

namespace Eminiarts\Aura\Database\Factories;

use Eminiarts\Aura\Resources\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Eminiarts\Aura\Resources\Post>
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
            'type' => "Post", //$this->faker->randomElement(['Post', 'Project', 'Invoice', 'Page', 'Post', 'Post']),
            'user_id' => 1,
            'team_id' => 1,
            'description' => $this->faker->paragraph(),
            'number' => $this->faker->randomNumber(),
            'text' => $this->faker->sentence(),
            'terms' => [
                'Tag' => [
                    'tag1',
                    'tag2',
                    'tag3',
                ],
                'Category' => [
                    'category1',
                    'category2',
                    'category3',
                ],
            ],
            'parent_id' => null,
            'order' => null,

        ];
    }
}
