<?php

namespace Tests\Feature\Delivery;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Models\Book;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityEmbedPwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_send_security_headers_and_pwa_assets_exist(): void
    {
        $this->get('/')->assertOk()
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('x-frame-options', 'SAMEORIGIN')
            ->assertHeader('referrer-policy', 'strict-origin-when-cross-origin')
            ->assertHeader('content-security-policy');
        $this->get('/manifest.webmanifest')->assertOk()->assertJsonPath('name', 'E-Perpustakaan Digital KPU');
        $this->get('/service-worker.js')->assertOk()->assertSee('eperpustakaan-shell');
    }

    public function test_embed_routes_cover_book_collection_and_category(): void
    {
        $book = $this->publishedBook();
        $collection = Collection::factory()->create(['status' => 'active']);
        $category = Category::factory()->create(['status' => 'active']);
        $book->collections()->attach($collection);
        $book->categories()->attach($category);

        $this->get("/embed/buku/{$book->slug}")->assertOk()->assertSee($book->title);
        $this->get("/embed/rak/{$collection->slug}")->assertOk()->assertSee($book->title);
        $this->get("/embed/kategori/{$category->slug}")->assertOk()->assertSee($book->title);
    }

    public function test_embed_referer_must_match_configured_domain_allowlist(): void
    {
        $book = $this->publishedBook();
        Setting::putValue('embed_allowed_domains', 'portal.kpu.go.id', 'embed');

        $this->withHeader('Referer', 'https://evil.example/page')->get("/embed/buku/{$book->slug}")->assertForbidden();
        $this->withHeader('Sec-Fetch-Dest', 'iframe')->get("/embed/buku/{$book->slug}")->assertForbidden();
        $this->withHeader('Referer', 'https://sub.portal.kpu.go.id/page')->get("/embed/buku/{$book->slug}")->assertOk();
    }

    private function publishedBook(): Book
    {
        return Book::factory()->create(['status' => BookStatus::Published, 'visibility' => BookVisibility::Public, 'published_at' => now()->subMinute()]);
    }
}
