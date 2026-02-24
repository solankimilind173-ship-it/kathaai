<?php

namespace App\Enums;

enum VideoFormat: string
{
    case InstagramReels = 'instagram_reels';
    case YouTube = 'youtube';

    public function label(): string
    {
        return match ($this) {
            self::InstagramReels => 'Instagram Reels',
            self::YouTube => 'YouTube',
        };
    }

    /** Aspect ratio for encoding (width:height). */
    public function aspectRatio(): string
    {
        return match ($this) {
            self::InstagramReels => '9:16',
            self::YouTube => '16:9',
        };
    }

    /** Typical dimensions for 1080p (width x height). */
    public function dimensions1080p(): array
    {
        return match ($this) {
            self::InstagramReels => [1080, 1920],
            self::YouTube => [1920, 1080],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
