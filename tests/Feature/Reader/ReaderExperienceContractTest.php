<?php

namespace Tests\Feature\Reader;

use Tests\TestCase;

class ReaderExperienceContractTest extends TestCase
{
    public function test_reader_uses_a_focused_flipbook_workspace_with_floating_toolbar_and_mini_filmstrip(): void
    {
        $view = file_get_contents(resource_path('views/reader/show.blade.php'));
        $javascript = file_get_contents(resource_path('js/reader.js'));
        $css = file_get_contents(resource_path('css/reader.css'));

        $this->assertStringContainsString('reader-control-bar', $view);
        $this->assertStringContainsString('reader-filmstrip', $view);
        $this->assertStringNotContainsString('reader-status-bar', $view);
        $this->assertStringContainsString('reader-accessibility-status', $view);
        $this->assertStringContainsString('reader-zoom-range', $view);
        $this->assertStringContainsString('data-zoom-range', $view);
        $this->assertStringContainsString('data-zoom-range type="range" min="50" max="300" step="5" value="100"', $view);
        $this->assertStringContainsString('data-filmstrip-track', $view);
        $this->assertStringContainsString('data-action="scroll-thumbnails-prev"', $view);
        $this->assertStringContainsString('data-action="scroll-thumbnails-next"', $view);
        $this->assertStringContainsString('data-action="toggle-sidebar" aria-label="Panel dokumen"', $view);
        $this->assertStringContainsString('data-download aria-label="Unduh PDF"', $view);
        $this->assertStringContainsString('data-action="share" aria-label="Bagikan halaman"', $view);
        $this->assertStringContainsString('data-action="previous"', $view);
        $this->assertStringContainsString('data-action="next"', $view);
        $this->assertStringContainsString('id="reader-more-menu"', $view);
        $this->assertSame(1, substr_count($view, 'data-action="fit-page"'));
        $this->assertStringNotContainsString('reader-bottom-dock', $view);
        $this->assertStringNotContainsString('reader-floating-brand', $view);

        $this->assertStringContainsString("const zoomRange = $('[data-zoom-range]')", $javascript);
        $this->assertStringContainsString("zoomRange.addEventListener('input'", $javascript);
        $this->assertStringContainsString("pageInput.addEventListener('keydown'", $javascript);
        $this->assertStringContainsString("event.key === 'Enter'", $javascript);
        $this->assertStringContainsString("case 'scroll-thumbnails-prev'", $javascript);
        $this->assertStringContainsString("case 'scroll-thumbnails-next'", $javascript);
        $this->assertStringContainsString('scrollCurrentThumbnailIntoView', $javascript);
        $this->assertStringContainsString('spreadPreference: null', $javascript);
        $this->assertStringContainsString('bookmarkLabel.textContent =', $javascript);
        $this->assertStringNotContainsString('${bookmark.label', $javascript);
        $this->assertStringContainsString('sidebar.inert = !open', $javascript);
        $this->assertStringContainsString("moreMenu.addEventListener('keydown'", $javascript);
        $this->assertStringContainsString("['ArrowDown', 'ArrowUp', 'Home', 'End']", $javascript);
        $this->assertStringContainsString('const canNext = lastVisiblePage < state.total', $javascript);
        $this->assertStringContainsString('const currentIndex = state.pageFlip.getCurrentPageIndex()', $javascript);
        $this->assertStringNotContainsString('getCurrentPageIndex().then', $javascript);
        $this->assertStringContainsString('if (!state.pdf) return;', $javascript);
        $this->assertStringContainsString('bookEl.style.height = `${pageHeight}px`', $javascript);
        $this->assertStringContainsString('let flipRenderGeneration = 0', $javascript);
        $this->assertStringContainsString('const renderGeneration = ++flipRenderGeneration', $javascript);
        $this->assertStringContainsString('if (renderGeneration !== flipRenderGeneration) return;', $javascript);
        $this->assertMatchesRegularExpression('/for \(let i = 1; i <= state\.total; i\+\+\) \{\s*if \(renderGeneration !== flipRenderGeneration\) return;/s', $javascript);
        $this->assertMatchesRegularExpression('/if \(state\.mode === \'scroll\'\) \{\s*flipRenderGeneration \+= 1;\s*state\.pageFlip\?\.destroy\(\);/s', $javascript);
        $this->assertMatchesRegularExpression('/window\.addEventListener\(\'resize\'.*if \(state\.mode === \'flip\'\) \{\s*state\.pageFlip\?\.destroy\(\);\s*state\.pageFlip = null;\s*\}\s*showMode\(true\);/s', $javascript);
        $this->assertStringContainsString("const controlBar = $('.reader-control-bar')", $javascript);
        $this->assertStringContainsString('reader-controls-hidden', $javascript);
        $this->assertStringContainsString("root.addEventListener('pointermove'", $javascript);
        $this->assertStringContainsString("controlBar.addEventListener('focusin'", $javascript);

        $this->assertStringContainsString('.reader-control-bar', $css);
        $this->assertStringContainsString('.reader-filmstrip', $css);
        $this->assertStringNotContainsString('.reader-status-bar', $css);
        $this->assertStringContainsString('.reader-zoom-range', $css);
        $this->assertStringContainsString('.filmstrip-thumbnail.is-current', $css);
        $this->assertMatchesRegularExpression('/\.reader-control-bar[^}]*position:\s*fixed/s', $css);
        $this->assertMatchesRegularExpression('/\.reader-control-bar[^}]*top:\s*max\(10px/s', $css);
        $this->assertMatchesRegularExpression('/\.reader-stage[^}]*position:\s*relative[^}]*grid-row:\s*1\s*\/\s*-1/s', $css);
        $this->assertMatchesRegularExpression('/\.reader-filmstrip[^}]*position:\s*fixed[^}]*bottom:\s*max\(8px/s', $css);
        $this->assertMatchesRegularExpression('/\.reader-filmstrip[^}]*height:\s*44px/s', $css);
        $this->assertMatchesRegularExpression('/\.reader-stage[^}]*background:\s*#303030/s', $css);
        $this->assertStringContainsString('@media (max-width: 767px)', $css);
        $this->assertStringContainsString('.fit-width-active .flip-viewport', $css);
        $this->assertStringContainsString('.reader-app.reader-controls-hidden .reader-control-bar', $css);
        $this->assertMatchesRegularExpression('/\.flip-viewport \.stf__wrapper,\s*\.flip-viewport \.stf__block\s*\{[^}]*overflow:\s*hidden;[^}]*clip-path:\s*inset\(0\);/s', $css);
        $this->assertMatchesRegularExpression('/\.flip-viewport \.flip-page,\s*\.flip-viewport \.stf__item\s*\{[^}]*background:\s*#fff\s*!important;[^}]*opacity:\s*1\s*!important;/s', $css);
        $this->assertMatchesRegularExpression('/\.flip-viewport \.flip-page img\s*\{[^}]*width:\s*100%;[^}]*height:\s*100%;[^}]*object-fit:\s*fill;/s', $css);
    }
}
