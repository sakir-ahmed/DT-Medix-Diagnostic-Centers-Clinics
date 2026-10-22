<?php

declare(strict_types=1);

namespace App\Enums;

enum LeaveType: string
{
    case Casual = 'casual';
    case Sick = 'sick';
    case Annual = 'annual';
    case Unpaid = 'unpaid';

    public function isPaid(): bool
    {
        return $this !== self::Unpaid;
    }
}
