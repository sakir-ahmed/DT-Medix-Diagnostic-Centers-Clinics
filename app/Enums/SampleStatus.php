<?php

declare(strict_types=1);

namespace App\Enums;

enum SampleStatus: string
{
    case Pending = 'pending';
    case Collected = 'collected';
    case Received = 'received';
    case Rejected = 'rejected';
}
