<?php

namespace Tests\Feature\PublicPortal;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCardAnimationContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_book_cards_render_a_layered_cover_without_changing_link_semantics(): void
    {
        $book = Book::factory()->create([
            'title' => 'Buku Interaktif',
            'status' => BookStatus::Published,
            'visibility' => BookVisibility::Public,
            'published_at' => now()->subMinute(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('href="'.route('books.show', $book).'"', false)
            ->assertSee('class="book-cover-pages"', false)
            ->assertSee('class="book-cover-face"', false)
            ->assertSee('aria-hidden="true"', false);
    }

    public function test_generated_and_bundled_covers_resolve_from_their_correct_public_locations(): void
    {
        $generated = Book::factory()->create(['cover_image' => 'covers/41/cover.webp']);
        $bundled = Book::factory()->create(['cover_image' => 'images/demo-covers/demo-reader-first-page.webp']);

        $this->assertSame(asset('storage/covers/41/cover.webp'), $generated->coverUrl());
        $this->assertSame(asset('images/demo-covers/demo-reader-first-page.webp'), $bundled->coverUrl());
    }

    public function test_book_card_css_has_pointer_keyboard_and_motion_safe_3d_states(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        $component = file_get_contents(resource_path('views/components/public/book-card.blade.php'));

        $this->assertStringContainsString('@media (hover: hover) and (pointer: fine)', $css);
        $this->assertStringContainsString('perspective: 1400px', $css);
        $this->assertStringContainsString('.book-card a:is(:hover, :focus-visible) .book-cover-face', $css);
        $this->assertStringContainsString('rotateY(-40deg)', $css);
        $this->assertStringContainsString('.book-card .book-cover-pages', $css);
        $this->assertStringContainsString('#fbfae8', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('.book-card a:is(:hover, :focus-visible) .book-cover', $css);
        $this->assertStringNotContainsString('focus:outline-none', $component);
    }

    public function test_page_block_and_heavy_shadows_are_scoped_to_card_consumers(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('.book-cover-pages { @apply absolute hidden', $css);
        $this->assertStringContainsString('.book-card .book-cover-pages { display: block;', $css);
        $this->assertStringContainsString('.mini-cover .book-cover-face', $css);
        $this->assertStringContainsString('.book-list .book-cover-face', $css);
    }

    public function test_card_covers_do_not_permanently_reserve_compositor_layers(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringNotContainsString('will-change: transform', $css);
    }
}
