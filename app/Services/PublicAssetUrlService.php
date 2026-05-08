<?php

namespace App\Services;

class PublicAssetUrlService
{
    public function fromStoragePath(?string $path): string
    {
        if (! $path) {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        $baseUrl = app()->runningInConsole()
            ? rtrim((string) config('app.url'), '/')
            : rtrim(request()->getSchemeAndHttpHost() ?: (string) config('app.url'), '/');

        $normalizedPath = ltrim((string) preg_replace('#^(public/|storage/)#', '', $path), '/');

        return $baseUrl.'/storage/'.$normalizedPath;
    }
}
