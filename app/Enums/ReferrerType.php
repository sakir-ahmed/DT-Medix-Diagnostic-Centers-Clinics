<?php

declare(strict_types=1);

namespace App\Enums;

enum ReferrerType: string
{
    case Doctor = 'doctor';
    case Agent = 'agent';
    case Institution = 'institution';
}
