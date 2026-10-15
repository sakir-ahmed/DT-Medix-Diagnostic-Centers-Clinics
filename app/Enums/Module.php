<?php

declare(strict_types=1);

namespace App\Enums;

enum Module: string
{
    case Patients = 'patients';
    case Appointments = 'appointments';
    case Orders = 'orders';
    case Lab = 'lab';
    case Reports = 'reports';
    case Billing = 'billing';
    case Dues = 'dues';
    case Sms = 'sms';
    case Commission = 'commission';
    case Referrals = 'referrals';
    case ReportTemplates = 'report_templates';
    case WhatsApp = 'whatsapp';
    case PatientPortal = 'patient_portal';
    case DueReminders = 'due_reminders';
    case Api = 'api';
    case Branding = 'branding';
    case Dashboard = 'dashboard';
    case Expenses = 'expenses';
    case HomeCollection = 'home_collection';
    case SampleTransfer = 'sample_transfer';
    case Import = 'import';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $module): string => $module->value, self::cases());
    }
}
