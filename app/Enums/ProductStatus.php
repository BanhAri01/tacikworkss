<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Draft = 'draft';
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'In review',
            default => ucfirst($this->value),
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
            self::Pending => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
            self::Approved => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
            self::Rejected => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
        };
    }
}
