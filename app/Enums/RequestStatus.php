<?php

namespace App\Enums;

enum RequestStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Done = 'done';

    public function next(): ?self
    {
        return match ($this) {
            self::New => self::InProgress,
            self::InProgress => self::Done,
            self::Done => null,
        };
    }
}
