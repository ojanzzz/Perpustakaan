<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\Process;

class RemotePdfDownloader
{
    private const MAX_REDIRECTS = 5;

    public function download(string $url, int $timeout = 120): string
    {
        $target = tempnam(sys_get_temp_dir(), 'pdf-dl-');
        $cookieFile = tempnam(sys_get_temp_dir(), 'pdf-cookie-');

        if ($target === false || $cookieFile === false) {
            throw new RuntimeException('Penyimpanan sementara tidak tersedia.');
        }

        try {
            $downloadUrl = $this->isGoogleDriveUrl($url)
                ? $this->googleDriveDownloadUrl($url)
                : $url;

            $this->downloadFollowingSafeRedirects($downloadUrl, $target, $cookieFile, $timeout);

            if ($this->isGoogleDriveUrl($url) && ! $this->hasPdfSignature($target)) {
                $token = $this->extractGoogleDriveConfirmToken($target);
                if ($token !== null) {
                    $separator = str_contains($downloadUrl, '?') ? '&' : '?';
                    $this->downloadFollowingSafeRedirects(
                        $downloadUrl.$separator.'confirm='.rawurlencode($token),
                        $target,
                        $cookieFile,
                        $timeout,
                    );
                }
            }

            if (! $this->hasPdfSignature($target)) {
                throw new RuntimeException('URL tidak menghasilkan file PDF yang valid.');
            }

            return $target;
        } catch (\Throwable $exception) {
            @unlink($target);
            throw $exception;
        } finally {
            @unlink($cookieFile);
        }
    }

    private function downloadFollowingSafeRedirects(
        string $url,
        string $target,
        string $cookieFile,
        int $timeout,
    ): void {
        $maximumBytes = max(1, (int) config('pdf.max_upload_kb', 102400)) * 1024;
        $currentUrl = $url;

        for ($redirects = 0; $redirects <= self::MAX_REDIRECTS; $redirects++) {
            [$host, $port, $ip] = $this->safeEndpoint($currentUrl);
            $headerFile = tempnam(sys_get_temp_dir(), 'pdf-header-');

            if ($headerFile === false) {
                throw new RuntimeException('Penyimpanan sementara tidak tersedia.');
            }

            try {
                $process = new Process([
                    'curl', '--silent', '--show-error', '--fail',
                    '--dump-header', $headerFile,
                    '--output', $target,
                    '--max-time', (string) $timeout,
                    '--max-filesize', (string) $maximumBytes,
                    '--proto', '=http,https',
                    '--resolve', "{$host}:{$port}:{$ip}",
                    '--cookie', $cookieFile,
                    '--cookie-jar', $cookieFile,
                    '--header', 'Accept: application/pdf,application/octet-stream;q=0.9',
                    $currentUrl,
                ]);
                $process->setTimeout($timeout + 10);
                $process->run();

                if (! $process->isSuccessful()) {
                    $message = str_contains(strtolower($process->getErrorOutput()), 'maximum file size')
                        ? 'Ukuran PDF dari URL melebihi batas yang diizinkan.'
                        : 'Gagal mengunduh PDF dari URL.';
                    throw new RuntimeException($message);
                }

                if (is_file($target) && filesize($target) > $maximumBytes) {
                    throw new RuntimeException('Ukuran PDF dari URL melebihi batas yang diizinkan.');
                }

                $headers = (string) file_get_contents($headerFile);
                $location = $this->redirectLocation($headers);
                if ($location === null) {
                    return;
                }

                $currentUrl = $this->resolveRedirectUrl($currentUrl, $location);
            } finally {
                @unlink($headerFile);
            }
        }

        throw new RuntimeException('URL PDF memiliki terlalu banyak pengalihan.');
    }

    /** @return array{string, int, string} */
    private function safeEndpoint(string $url): array
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            throw new RuntimeException('URL PDF harus menggunakan protokol HTTP atau HTTPS.');
        }

        $port = isset($parts['port']) ? (int) $parts['port'] : ($scheme === 'https' ? 443 : 80);
        if ($port < 1 || $port > 65535) {
            throw new RuntimeException('Port URL PDF tidak valid.');
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP)
            ? [$host]
            : (gethostbynamel($host) ?: []);

        foreach ($addresses as $address) {
            if (filter_var(
                $address,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
            )) {
                return [$host, $port, $address];
            }
        }

        throw new RuntimeException('URL PDF tidak boleh mengarah ke jaringan privat atau alamat yang tidak valid.');
    }

    private function redirectLocation(string $headers): ?string
    {
        if (! preg_match('/^HTTP\/\S+\s+(3\d{2})/mi', $headers, $status)
            || ! preg_match('/^Location:\s*(.+)$/mi', $headers, $location)) {
            return null;
        }

        return trim($location[1]);
    }

    private function resolveRedirectUrl(string $baseUrl, string $location): string
    {
        if (filter_var($location, FILTER_VALIDATE_URL)) {
            return $location;
        }

        $base = parse_url($baseUrl);
        $scheme = (string) ($base['scheme'] ?? '');
        $host = (string) ($base['host'] ?? '');
        $port = isset($base['port']) ? ':'.$base['port'] : '';

        if (str_starts_with($location, '//')) {
            return $scheme.':'.$location;
        }

        if (str_starts_with($location, '/')) {
            return "{$scheme}://{$host}{$port}{$location}";
        }

        $path = (string) ($base['path'] ?? '/');
        $directory = rtrim(str_replace('\\', '/', dirname($path)), '/');

        return "{$scheme}://{$host}{$port}{$directory}/{$location}";
    }

    private function isGoogleDriveUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return $host === 'drive.google.com' || $host === 'docs.google.com';
    }

    private function googleDriveDownloadUrl(string $url): string
    {
        if (preg_match('#/file/d/([a-zA-Z0-9_-]+)#', $url, $match)
            || preg_match('/[?&]id=([a-zA-Z0-9_-]+)/', $url, $match)) {
            return 'https://drive.google.com/uc?export=download&id='.$match[1];
        }

        throw new RuntimeException('ID file Google Drive tidak dapat dibaca dari URL.');
    }

    private function extractGoogleDriveConfirmToken(string $file): ?string
    {
        $contents = @file_get_contents($file);
        if ($contents === false) {
            return null;
        }

        return preg_match('/confirm=([0-9A-Za-z_-]+)/', $contents, $match) ? $match[1] : null;
    }

    private function hasPdfSignature(string $file): bool
    {
        if (! is_file($file) || filesize($file) < 5) {
            return false;
        }

        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return false;
        }

        $signature = fread($handle, 5);
        fclose($handle);

        return $signature === '%PDF-';
    }
}
