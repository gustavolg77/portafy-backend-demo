<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use Throwable;

class CloudinaryService
{
    private ?object $client = null;

    private function client(): object
    {
        if ($this->client) {
            return $this->client;
        }

        if (! class_exists(\Cloudinary\Cloudinary::class)) {
            throw new RuntimeException('Cloudinary no esta instalado. Ejecuta composer install antes de subir imagenes.');
        }

        $cloudUrl = config('cloudinary.cloud_url');

        if (! is_string($cloudUrl) || trim($cloudUrl) === '' || str_contains($cloudUrl, '://:@')) {
            throw new RuntimeException('Cloudinary no esta configurado correctamente.');
        }

        $this->client = new \Cloudinary\Cloudinary($cloudUrl);

        return $this->client;
    }

    public function upload(UploadedFile $file, string $folder): string
    {
        $result = $this->client()->uploadApi()->upload($file->getRealPath(), [
            'folder'         => $folder,
            'resource_type'  => 'image',
            'transformation' => [['quality' => 'auto', 'fetch_format' => 'auto']],
            'verify'         => $this->sslVerification(),
        ]);

        return $result['secure_url'];
    }

    public function delete(string $url): void
    {
        if (! $url || ! str_contains($url, 'cloudinary.com')) {
            return;
        }

        preg_match('/upload\/(?:v\d+\/)?(.+)\.\w+$/', $url, $matches);

        if (empty($matches[1])) {
            return;
        }

        try {
            $this->client()->uploadApi()->destroy($matches[1]);
        } catch (Throwable) {
            // Replacing the image must not fail just because old cleanup failed.
        }
    }

    private function sslVerification(): bool|string
    {
        $verify = config('cloudinary.verify_ssl', ! app()->isLocal());

        if (is_bool($verify)) {
            return $verify;
        }

        if (is_string($verify)) {
            $normalized = strtolower(trim($verify));

            if (in_array($normalized, ['false', '0', 'no', 'off'], true)) {
                return false;
            }

            if (in_array($normalized, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }

            if ($normalized !== '') {
                return $verify;
            }
        }

        return ! app()->isLocal();
    }
}
