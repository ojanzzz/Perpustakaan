<?php

namespace Tests\Unit\Services;

use App\Services\RemotePdfDownloader;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class RemotePdfDownloaderTest extends TestCase
{
    #[DataProvider('unsafeUrls')]
    public function test_it_rejects_non_http_and_private_network_urls(string $url): void
    {
        $this->expectException(RuntimeException::class);

        app(RemotePdfDownloader::class)->download($url);
    }

    public static function unsafeUrls(): array
    {
        return [
            'file scheme' => ['file:///etc/passwd'],
            'loopback ipv4' => ['http://127.0.0.1/internal.pdf'],
            'private ipv4' => ['http://10.0.0.1/internal.pdf'],
            'cloud metadata' => ['http://169.254.169.254/latest/meta-data'],
            'localhost host' => ['http://localhost/internal.pdf'],
        ];
    }
}
