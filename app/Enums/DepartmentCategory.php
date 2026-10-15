<?php

declare(strict_types=1);

namespace App\Enums;

enum DepartmentCategory: string
{
    case Pathology = 'pathology';
    case Imaging = 'imaging';
    case Cardiology = 'cardiology';
    case Other = 'other';
}
