<?php

namespace Tests\Feature\PublicPortal;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_discovery_pages_render_published_catalog_data(): void
    {
        $category = Category::factory()->create(['name' => 'Pendidikan Pemilih']);
        $collection = Collection::factory()->create(['name' => 'Rak Utama']);
        $book = $this->publishedBook(['title' => 'Demokrasi untuk Semua']);
        $book->categories()->attach($category);
        $book->collections()->attach($collection);

        $this->get('/')->assertOk()->assertSee('Demokrasi untuk Semua')->assertSee('Publikasi');
        $this->get('/kategori/'.$category->slug)->assertOk()->assertSee($book->title);
        $this->get('/rak/'.$collection->slug)->assertOk()->assertSee($book->title);
        $this->get('/terbaru')->assertOk()->assertSee($book->title);
        $this->get('/terpopuler')->assertOk()->assertSee($book->title);
    }

    public function test_detail_renders_metadata_and_locked_password_state_without_private_path(): void
    {
        $public = $this->publishedBook([
            'title' => 'Pedoman Kepemiluan',
            'isbn' => '978-000-000',
            'original_file' => 'books/secret/original.pdf',
        ]);
        $locked = $this->publishedBook([
            'title' => 'Dokumen Terlindungi',
            'visibility' => BookVisibility::Password,
            'password_hash' => Hash::make('Buka-2026'),
            'original_file' => 'books/private/locked.pdf',
        ]);

        $this->get('/buku/'.$public->slug)
            ->assertOk()->assertSee('978-000-000')->assertDontSee('books/secret/original.pdf');
        $this->get('/buku/'.$locked->slug)
            ->assertOk()->assertSee('Masukkan kata sandi')->assertDontSee('books/private/locked.pdf');
        $this->post('/buku/'.$locked->slug.'/akses', ['password' => 'Buka-2026'])
            ->assertRedirect('/buku/'.$locked->slug);
        $this->get('/buku/'.$locked->slug)->assertOk()->assertSee('Baca sekarang');
    }

    public function test_detail_omits_secondary_access_card_and_redundant_metadata_rows(): void
    {
        $book = $this->publishedBook([
            'title' => 'Detail Publikasi Ringkas',
            'original_file' => 'books/private/detail.pdf',
        ]);

        $this->get('/buku/'.$book->slug)
            ->assertOk()
            ->assertSee('Baca sekarang')
            ->assertDontSee('<aside class="detail-access">', false)
            ->assertDontSee('<dt>Penerbit</dt>', false)
            ->assertDontSee('<dt>Bahasa</dt>', false)
            ->assertDontSee('Akses dokumen');
    }

    public function test_private_draft_future_and_expired_slugs_return_not_found(): void
    {
        $records = [
            Book::factory()->create(['status' => BookStatus::Draft]),
            $this->publishedBook(['visibility' => BookVisibility::Private]),
            $this->publishedBook(['published_at' => now()->addDay()]),
            $this->publishedBook(['visibility' => BookVisibility::Expiring, 'expires_at' => now()->subMinute()]),
        ];

        foreach ($records as $book) {
            $this->get('/buku/'.$book->slug)->assertNotFound();
        }
    }

    public function test_static_seo_and_machine_routes_are_available(): void
    {
        $book = $this->publishedBook(['title' => 'Buku Sitemap']);

        foreach (['/tentang', '/panduan', '/kontak', '/privasi'] as $path) {
            $this->get($path)->assertOk();
        }
        $this->get('/sitemap.xml')->assertOk()->assertSee('/buku/'.$book->slug, false);
        $this->get('/robots.txt')->assertOk()->assertSee('Sitemap:');
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
