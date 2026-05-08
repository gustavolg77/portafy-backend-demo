<?php

namespace Tests\Unit;

use App\Services\PublicAssetUrlService;
use Tests\TestCase;

class PublicAssetUrlServiceTest extends TestCase
{
    public function test_it_returns_empty_string_for_missing_path(): void
    {
        $service = app(PublicAssetUrlService::class);

        $this->assertSame('', $service->fromStoragePath(null));
    }

    public function test_it_keeps_absolute_urls_untouched(): void
    {
        $service = app(PublicAssetUrlService::class);
        $url = 'https://cdn.example.com/avatar.png';

        $this->assertSame($url, $service->fromStoragePath($url));
    }

    public function test_it_builds_public_storage_url_from_relative_path(): void
    {
        config()->set('app.url', 'http://api.test');

        $service = app(PublicAssetUrlService::class);

        $this->assertSame(
            'http://api.test/storage/fotos/avatar.png',
            $service->fromStoragePath('public/fotos/avatar.png')
        );
    }
}
