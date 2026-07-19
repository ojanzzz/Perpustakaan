<?php

namespace Tests\Feature\Content;

use App\Models\Book;
use App\Models\Category;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $this->assertDatabaseCount('book_category', 20);
        $this->assertDatabaseCount('book_collection', 20);
        $this->assertDatabaseCount('book_author', 20);
        $this->assertDatabaseCount('book_tag', 20);
        $this->assertDatabaseCount('announcements', 1);
        $this->assertDatabaseCount('book_views', 60);
        $this->assertDatabaseMissing('books', ['status' => 'draft']);
        $this->assertSame(20, Book::query()->whereNotNull('cover_image')->count());
        $this->assertSame(10, DB::table('categories')->whereExists(
            fn ($query) => $query->selectRaw('1')->from('book_category')->whereColumn('book_category.category_id', 'categories.id')
        )->count());
        $this->assertSame(5, DB::table('collections')->whereExists(
            fn ($query) => $query->selectRaw('1')->from('book_collection')->whereColumn('book_collection.collection_id', 'collections.id')
        )->count());
        $firstPageCover = 'images/demo-covers/demo-reader-first-page.webp';
        $this->assertSame([$firstPageCover], Book::query()->distinct()->pluck('cover_image')->all());
        $this->assertFileExists(public_path($firstPageCover));
    }

    public function test_catalog_seed_restores_soft_deleted_demo_records_without_duplicates(): void
    {
        $this->seed(DatabaseSeeder::class);

        $book = Book::query()->orderBy('id')->firstOrFail();
        $category = Category::query()->orderBy('id')->firstOrFail();
        $book->delete();
        $category->delete();

        $this->seed(CatalogSeeder::class);

        $this->assertDatabaseHas('books', ['id' => $book->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'deleted_at' => null]);
        $this->assertSame(20, Book::withTrashed()->count());
        $this->assertSame(10, Category::withTrashed()->count());
    }
}
