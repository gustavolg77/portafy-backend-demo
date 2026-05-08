<?php

namespace App\Support;

class ProfileCatalog
{
    public static function socialPlatforms(): array
    {
        return [
            ['value' => 'linkedin', 'label' => 'LinkedIn', 'icon' => 'linkedin', 'color' => '#0A66C2'],
            ['value' => 'github', 'label' => 'GitHub', 'icon' => 'github', 'color' => '#181717'],
            ['value' => 'gitlab', 'label' => 'GitLab', 'icon' => 'gitlab', 'color' => '#FC6D26'],
            ['value' => 'facebook', 'label' => 'Facebook', 'icon' => 'facebook', 'color' => '#1877F2'],
            ['value' => 'instagram', 'label' => 'Instagram', 'icon' => 'instagram', 'color' => '#E4405F'],
            ['value' => 'x', 'label' => 'X', 'icon' => 'twitter', 'color' => '#111111'],
            ['value' => 'youtube', 'label' => 'YouTube', 'icon' => 'youtube', 'color' => '#FF0000'],
            ['value' => 'tiktok', 'label' => 'TikTok', 'icon' => 'music', 'color' => '#111111'],
            ['value' => 'behance', 'label' => 'Behance', 'icon' => 'palette', 'color' => '#1769FF'],
            ['value' => 'dribbble', 'label' => 'Dribbble', 'icon' => 'dribbble', 'color' => '#EA4C89'],
            ['value' => 'medium', 'label' => 'Medium', 'icon' => 'newspaper', 'color' => '#111111'],
            ['value' => 'stackoverflow', 'label' => 'Stack Overflow', 'icon' => 'square-code', 'color' => '#F48024'],
            ['value' => 'portafolio', 'label' => 'Portafolio', 'icon' => 'globe', 'color' => '#2563EB'],
            ['value' => 'otro', 'label' => 'Otro enlace', 'icon' => 'globe', 'color' => '#64748B'],
        ];
    }

    public static function platformMeta(?string $platform): array
    {
        $key = self::normalizePlatform($platform);

        foreach (self::socialPlatforms() as $item) {
            if ($item['value'] === $key) {
                return $item;
            }
        }

        return ['value' => 'otro', 'label' => self::titleFromKey($key), 'icon' => 'globe', 'color' => '#64748B'];
    }

    public static function skillLevels(): array
    {
        return [
            ['value' => 'basico', 'label' => 'Basico', 'dots' => 1, 'number' => 30],
            ['value' => 'intermedio', 'label' => 'Intermedio', 'dots' => 2, 'number' => 60],
            ['value' => 'avanzado', 'label' => 'Avanzado', 'dots' => 3, 'number' => 85],
            ['value' => 'experto', 'label' => 'Experto', 'dots' => 3, 'number' => 100],
        ];
    }

    public static function normalizePlatform(?string $platform): string
    {
        $key = mb_strtolower(trim((string) $platform));

        return match ($key) {
            'portfolio' => 'portafolio',
            'twitter' => 'x',
            'tik tok' => 'tiktok',
            'stack overflow', 'stack-overflow' => 'stackoverflow',
            default => $key !== '' ? $key : 'otro',
        };
    }

    private static function titleFromKey(string $key): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $key !== '' ? $key : 'otro'));
    }
}
