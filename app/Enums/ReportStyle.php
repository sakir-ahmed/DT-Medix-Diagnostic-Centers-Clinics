<?php

declare(strict_types=1);

namespace App\Enums;

enum ReportStyle: string
{
    case Table = 'table';
    case Narrative = 'narrative';
}
