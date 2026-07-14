<?php

namespace Tests\Feature\Reader;

use Tests\TestCase;

class ReaderAnimationContractTest extends TestCase
{
    public function test_flip_reader_has_directional_page_sheet_animation_and_cleanup(): void
    {
        $javascript = file_get_contents(resource_path('js/reader.js'));
        $css = file_get_contents(resource_path('css/reader.css'));

        $this->assertStringContainsString('page-turn-overlay', $javascript);
        $this->assertStringContainsString('animationend', $javascript);
        $this->assertStringContainsString('flipRenderToken', $javascript);
        $this->assertStringContainsString('pageTurnState', $javascript);
        $this->assertStringContainsString('.page-turn-overlay.turn-forward', $css);
        $this->assertStringContainsString('.page-turn-overlay.turn-backward', $css);
        $this->assertStringContainsString('@keyframes turnSheetForward', $css);
        $this->assertStringContainsString('@keyframes turnSheetBackward', $css);
        $this->assertStringContainsString('.reduce-motion .page-turn-overlay', $css);
        $this->assertStringContainsString('.flip-pages.is-single::before', $css);
        $this->assertStringContainsString('perspective: 900px', $css);
        $this->assertStringContainsString('rotateY(-40deg)', $css);
        $this->assertStringContainsString('#fbfae8', $css);
    }
}
