<?php

namespace Tests\Feature\PublicPortal;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Publisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_searches_metadata_relations_and_logs_a_privacy_preserving_term(): void
    {
        $author = Author::factory()->create(['name' => 'Siti Demokrasi']);
        $publisher = Publisher::factory()->create(['name' => 'Penerbit Nusantara']);
        $category = Category::factory()->create(['name' => 'Pendidikan Pemilih']);
        $collection = Collection::factory()->create(['name' => 'Rak Demokrasi']);
        $book = $this->publishedBook(['title' => 'Panduan Warga', 'publisher_id' => $publisher->id]);
        $book->authors()->attach($author);
        $book->categories()->attach($category);
        $book->collections()->attach($collection);
        $this->publishedBook(['title' => 'Dokumen Lain']);

        $response = $this->get('/katalog?q=Siti+Demokrasi');

        $response->assertOk()->assertSee('Panduan Warga')->assertDontSee('Dokumen Lain');
        $this->assertDatabaseHas('search_logs', [
            'query' => 'Siti Demokrasi',
            'normalized_query' => 'siti demokrasi',
            'result_count' => 1,
        ]);
    }

    public function test_catalog_combines_filters_sort_and_view_mode_and_preserves_them_in_pagination(): void
    {
        $category = Category::factory()->create();
        $matching = $this->publishedBook(['title' => 'Buku Zeta', 'publication_year' => 2024]);
        $matching->categories()->attach($category);
        $other = $this->publishedBook(['title' => 'Buku Alfa', 'publication_year' => 2020]);
        $other->categories()->attach($category);

        $response = $this->get('/katalog?category='.$category->id.'&year_from=2023&sort=title_desc&mode=list');

        $response->assertOk()
            ->assertSee('Buku Zeta')
            ->assertDontSee('Buku Alfa')
            ->assertSee('mode=list', false);
    }

    public function test_suggestions_are_bounded_and_hide_unlisted_records(): void
    {
        foreach (range(1, 8) as $number) {
            $this->publishedBook(['title' => sprintf('Panduan Pemilu %02d', $number)]);
        }
        $this->publishedBook(['title' => 'Panduan Pemilu Rahasia', 'visibility' => BookVisibility::Unlisted]);

        $response = $this->getJson('/api/search/suggestions?q=Panduan');

        $response->assertOk()->assertJsonCount(6, 'data');
        $this->assertNotContains('Panduan Pemilu Rahasia', collect($response->json('data'))->pluck('title'));
    }

    public function test_filter_options_do_not_reveal_publication_types_from_hidden_records(): void
    {
        $this->publishedBook(['publication_type' => 'Panduan Publik']);
        $this->publishedBook(['publication_type' => 'Klasifikasi Internal', 'visibility' => BookVisibility::Private]);

        $this->get('/katalog')
            ->assertOk()
            ->assertSee('Panduan Publik')
            ->assertDontSee('Klasifikasi Internal');
    }

    private function publishedBook(array $attributes = []): Book
    {
        return Book::factory()->create([
            'status' => BookStatus::Published,
            'visibility' => BookVisibility::Public,
            'published_at' => now()->subMinute(),
            ...$attributes,
        ]);
    }
}
