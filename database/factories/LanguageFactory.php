<?php

namespace Database\Factories;

use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Language> */
class LanguageFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => 'Bahasa '.fake()->unique()->word(), 'code' => fake()->unique()->lexify('??'), 'is_active' => true];
    }
}
