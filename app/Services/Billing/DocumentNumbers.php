<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Models\Branch;
use App\Services\SequenceGenerator;

/**
 * Invoice, money receipt and sample numbers. Gapless, generated under a row
 * lock inside the caller's transaction.
 */
final class DocumentNumbers
{
    public function __construct(private readonly SequenceGenerator $sequences) {}

    /**
     * INV-BR01-26-000123: per branch and year.
     */
    public function invoice(Branch $branch): string
    {
        $year = now()->format('y');

        return sprintf('INV-%s-%s-%06d', $branch->code, $year, $this->sequences->next("invoice:{$branch->id}:{$year}"));
    }

    /**
     * MR-BR01-26-000045: per branch and year.
     */
    public function receipt(Branch $branch): string
    {
        $year = now()->format('y');

        return sprintf('MR-%s-%s-%06d', $branch->code, $year, $this->sequences->next("receipt:{$branch->id}:{$year}"));
    }

    /**
     * BR01-260115-0042: per branch per day.
     */
    public function sample(Branch $branch): string
    {
        $day = now()->format('ymd');

        return sprintf('%s-%s-%04d', $branch->code, $day, $this->sequences->next("sample:{$branch->id}:{$day}"));
    }
}
