<?php

namespace App\Enums;

enum UserRole: string
{
    /** Full access, including the waiting list itself. */
    case Owner = 'owner';

    /** Website and app content only. Cannot see anyone's email address. */
    case Editor = 'editor';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Editor => 'Content editor',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Owner => 'Can edit content and see the waiting list.',
            self::Editor => 'Can edit content. Cannot see anyone’s email address.',
        };
    }
}
