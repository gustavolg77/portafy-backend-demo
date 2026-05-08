<?php

namespace App\Support;

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;

class CloudinaryUploader
{
    public static function upload($file, string $folder = 'general'): string
    {
        $config = new Configuration();
        $config->cloud->cloudName = config('cloudinary.cloud_name');
        $config->cloud->apiKey    = config('cloudinary.api_key');
        $config->cloud->apiSecret = config('cloudinary.api_secret');
        $config->url->secure      = true;

        $cloudinary = new Cloudinary($config);

        $result = $cloudinary->uploadApi()->upload($file->getRealPath(), [
            'folder' => $folder,
        ]);

        return $result['secure_url'];
    }
}
