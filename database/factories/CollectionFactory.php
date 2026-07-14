<?php

namespace Database\Factories;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Collection> */
class CollectionFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['name' => Str::title($name), 'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999), 'visibility' => 'public', 'sort_order' => 0, 'status' => 'active'];
    }
}
