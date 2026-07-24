<?php

namespace Tests\Feature\PublicPortal;

use App\Enums\BookStatus;
use App\Enums\BookVisibility;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDesignRefreshContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_shell_uses_publication_focused_navigation_and_compact_footer(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('site-footer-compact', false)
            ->assertSee('images/logo.png', false)
            ->assertSee('>Publikasi<', false)
            ->assertDontSee('>Katalog<', false)
            ->assertDontSee('>Rak<', false)
            ->assertDontSee('Hubungi kami');
    }

    public function test_home_shows_eight_publications_per_paginated_grid_without_legacy_discovery_sections(): void
    {
        Book::factory()->count(9)->sequence(fn ($sequence) => [
            'title' => 'Publikasi '.($sequence->index + 1),
            'status' => BookStatus::Published,
            'visibility' => BookVisibility::Public,
            'published_at' => now()->subMinutes($sequence->index + 1),
        ])->create();

        $firstPage = $this->get('/');
        $firstPage
            ->assertOk()
            ->assertSee('id="publikasi"', false)
            ->assertSee('publication-grid', false)
            ->assertDontSee('data-publication-slideshow', false)
            ->assertSee('Publikasi')
            ->assertDontSee('hero-art', false)
            ->assertDontSee('Jelajahi katalog')
            ->assertDontSee('Kategori utama')
            ->assertDontSee('Paling banyak dibaca')
            ->assertDontSee('Rak pilihan')
            ->assertDontSee('statistics-band', false)
            ->assertDontSee('announcement-band', false);

        $this->assertSame(8, substr_count($firstPage->getContent(), 'class="book-card group"'));

        $secondPage = $this->get('/?page=2');
        $secondPage->assertOk()->assertSee('Publikasi 9');
        $this->assertSame(1, substr_count($secondPage->getContent(), 'class="book-card group"'));
    }

    public function test_publication_grid_has_responsive_columns_without_slideshow_runtime(): void
    {
        $javascript = file_get_contents(resource_path('js/app.js'));
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringNotContainsString("'[data-publication-slideshow]'", $javascript);
        $this->assertStringContainsString('.publication-grid', $css);
        $this->assertStringContainsString('grid-template-columns: repeat(4, minmax(0, 1fr))', $css);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr))', $css);
    }

    public function test_public_styles_define_semantic_light_and_dark_tokens(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('--surface-page: #ffffff', $css);
        $this->assertStringContainsString('--surface-raised: #ffffff', $css);
        $this->assertStringContainsString('--text-primary: #0f2747', $css);
        $this->assertStringContainsString('html[data-theme="dark"]', $css);
        $this->assertStringContainsString('--surface-page: #08111f', $css);
        $this->assertStringContainsString('--surface-raised: #0f1b2d', $css);
        $this->assertStringContainsString('color-scheme: dark', $css);
    }
}
