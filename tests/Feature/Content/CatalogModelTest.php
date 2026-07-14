<?php

namespace Tests\Feature\Content;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Publisher;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_book_metadata_relationships_are_persisted(): void
    {
        $book = Book::factory()->create();
        $category = Category::factory()->create();
        $collection = Collection::factory()->create();
        $author = Author::factory()->create();
        $tag = Tag::factory()->create();

        $book->categories()->attach($category);
        $book->collections()->attach($collection, ['sort_order' => 3]);
        $book->authors()->attach($author);
        $book->tags()->attach($tag);

        $book->load('categories', 'collections', 'authors', 'tags', 'publisher', 'language');

        $this->assertTrue($book->categories->contains($category));
        $this->assertTrue($book->collections->contains($collection));
        $this->assertTrue($book->authors->contains($author));
        $this->assertTrue($book->tags->contains($tag));
        $this->assertInstanceOf(Publisher::class, $book->publisher);
        $this->assertNotNull($book->language);
    }

    public function test_category_hierarchy_is_available(): void
    {
        $parent = Category::factory()->create();
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        $this->assertTrue($parent->children->contains($child));
        $this->assertTrue($child->parent->is($parent));
    }
}
