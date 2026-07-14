<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class RemotePdfDownloader
{
    public function download(string $url, int $timeout = 120): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pdf-dl-').'.pdf';

        if ($this->isGoogleDriveUrl($url)) {
            $this->downloadFromGoogleDrive($url, $tempFile, $timeout);
        } else {
            $this->downloadDirect($url, $tempFile, $timeout);
        }

        if (! is_file($tempFile) || filesize($tempFile) === 0) {
            @unlink($tempFile);
            throw new RuntimeException('Gagal mengunduh PDF dari URL yang diberikan.');
        }

        return $tempFile;
    }

    private function isGoogleDriveUrl(string $url): bool
    {
        return (bool) preg_match('#drive\.google\.com|docs\.google\.com#', $url);
    }

    private function extractGoogleDriveId(string $url): string
    {
        if (preg_match('#/file/d/([a-zA-Z0-9_-]+)#', $url, $m)) {
            return $m[1];
        }
        if (preg_match('#id=([a-zA-Z0-9_-]+)#', $url, $m)) {
            return $m[1];
        }
        if (preg_match('#open\?id=([a-zA-Z0-9_-]+)#', $url, $m)) {
            return $m[1];
        }

        throw new RuntimeException('ID file Google Drive tidak dapat dibaca dari URL.');
    }

    private function downloadFromGoogleDrive(string $url, string $target, int $timeout): void
    {
        $fileId = $this->extractGoogleDriveId($url);
        $confirmUrl = "https://drive.google.com/uc?export=download&id={$fileId}";

        $session = tempnam(sys_get_temp_dir(), 'gdsess-');
        $process = new Process([
            'curl', '-L', '-c', $session, '-o', $target,
            '--max-time', (string) $timeout,
            $confirmUrl,
        ]);
        $process->setTimeout($timeout + 10);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($target)) {
            @unlink($target);
            throw new RuntimeException('Gagal mengakses Google Drive.');
        }

        $contents = file_get_contents($target);
        if ($contents !== false && str_starts_with($contents, '%PDF-')) {
            return;
        }

        $confirmToken = $this->extractGoogleDriveConfirmToken($target, $session);
        if ($confirmToken !== null) {
            @unlink($target);
            $process = new Process([
                'curl', '-L', '-b', "download_warning_{$fileId}={$confirmToken}", '-o', $target,
                '--max-time', (string) $timeout,
                $confirmUrl,
            ]);
            $process->setTimeout($timeout + 10);
            $process->run();
        }

        @unlink($session);
    }

    private function extractGoogleDriveConfirmToken(string $file, string $session): ?string
    {
        $contents = @file_get_contents($file);
        if ($contents === false) {
            return null;
        }

        if (preg_match('/confirm=([0-9A-Za-z_]+)/', $contents, $m)) {
            return $m[1];
        }

        if (preg_match('/download_warning_'.preg_quote(basename($file), '/').'=([0-9A-Za-z_]+)/', $contents, $m)) {
            return $m[1];
        }

        return null;
    }

    private function downloadDirect(string $url, string $target, int $timeout): void
    {
        $process = new Process([
            'curl', '-L', '-o', $target,
            '--max-time', (string) $timeout,
            '-H', 'Accept: application/pdf',
            $url,
        ]);
        $process->setTimeout($timeout + 10);
        $process->run();

        if (! $process->isSuccessful()) {
            @unlink($target);
            throw new RuntimeException('Gagal mengunduh PDF dari URL.');
        }
    }
}
