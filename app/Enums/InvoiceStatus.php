<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Due = 'due';
    case Partial = 'partial';
    case Paid = 'paid';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
