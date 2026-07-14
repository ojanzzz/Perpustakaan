<?php

namespace Tests\Feature\Content;

use App\Models\Book;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_development_seed_contains_required_catalog_samples(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('categories', 10);
        $this->assertDatabaseCount('collections', 5);
        $this->assertDatabaseCount('books', 20);
        $this->assertDatabaseCount('announcements', 1);
        $this->assertDatabaseCount('book_views', 60);
        $this->assertDatabaseMissing('books', ['status' => 'draft']);
        $this->assertSame(20, Book::query()->whereNotNull('cover_image')->count());
        $this->assertFileExists(public_path('images/demo-covers/civic-red.webp'));
    }
}
