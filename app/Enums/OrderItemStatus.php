<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderItemStatus: string
{
    case Ordered = 'ordered';
    case Collected = 'collected';
    case Received = 'received';
    case Entered = 'entered';
    case Verified = 'verified';
    case Released = 'released';
    case Cancelled = 'cancelled';

    public function isCollected(): bool
    {
        return ! in_array($this, [self::Ordered, self::Cancelled], true);
    }
}
