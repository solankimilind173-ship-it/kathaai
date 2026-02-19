<?php

namespace App\Enums;

enum SourceType: string
{
    case Library = 'library';
    case Uploaded = 'uploaded';

    public function label(): string
    {
        return match ($this) {
            self::Library => 'Library',
            self::Uploaded => 'Uploaded',
        };
    }
}
