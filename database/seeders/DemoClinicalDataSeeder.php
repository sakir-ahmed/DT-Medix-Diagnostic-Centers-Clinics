<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Appointments\BookAppointmentAction;
use App\Actions\Billing\CreateOrderAction;
use App\Actions\Lab\AcknowledgeCriticalAction;
use App\Actions\Lab\CollectSampleAction;
use App\Actions\Lab\ReceiveSampleAction;
use App\Actions\Lab\RejectSampleAction;
use App\Actions\Lab\SaveResultsAction;
use App\Actions\Lab\SubmitResultsAction;
use App\Actions\Patients\RegisterPatientAction;
use App\Enums\AppointmentStatus;
use App\Enums\ReferrerType;
use App\Enums\RoleName;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Doctor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\Patient;
use App\Models\Referrer;
use App\Models\ResultAttachment;
use App\Models\Sample;
use App\Models\Test;
use App\Models\TestParameter;
use App\Models\User;
use App\Services\SequenceGenerator;
use App\Services\SlotService;
use App\Support\BranchContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Doctors with schedules, referrers, patients and a realistic two-week
 * flow of orders, payments and lab states for the demo center.
 */
class DemoClinicalDataSeeder extends Seeder
{
    public function run(BranchContext $context, RegisterPatientAction $register): void
    {
        if (Doctor::query()->exists()) {
            return;
        }

        $branches = Branch::query()->orderBy('code')->get();
        $main = $branches->first();

        if ($main === null) {
            return;
        }

        $context->setCurrentBranch($main->id);
        $doctorUser = User::role(RoleName::Doctor->value)->first();

        $this->seedDoctors($branches, $doctorUser);
        $this->seedReferrers();
        $patients = $this->seedPatients($register);

        if (Sample::query()->doesntExist()) {
            $this->seedHistory($main, $patients);
        }
    }

    /**
     * @param  Collection<int, Branch>  $branches
     */
    private function seedDoctors(Collection $branches, ?User $doctorUser): void
    {
        $doctors = [
            ['name' => 'Dr. Ayesha Rahman', 'specialty' => 'Medicine', 'qualifications' => 'MBBS, FCPS (Medicine)', 'bmdc_no' => 'A-23145', 'consultation_fee' => '1000.00', 'follow_up_fee' => '500.00', 'user_id' => $doctorUser?->id],
            ['name' => 'Dr. Kamrul Hasan', 'specialty' => 'Pathology', 'qualifications' => 'MBBS, M.Phil (Pathology)', 'bmdc_no' => 'A-18820', 'consultation_fee' => '0.00', 'follow_up_fee' => '0.00'],
            ['name' => 'Dr. Nusrat Jahan', 'specialty' => 'Gynae and Obs', 'qualifications' => 'MBBS, FCPS (Gynae)', 'bmdc_no' => 'A-30211', 'consultation_fee' => '1200.00', 'follow_up_fee' => '600.00', 'is_visiting' => true],
            ['name' => 'Dr. Shahin Alam', 'specialty' => 'Pediatrics', 'qualifications' => 'MBBS, FCPS (Paediatrics)', 'bmdc_no' => 'A-27634', 'consultation_fee' => '800.00', 'follow_up_fee' => '400.00', 'designation' => 'Consultant'],
            ['name' => 'Dr. Farhana Yeasmin', 'specialty' => 'Endocrinology', 'qualifications' => 'MBBS, MD (Endocrinology)', 'bmdc_no' => 'A-34189', 'consultation_fee' => '1500.00', 'follow_up_fee' => '800.00', 'is_visiting' => true],
        ];

        foreach ($doctors as $data) {
            $doctor = Doctor::query()->create($data + ['slot_minutes' => 15]);

            foreach ($branches as $branch) {
                foreach ([0, 1, 2, 3, 4, 5, 6] as $weekday) {
                    $doctor->schedules()->create(['branch_id' => $branch->id, 'weekday' => $weekday, 'start_time' => '17:00', 'end_time' => '21:00']);
                }
            }
        }
    }

    private function seedReferrers(): void
    {
        foreach ([['Dr. Mahmudul Karim', 'Popular Medical College'], ['Dr. Shirin Akter', 'Labaid Hospital'], ['Rafiq Agency', null]] as $index => [$name, $org]) {
            Referrer::query()->create([
                'type' => $org === null ? ReferrerType::Agent : ReferrerType::Doctor,
                'code' => sprintf('R%03d', $index + 1),
                'name' => $name,
                'organization' => $org,
                'mobile' => sprintf('8801811%06d', $index + 1),
                'payment_method' => 'bkash',
                'payment_account' => sprintf('01811%06d', $index + 1),
            ]);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Patient>
     */
    private function seedPatients(RegisterPatientAction $register): \Illuminate\Support\Collection
    {
        $patients = [
            ['name' => 'Rahim Uddin', 'name_bn' => 'রহিম উদ্দিন', 'sex' => 'male', 'age_years' => 45, 'mobile' => '01711000001', 'blood_group' => 'B+'],
            ['name' => 'Fatema Begum', 'name_bn' => 'ফাতেমা বেগম', 'sex' => 'female', 'age_years' => 38, 'mobile' => '01711000001', 'relation_to_mobile_owner' => 'Wife', 'blood_group' => 'O+'],
            ['name' => 'Arif Hossain', 'sex' => 'male', 'age_years' => 29, 'mobile' => '01711000002', 'allergies' => 'Penicillin', 'blood_group' => 'A+'],
            ['name' => 'Sumaiya Islam', 'sex' => 'female', 'age_years' => 6, 'mobile' => '01711000003', 'relation_to_mobile_owner' => 'Daughter', 'blood_group' => 'B+'],
        ];

        $patients = array_merge($patients, [
            ['name' => 'Abdul Karim', 'name_bn' => 'আব্দুল করিম', 'sex' => 'male', 'age_years' => 62, 'mobile' => '01711000004', 'blood_group' => 'AB+', 'allergies' => 'Sulfa drugs'],
            ['name' => 'Shirin Sultana', 'sex' => 'female', 'age_years' => 51, 'mobile' => '01711000005', 'blood_group' => 'O-'],
            ['name' => 'Md. Jashim Uddin', 'sex' => 'male', 'age_years' => 34, 'mobile' => '01711000006', 'blood_group' => 'A-'],
            ['name' => 'Nasrin Akhter', 'name_bn' => 'নাসরিন আক্তার', 'sex' => 'female', 'age_years' => 27, 'mobile' => '01711000006', 'relation_to_mobile_owner' => 'Wife', 'blood_group' => 'B+'],
            ['name' => 'Kamal Hossain', 'sex' => 'male', 'age_years' => 55, 'mobile' => '01711000007', 'blood_group' => 'O+'],
            ['name' => 'Rokeya Begum', 'sex' => 'female', 'age_years' => 47, 'mobile' => '01711000007', 'relation_to_mobile_owner' => 'Wife', 'blood_group' => 'A+'],
            ['name' => 'Tanvir Ahmed', 'sex' => 'male', 'age_years' => 19, 'mobile' => '01711000008', 'blood_group' => 'B+'],
            ['name' => 'Mizanur Rahman', 'sex' => 'male', 'age_years' => 41, 'mobile' => '01711000009', 'blood_group' => 'AB-'],
            ['name' => 'Salma Khatun', 'sex' => 'female', 'age_years' => 33, 'mobile' => '01711000010', 'blood_group' => 'O+'],
            ['name' => 'Habibur Rahman', 'name_bn' => 'হাবিবুর রহমান', 'sex' => 'male', 'age_years' => 70, 'mobile' => '01711000011', 'blood_group' => 'A+', 'allergies' => 'Iodine contrast'],
            ['name' => 'Ishrat Jahan', 'sex' => 'female', 'age_years' => 24, 'mobile' => '01711000012', 'blood_group' => 'B-'],
            ['name' => 'Aminul Islam', 'sex' => 'male', 'age_years' => 8, 'mobile' => '01711000012', 'relation_to_mobile_owner' => 'Son', 'blood_group' => 'O+'],
        ]);

        $created = collect();

        foreach ($patients as $data) {
            $created->push($register->execute($data, true));
        }

        foreach ($created as $patient) {
            $this->writeAvatar($patient);
        }

        return $created;
    }

    /**
     * Vector initials avatar on the private disk: realistic and light.
     */
    private function writeAvatar(Patient $patient): void
    {
        $initials = mb_strtoupper(mb_substr($patient->name, 0, 1));
        $words = preg_split('/\s+/u', trim($patient->name)) ?: [];
        $initials .= count($words) > 1 ? mb_strtoupper(mb_substr((string) end($words), 0, 1)) : '';

        $palette = [['#0b63ce', '#38a8ff'], ['#0a7d6b', '#37c3a6'], ['#7a4fd0', '#a98ef5'], ['#b3541e', '#e8874d'], ['#155e75', '#38bdf8']];
        [$from, $to] = $palette[$patient->id % count($palette)];

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96" role="img" aria-label="avatar">
  <defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{$from}"/><stop offset="1" stop-color="{$to}"/></linearGradient></defs>
  <rect width="96" height="96" fill="url(#g)"/>
  <circle cx="48" cy="38" r="15" fill="#ffffff" fill-opacity="0.28"/>
  <path d="M20 88c4-16 15-24 28-24s24 8 28 24Z" fill="#ffffff" fill-opacity="0.28"/>
  <text x="48" y="56" text-anchor="middle" font-family="Arial, sans-serif" font-size="30" font-weight="bold" fill="#ffffff">{$initials}</text>
</svg>
SVG;

        $disk = Storage::disk((string) config('center.storage_disk'));
        $path = "patients/{$patient->id}/photo.svg";
        $disk->put($path, $svg);
        $patient->forceFill(['photo_path' => $path])->save();
    }

    /**
     * Two weeks of orders in every lab state, plus today's appointments.
     *
     * @param  \Illuminate\Support\Collection<int, Patient>  $patients
     */
    private function seedHistory(Branch $branch, \Illuminate\Support\Collection $patients): void
    {
        $receptionist = User::role(RoleName::Receptionist->value)->first();
        $collector = User::role(RoleName::SampleCollector->value)->first();
        $technician = User::role(RoleName::LabTechnician->value)->first();
        $pathologist = User::role(RoleName::Pathologist->value)->first();

        $test = fn (string $code): int => (int) Test::query()->where('code', $code)->value('id');
        $package = fn (string $code): int => (int) Package::query()->where('code', $code)->value('id');
        $referrer = fn (int $n): ?int => Referrer::query()->orderBy('id')->pluck('id')->get($n - 1);
        $patient = fn (int $i): Patient => $patients[$i];
        $parameter = fn (string $code): int => (int) TestParameter::query()->where('code', $code)->value('id');
        $entry = fn (string $code, string $value): array => [$parameter($code) => ['value' => $value, 'remarks' => null]];

        $collect = fn (Sample $sample): Sample => app(CollectSampleAction::class)->execute($sample, $collector);
        $receive = fn (Sample $sample): Sample => app(ReceiveSampleAction::class)->execute($sample, $technician);
        $saveResults = function (Sample $sample, array $values) use ($technician): OrderItem {
            $sample->loadMissing('items');

            return app(SaveResultsAction::class)->execute($sample->items->first(), ['results' => $values], $technician);
        };
        $saveResultsItem = fn (OrderItem $item, array $values): OrderItem => app(SaveResultsAction::class)->execute($item, ['results' => $values], $technician);

        // 12 days ago: routine checkup, fully processed.
        $order = app(CreateOrderAction::class)->execute($branch, [
            'patient_id' => $patient(4)->id, 'test_ids' => [$test('CBC'), $test('FBS')], 'referrer_id' => $referrer(1),
            'payments' => [['amount' => '550.00']],
        ], $receptionist);
        $sample = $receive($collect($order->samples->first())->fresh());
        $item = $saveResults($sample, $entry('GLU', '5.2') + $entry('HB', '14.6') + $entry('WBC', '7.8') + $entry('RBC', '5.1') + $entry('PLT', '245') + $entry('PCV', '44') + $entry('ESR', '12'));
        app(SubmitResultsAction::class)->execute($item, $technician);
        $this->backdate($order, 12);

        // 10 days ago: diabetes panel package with a partial payment.
        $order = app(CreateOrderAction::class)->execute($branch, [
            'patient_id' => $patient(0)->id, 'package_ids' => [$package('DIAB')],
            'payments' => [['amount' => '700.00']],
        ], $receptionist);
        $sample = $receive($collect($order->samples->first())->fresh());
        $item = $saveResults($sample, $entry('GLU', '8.4') + $entry('HBA1C', '7.8') + $entry('CREAT', '1.0'));
        app(SubmitResultsAction::class)->execute($item, $technician);
        $this->backdate($order, 10);

        // 8 days ago: USG with a narrative report and an attachment.
        $order = app(CreateOrderAction::class)->execute($branch, [
            'patient_id' => $patient(2)->id, 'test_ids' => [$test('USGWA')], 'referrer_id' => $referrer(3),
            'payments' => [['amount' => '1500.00']],
        ], $receptionist);
        $sample = $receive($collect($order->samples->first())->fresh());
        $sample->loadMissing('items');
        $item = app(SaveResultsAction::class)->execute($sample->items->first(), [
            'findings' => "Liver: normal in size, smooth outline, uniform parenchymal echotexture. No focal lesion.\nGallbladder: well distended, thin wall, no stone. CBD not dilated.\nBoth kidneys: normal size, cortex maintained, no calculus or hydronephrosis.\nUrinary bladder: adequate filling, wall thickness normal.",
            'impression' => 'Ultrasonographic study of whole abdomen is normal.',
        ], $technician);
        $this->writeAttachment($item);
        app(SubmitResultsAction::class)->execute($item, $technician);
        $this->backdate($order, 8);

        // 6 days ago: urgent X-ray plus ECG, still waiting at result entry.
        $order = app(CreateOrderAction::class)->execute($branch, [
            'patient_id' => $patient(10)->id, 'test_ids' => [$test('XRCPA'), $test('ECG')], 'urgency' => 'urgent',
            'payments' => [['amount' => '900.00']],
        ], $receptionist);
        $receive($collect($order->samples->first())->fresh());
        $this->backdate($order, 6);

        // 5 days ago: urine and CBC for the child, partial payment, processed.
        $order = app(CreateOrderAction::class)->execute($branch, [
            'patient_id' => $patient(3)->id, 'test_ids' => [$test('URE'), $test('CBC')],
            'payments' => [['amount' => '200.00']],
        ], $receptionist);

        foreach ($order->samples as $orderSample) {
            $sample = $receive($collect($orderSample)->fresh());
            $sample->loadMissing('items.test');

            $values = $sample->items->first()->code === 'URE'
                ? $entry('COLOR', 'Straw') + $entry('PROT', 'Nil') + $entry('SUGAR', 'Nil') + $entry('PUS', '2')
                : $entry('HB', '12.4') + $entry('WBC', '9.1') + $entry('RBC', '4.8') + $entry('PLT', '310') + $entry('PCV', '38') + $entry('ESR', '18');

            foreach ($sample->items as $sampleItem) {
                app(SubmitResultsAction::class)->execute($saveResultsItem($sampleItem, $values), $technician);
            }
        }

        $this->backdate($order, 5);

        // 3 days ago: thyroid and hepatitis screening, processed.
        $order = app(CreateOrderAction::class)->execute($branch, [
            'patient_id' => $patient(8)->id, 'test_ids' => [$test('TSH'), $test('HBSAG')], 'referrer_id' => $referrer(2),
            'payments' => [['amount' => '1300.00']],
        ], $receptionist);
        $sample = $receive($collect($order->samples->first())->fresh());
        $item = $saveResults($sample, $entry('TSH', '2.6') + $entry('HBSAG', 'Negative'));
        app(SubmitResultsAction::class)->execute($item, $technician);
        $this->backdate($order, 3);

        // Yesterday: lipid profile, rejected and waiting for recollection.
        $order = app(CreateOrderAction::class)->execute($branch, [
            'patient_id' => $patient(9)->id, 'test_ids' => [$test('LIPID')],
            'payments' => [['amount' => '1200.00']],
        ], $receptionist);
        $sample = $collect($order->samples->first());
        app(RejectSampleAction::class)->execute($sample->fresh(), 'Hemolyzed sample', true, $technician);
        $this->backdate($order, 1);

        // Yesterday: collected, on the way to the lab.
        $order = app(CreateOrderAction::class)->execute($branch, [
            'patient_id' => $patient(12)->id, 'test_ids' => [$test('FBS'), $test('CREAT')],
            'payments' => [['amount' => '500.00']],
        ], $receptionist);
        $collect($order->samples->first());
        $this->backdate($order, 1);

        // Today: a critical sugar, acknowledged by phone and submitted.
        $order = app(CreateOrderAction::class)->execute($branch, [
            'patient_id' => $patient(11)->id, 'test_ids' => [$test('FBS')], 'referrer_id' => $referrer(1),
            'payments' => [['amount' => '150.00']],
        ], $receptionist);
        $sample = $receive($collect($order->samples->first())->fresh());
        $item = $saveResults($sample, $entry('GLU', '26.4'));
        app(AcknowledgeCriticalAction::class)->execute($item, 'Dr. Ayesha Rahman, over phone', $pathologist);
        app(SubmitResultsAction::class)->execute($item, $technician);

        // Today: lipid profile waiting at result entry.
        $order = app(CreateOrderAction::class)->execute($branch, [
            'patient_id' => $patient(1)->id, 'test_ids' => [$test('LIPID'), $test('TSH')],
            'payments' => [['amount' => '2000.00']],
        ], $receptionist);
        $receive($collect($order->samples->first())->fresh());

        // Today: one waiting for collection, one imaging sample collected.
        app(CreateOrderAction::class)->execute($branch, [
            'patient_id' => $patient(0)->id, 'test_ids' => [$test('CBC'), $test('FBS')],
            'payments' => [['amount' => '550.00']],
        ], $receptionist);

        $order = app(CreateOrderAction::class)->execute($branch, [
            'patient_id' => $patient(2)->id, 'test_ids' => [$test('USGWA')],
            'payments' => [['amount' => '1500.00']],
        ], $receptionist);
        $collect($order->samples->first());

        $this->seedAppointments($patients, $branch);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Patient>  $patients
     */
    /**
     * Today's clinic: finished and current consultations earlier in the
     * day (written directly, the slot service refuses past times) and
     * booked slots for the evening ahead.
     */
    private function seedAppointments(\Illuminate\Support\Collection $patients, Branch $branch): void
    {
        $receptionist = User::role(RoleName::Receptionist->value)->first();
        $doctors = Doctor::query()->orderBy('id')->get();
        $today = now()->toDateString();
        $sequences = app(SequenceGenerator::class);

        $earlier = [
            ['doctor' => 0, 'time' => '17:00', 'status' => AppointmentStatus::Completed, 'minutes_ago' => 95],
            ['doctor' => 0, 'time' => '17:15', 'status' => AppointmentStatus::InConsultation, 'minutes_ago' => 25],
            ['doctor' => 0, 'time' => '17:30', 'status' => AppointmentStatus::Arrived, 'minutes_ago' => 8],
        ];

        foreach ($patients->take(count($earlier))->values() as $index => $patient) {
            ['doctor' => $doctorIndex, 'time' => $time, 'status' => $status, 'minutes_ago' => $minutesAgo] = $earlier[$index];
            $at = now()->subMinutes($minutesAgo);
            $doctor = $doctors[$doctorIndex];

            Appointment::query()->create([
                'branch_id' => $branch->id,
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'appointment_date' => $today,
                'start_time' => $time,
                'token_no' => $sequences->next("token:{$branch->id}:{$doctor->id}:{$today}"),
                'visit_type' => 'new',
                'status' => $status,
                'source' => 'phone',
                'fee' => $doctor->consultation_fee,
                'booked_by' => $receptionist?->id,
                'arrived_at' => $at,
                'started_at' => in_array($status, [AppointmentStatus::InConsultation, AppointmentStatus::Completed], true) ? $at->copy()->addMinutes(2) : null,
                'completed_at' => $status === AppointmentStatus::Completed ? $at->copy()->addMinutes(15) : null,
            ]);
        }

        $eveningDoctors = [2, 3, 4];
        $slots = app(SlotService::class);

        foreach ($patients->slice(3, count($eveningDoctors))->values() as $index => $patient) {
            $doctor = $doctors[$eveningDoctors[$index]];

            // The next three free slots today, or tomorrow evening when
            // today's session is over.
            foreach ([0, 1] as $dayOffset) {
                $date = now()->startOfDay()->addDays($dayOffset);
                $free = collect($slots->slots($doctor, $branch->id, $date))
                    ->filter(fn (array $slot): bool => $slot['available'])
                    ->pluck('time')
                    ->values();

                if ($free->isNotEmpty()) {
                    app(BookAppointmentAction::class)->execute(
                        $patient,
                        $doctor,
                        $branch->id,
                        $date,
                        (string) $free[$index % $free->count()],
                        'phone',
                        null,
                        $receptionist?->id,
                    );

                    break;
                }
            }
        }
    }

    /**
     * Move an order and its paper trail into the past.
     */
    private function backdate(Order $order, int $days): void
    {
        $at = now()->subDays($days);

        $order->loadMissing('invoice.payments', 'samples');
        $payments = $order->invoice?->payments->all() ?? [];

        foreach ([$order, $order->invoice, ...$payments, ...$order->samples->all()] as $model) {
            if ($model === null) {
                continue;
            }

            $model->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
        }

        $shift = fn ($value) => $value === null ? null : $value->copy()->subDays($days);

        foreach ($order->samples as $sample) {
            $sample->forceFill([
                'collected_at' => $shift($sample->collected_at),
                'received_at' => $shift($sample->received_at),
            ])->save();
        }

        foreach ($order->items as $item) {
            $item->forceFill(['expected_at' => $shift($item->expected_at)])->save();
        }
    }

    /**
     * A grayscale ultrasound-style image attached to the narrative result.
     */
    private function writeAttachment(OrderItem $item): void
    {
        $svg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 240" role="img" aria-label="ultrasound image">
  <rect width="320" height="240" fill="#101418"/>
  <ellipse cx="165" cy="125" rx="120" ry="88" fill="#1d2530"/>
  <ellipse cx="165" cy="125" rx="95" ry="66" fill="#2b3644"/>
  <ellipse cx="150" cy="118" rx="60" ry="42" fill="#3a485a"/>
  <ellipse cx="150" cy="118" rx="38" ry="26" fill="#4d5f76"/>
  <path d="M60 190c40 26 120 30 190 4" stroke="#55647a" stroke-width="10" stroke-linecap="round" fill="none"/>
  <path d="M92 66c30-18 96-18 128 2" stroke="#39485c" stroke-width="8" stroke-linecap="round" fill="none"/>
  <g fill="#7f93ad" font-family="monospace" font-size="11">
    <text x="12" y="20">USG WHOLE ABDOMEN</text>
    <text x="12" y="230">PROBE 3.5 MHz</text>
    <text x="238" y="230">GAIN 68</text>
  </g>
</svg>
SVG;

        $disk = Storage::disk((string) config('center.storage_disk'));
        $path = "results/{$item->id}/usg-scan.svg";
        $disk->put($path, $svg);

        ResultAttachment::query()->create([
            'order_item_id' => $item->id,
            'path' => $path,
            'original_name' => 'usg-whole-abdomen.svg',
            'mime_type' => 'image/svg+xml',
            'size' => strlen($svg),
            'uploaded_by' => null,
        ]);
    }
}
