<?php

namespace Aura\Base\Database\Factories;

use Aura\Base\Models\Meta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meta>
 */
class MetaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Meta>
     */
    protected $model = Meta::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'key' => $this->faker->randomElement(['meta_1', 'meta_2', 'meta_3', 'meta_4', 'meta_5', 'meta_6', 'meta_7', 'meta_8', 'meta_9', 'meta_10']),
            'value' => $this->faker->sentence(),
        ];
    }
}
