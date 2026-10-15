<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Message types. Placeholders never include results, diagnoses or
 * patient identity (names, mobile): only a short notice and a link.
 */
enum MessageKey: string
{
    case AppointmentConfirmation = 'appointment_confirmation';
    case AppointmentReminder = 'appointment_reminder';
    case SampleCollected = 'sample_collected';
    case ReportReady = 'report_ready';
    case PaymentReceipt = 'payment_receipt';
    case DueReminder = 'due_reminder';
    case OwnerDailySummary = 'owner_daily_summary';

    /**
     * @return list<string>
     */
    public function placeholders(): array
    {
        return match ($this) {
            self::AppointmentConfirmation, self::AppointmentReminder => ['center', 'doctor', 'date', 'time', 'token', 'branch', 'phone'],
            self::SampleCollected => ['center', 'invoice_no', 'ready_at', 'phone'],
            self::ReportReady => ['center', 'link', 'pin_hint', 'phone'],
            self::PaymentReceipt => ['center', 'invoice_no', 'amount', 'due', 'phone'],
            self::DueReminder => ['center', 'invoice_no', 'due', 'link', 'phone'],
            self::OwnerDailySummary => ['center', 'date', 'patients', 'billed', 'collected', 'dues'],
        };
    }

    /**
     * Default bodies (en, bn) seeded for every tenant.
     *
     * @return array{0: string, 1: string}
     */
    public function defaults(): array
    {
        return match ($this) {
            self::AppointmentConfirmation => [
                '{center}: Appointment with {doctor} on {date} at {time}. Token {token}. Call {phone} to change.',
                '{center}: {doctor} এর সাথে {date} {time} এ অ্যাপয়েন্টমেন্ট। টোকেন {token}। পরিবর্তনে {phone}।',
            ],
            self::AppointmentReminder => [
                '{center}: Reminder, appointment with {doctor} tomorrow at {time}. Token {token}.',
                '{center}: মনে করিয়ে দিচ্ছি, আগামীকাল {time} এ {doctor} এর সাথে অ্যাপয়েন্টমেন্ট। টোকেন {token}।',
            ],
            self::SampleCollected => [
                '{center}: Sample collected for invoice {invoice_no}. Expected report {ready_at}.',
                '{center}: ইনভয়েস {invoice_no} এর নমুনা সংগ্রহ হয়েছে। রিপোর্ট আনুমানিক {ready_at}।',
            ],
            self::ReportReady => [
                '{center}: Your report is ready. View securely: {link} {pin_hint}',
                '{center}: আপনার রিপোর্ট প্রস্তুত। নিরাপদে দেখুন: {link} {pin_hint}',
            ],
            self::PaymentReceipt => [
                '{center}: Received Tk {amount} for invoice {invoice_no}. Due Tk {due}. Thank you.',
                '{center}: ইনভয়েস {invoice_no} এর জন্য {amount} টাকা গ্রহণ করা হয়েছে। বাকি {due} টাকা। ধন্যবাদ।',
            ],
            self::DueReminder => [
                '{center}: Tk {due} is due for invoice {invoice_no}. Pay online: {link}',
                '{center}: ইনভয়েস {invoice_no} এ {due} টাকা বকেয়া। অনলাইনে পরিশোধ: {link}',
            ],
            self::OwnerDailySummary => [
                '{center} {date}: patients {patients}, billed Tk {billed}, collected Tk {collected}, dues Tk {dues}.',
                '{center} {date}: রোগী {patients}, বিল {billed} টাকা, আদায় {collected} টাকা, বকেয়া {dues} টাকা।',
            ],
        };
    }
}
