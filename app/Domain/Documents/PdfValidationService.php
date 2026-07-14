<?php

namespace App\Domain\Documents;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use Symfony\Component\Process\Process;

class PdfValidationService
{
    public function probeUpload(UploadedFile $file): PdfProbe
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Upload PDF tidak lengkap atau gagal diterima server.');
        }

        $path = $file->getRealPath();
        $contents = file_get_contents($path);

        if ($contents === false || ! str_starts_with($contents, '%PDF-')) {
            throw new RuntimeException('Berkas tidak memiliki signature PDF yang valid.');
        }

        if (! str_contains(substr($contents, -2048), '%%EOF')) {
            throw new RuntimeException('Struktur akhir PDF tidak ditemukan; berkas mungkin rusak.');
        }

        $structuralPages = preg_match_all('/\/Type\s*\/Page\b/', $contents);
        if ($structuralPages < 1) {
            throw new RuntimeException('PDF tidak memiliki halaman yang dapat dibaca.');
        }

        $externalCount = $this->probeWithPdfInfo($path);

        return new PdfProbe($externalCount ?? $structuralPages);
    }

    public function probePath(string $path): PdfProbe
    {
        if (! is_file($path)) {
            throw new RuntimeException('File PDF privat tidak ditemukan.');
        }

        $count = $this->probeWithPdfInfo($path);
        if ($count !== null) {
            return new PdfProbe($count);
        }

        $contents = file_get_contents($path);
        $pages = $contents === false ? 0 : preg_match_all('/\/Type\s*\/Page\b/', $contents);
        if ($pages < 1) {
            throw new RuntimeException('Jumlah halaman PDF tidak dapat dibaca.');
        }

        return new PdfProbe($pages);
    }

    private function probeWithPdfInfo(string $path): ?int
    {
        $binary = (string) config('pdf.pdfinfo_binary');
        if ($binary === '') {
            return null;
        }

        try {
            $process = new Process([$binary, $path]);
            $process->setTimeout(20);
            $process->run();
            if (! $process->isSuccessful()) {
                return null;
            }
            if (preg_match('/^Pages:\s+(\d+)$/mi', $process->getOutput(), $matches) === 1) {
                return max(1, (int) $matches[1]);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
