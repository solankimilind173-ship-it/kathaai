<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Generating = 'generating';
    case Ready = 'ready';
    case Rendering = 'rendering';
    case Completed = 'completed';
    case Failed = 'failed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Generating => 'Generating',
            self::Ready => 'Ready',
            self::Rendering => 'Rendering',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Archived => 'Archived',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
