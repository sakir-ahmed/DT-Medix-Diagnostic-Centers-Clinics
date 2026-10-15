<?php

declare(strict_types=1);

namespace App\Enums;

enum ResultType: string
{
    case Number = 'number';
    case Text = 'text';
    case Choice = 'choice';
    case Calculated = 'calculated';
}
