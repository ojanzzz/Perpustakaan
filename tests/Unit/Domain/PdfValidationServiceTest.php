<?php

namespace Tests\Unit\Domain;

use App\Domain\Documents\PdfValidationService;
use RuntimeException;
use Tests\TestCase;

class PdfValidationServiceTest extends TestCase
{
    public function test_path_probe_rejects_truncated_pdf_when_pdfinfo_is_unavailable(): void
    {
        config(['pdf.pdfinfo_binary' => '']);
        $path = tempnam(sys_get_temp_dir(), 'truncated-pdf-');
        file_put_contents($path, '%PDF-1.4 /Type /Page truncated');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Struktur akhir PDF tidak ditemukan');

        try {
            app(PdfValidationService::class)->probePath($path);
        } finally {
            @unlink($path);
        }
    }
}
