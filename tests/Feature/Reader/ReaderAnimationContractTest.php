<?php

namespace Tests\Feature\Reader;

use Tests\TestCase;

class ReaderAnimationContractTest extends TestCase
{
    public function test_flip_reader_uses_page_flip_animation_with_cleanup_and_reduced_motion_support(): void
    {
        $javascript = file_get_contents(resource_path('js/reader.js'));
        $css = file_get_contents(resource_path('css/reader.css'));

        $this->assertStringContainsString("import { PageFlip } from 'page-flip'", $javascript);
        $this->assertStringContainsString('state.pageFlip = new PageFlip', $javascript);
        $this->assertStringContainsString('flippingTime: state.reduced ? 1 : 800', $javascript);
        $this->assertStringContainsString('drawShadow: true', $javascript);
        $this->assertStringContainsString('showPageCorners: false', $javascript);
        $this->assertStringContainsString('const pageImages = []', $javascript);
        $this->assertStringContainsString('pageImages.push(canvas.toDataURL', $javascript);
        $this->assertStringContainsString('state.pageFlip.loadFromImages(pageImages)', $javascript);
        $this->assertStringNotContainsString('state.pageFlip.loadFromHTML', $javascript);
        $this->assertStringContainsString('usePortrait: !state.spread', $javascript);
        $this->assertStringContainsString("state.pageFlip.on('flip'", $javascript);
        $this->assertStringContainsString('state.pageFlip.destroy()', $javascript);
        $this->assertStringContainsString('const currentIndex = state.pageFlip.getCurrentPageIndex()', $javascript);
        $this->assertStringContainsString("if (state.mode === 'flip') await initFlipBook()", $javascript);
        $this->assertStringContainsString('--reader-paper-shadow:', $css);
        $this->assertStringContainsString('.flip-viewport .flip-page', $css);
        $this->assertStringContainsString('.flip-viewport .stf__canvas', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('.reduce-motion *', $css);
    }
}
