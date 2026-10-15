<?php

declare(strict_types=1);

namespace App\Support;

final class ValidationRules
{
    public const MOBILE = 'regex:/^(\+?88)?01[3-9]\d{8}$/';

    public const MONEY = 'decimal:0,2';
}
