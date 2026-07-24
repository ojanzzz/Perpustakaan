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
        $this->assertStringContainsString('const pageElements = []', $javascript);
        $this->assertStringContainsString("pageImage.src = canvas.toDataURL('image/png')", $javascript);
        $this->assertStringContainsString('state.pageFlip.loadFromHTML(pageElements)', $javascript);
        $this->assertStringNotContainsString('state.pageFlip.loadFromImages', $javascript);
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

    public function test_portrait_flip_keeps_an_opaque_paper_beneath_backward_animation(): void
    {
        $javascript = file_get_contents(resource_path('js/reader.js'));
        $css = file_get_contents(resource_path('css/reader.css'));

        $this->assertStringContainsString(
            'bookEl.style.setProperty(\'--flip-paper-width\', `${state.spread ? pageWidth * 2 : pageWidth}px`)',
            $javascript,
        );
        $this->assertMatchesRegularExpression(
            '/\.flip-viewport \.flip-book::before\s*\{[^}]*z-index:\s*0[^}]*background:\s*#fff[^}]*width:\s*var\(--flip-paper-width\)/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\.flip-viewport \.stf__wrapper,\s*\.flip-viewport \.stf__block\s*\{[^}]*z-index:\s*1/s',
            $css,
        );
    }

    public function test_small_mobile_toolbar_keeps_back_navigation_visible(): void
    {
        $css = file_get_contents(resource_path('css/reader.css'));

        $this->assertStringNotContainsString('.reader-control-primary > a { display: none; }', $css);
        $this->assertMatchesRegularExpression(
            '/@media \(max-width:\s*430px\).*?\.reader-zoom-controls\s*\{\s*display:\s*none;\s*\}.*?\.reader-control-primary > a\s*\{\s*display:\s*inline-flex;\s*\}/s',
            $css,
        );
    }

    public function test_page_flip_is_the_only_mobile_swipe_owner(): void
    {
        $javascript = file_get_contents(resource_path('js/reader.js'));

        $this->assertStringNotContainsString("stage.addEventListener('touchstart'", $javascript);
        $this->assertStringNotContainsString("stage.addEventListener('touchend'", $javascript);
        $this->assertStringContainsString('state.pageFlip.loadFromHTML(pageElements)', $javascript);
    }

    public function test_page_surface_owns_click_to_zoom_without_icon_buttons(): void
    {
        $view = file_get_contents(resource_path('views/reader/show.blade.php'));
        $javascript = file_get_contents(resource_path('js/reader.js'));
        $css = file_get_contents(resource_path('css/reader.css'));

        $this->assertDoesNotMatchRegularExpression(
            '/reader-zoom-controls(?:(?!<\/div>).)*data-action="zoom-(?:in|out)"/s',
            $view,
        );
        $this->assertStringContainsString('data-zoom-range', $view);
        $this->assertStringContainsString('disableFlipByClick: true', $javascript);
        $this->assertStringContainsString("size: state.zoom > 1 ? 'fixed' : 'stretch'", $javascript);
        $this->assertStringContainsString('useMouseEvents: state.zoom <= 1', $javascript);
        $this->assertStringContainsString('state.pageFlip.getUI().removeHandlers()', $javascript);
        $this->assertStringContainsString('function togglePageZoom()', $javascript);
        $this->assertStringContainsString("flipViewport.addEventListener('pointerdown'", $javascript);
        $this->assertStringContainsString("flipViewport.addEventListener('pointerup'", $javascript);
        $this->assertStringContainsString("root.classList.toggle('page-zoomed', state.zoom > 1)", $javascript);
        $this->assertStringContainsString('maxWidth: Math.max(200, stageWidth, pageWidth)', $javascript);
        $this->assertStringContainsString('maxHeight: Math.max(stageHeight, pageHeight)', $javascript);
        $this->assertMatchesRegularExpression('/\.flip-viewport \.flip-page[^}]*cursor:\s*zoom-in/s', $css);
        $this->assertMatchesRegularExpression('/\.page-zoomed \.flip-viewport[^}]*overflow:\s*auto/s', $css);
        $this->assertMatchesRegularExpression('/\.page-zoomed \.stf__parent[^}]*touch-action:\s*auto/s', $css);
        $this->assertMatchesRegularExpression('/\.page-zoomed \.flip-page[^}]*cursor:\s*zoom-out/s', $css);
    }
}
