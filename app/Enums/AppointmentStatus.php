<?php

declare(strict_types=1);

namespace App\Enums;

enum AppointmentStatus: string
{
    case Booked = 'booked';
    case Arrived = 'arrived';
    case InConsultation = 'in-consultation';
    case Completed = 'completed';
    case NoShow = 'no-show';
    case Cancelled = 'cancelled';

    /**
     * Allowed next statuses.
     *
     * @return list<self>
     */
    public function next(): array
    {
        return match ($this) {
            self::Booked => [self::Arrived, self::NoShow, self::Cancelled],
            self::Arrived => [self::InConsultation, self::NoShow, self::Cancelled],
            self::InConsultation => [self::Completed],
            self::Completed, self::NoShow, self::Cancelled => [],
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Cancelled, self::NoShow], true);
    }
}
