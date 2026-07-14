<?php

namespace Database\Factories;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Enums\ProcessingStatus;
use App\Models\Book;
use App\Models\Language;
use App\Models\Publisher;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Book> */
class BookFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => fake()->paragraph(),
            'publisher_id' => Publisher::factory(),
            'language_id' => Language::factory(),
            'publication_year' => fake()->numberBetween(2000, 2026),
            'processing_status' => ProcessingStatus::Completed,
            'status' => BookStatus::Draft,
            'visibility' => BookVisibility::Public,
            'created_by' => User::factory(),
        ];
    }
}
