<?php

namespace Tests\Feature\PublicPortal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDesignRefreshContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_shell_exposes_theme_aware_navigation_and_clean_footer_structure(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('data-theme-toggle', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('site-footer', false)
            ->assertSee('Hubungi kami');
    }

    public function test_home_uses_the_editorial_discovery_layout(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('class="hero-copy"', false)
            ->assertSee('home-discovery-grid', false)
            ->assertSee('Paling banyak dibaca')
            ->assertSee('Rak pilihan')
            ->assertSee('class="statistics-band"', false);
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
