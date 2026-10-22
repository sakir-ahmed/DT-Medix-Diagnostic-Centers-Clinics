<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Appointments\BookAppointmentAction;
use App\Actions\Billing\CancelItemsAction;
use App\Actions\Billing\CollectPaymentAction;
use App\Actions\Billing\CreateOrderAction;
use App\Actions\Billing\DecideDiscountAction;
use App\Actions\Billing\DecideRefundAction;
use App\Actions\Billing\RequestRefundAction;
use App\Actions\Catalog\SaveCatalogItemAction;
use App\Actions\Catalog\SaveTestAction;
use App\Actions\Doctors\ManageScheduleAction;
use App\Actions\Doctors\SaveDoctorAction;
use App\Actions\Hr\DecideLeaveAction;
use App\Actions\Hr\FinalizePayrollRunAction;
use App\Actions\Hr\GeneratePayrollRunAction;
use App\Actions\Hr\IngestAttendancePunchesAction;
use App\Actions\Hr\RequestLeaveAction;
use App\Actions\Hr\SaveEmployeeAction;
use App\Actions\Lab\AcknowledgeCriticalAction;
use App\Actions\Lab\CollectSampleAction;
use App\Actions\Lab\ReceiveSampleAction;
use App\Actions\Lab\RejectSampleAction;
use App\Actions\Lab\SaveResultsAction;
use App\Actions\Lab\SubmitResultsAction;
use App\Actions\Patients\RegisterPatientAction;
use App\Enums\AppointmentStatus;
use App\Enums\ApprovalStatus;
use App\Enums\ReferrerType;
use App\Enums\ResultType;
use App\Enums\RoleName;
use App\Exceptions\DomainException;
use App\Models\Appointment;
use App\Models\AttendanceDevice;
use App\Models\Branch;
use App\Models\BranchTestPrice;
use App\Models\Department;
use App\Models\DiscountRequest;
use App\Models\Doctor;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Package;
use App\Models\Patient;
use App\Models\PatientAccessLog;
use App\Models\PayrollRun;
use App\Models\Referrer;
use App\Models\ReportTemplate;
use App\Models\Test;
use App\Models\TestParameter;
use App\Models\User;
use App\Models\WorkShift;
use App\Services\SequenceGenerator;
use App\Services\SlotService;
use App\Support\BranchContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Fills every module with a believable volume of demo data: a bigger
 * catalog, referrers and doctors, about a hundred patients, two months of
 * orders in every lab state, dues, refunds, discount requests, appointments
 * and an access log. Everything goes through the real actions.
 */
class DemoActivitySeeder extends Seeder
{
    private const MALE = ['Abdullah', 'Rahim', 'Karim', 'Jamal', 'Faruk', 'Imran', 'Sajid', 'Nahid', 'Tareq', 'Mahfuz', 'Rubel', 'Shakil', 'Anwar', 'Delwar', 'Monir', 'Sabbir', 'Rakib', 'Hasan', 'Zahid', 'Nayeem', 'Sumon', 'Ashraf', 'Babul', 'Liton'];

    private const FEMALE = ['Ayesha', 'Fatima', 'Rina', 'Nargis', 'Sabina', 'Shamima', 'Taslima', 'Laboni', 'Mitu', 'Popy', 'Rumana', 'Sharmin', 'Jesmin', 'Khadija', 'Nipa', 'Parvin', 'Runa', 'Shila', 'Tahmina', 'Mahmuda', 'Afroza', 'Dilruba', 'Eti', 'Farzana'];

    private const SURNAMES = ['Ahmed', 'Hossain', 'Rahman', 'Islam', 'Khan', 'Chowdhury', 'Sarker', 'Mia', 'Uddin', 'Akter', 'Begum', 'Khatun', 'Sultana', 'Biswas', 'Das', 'Talukder', 'Mondal', 'Sheikh', 'Bhuiyan', 'Miah'];

    private ?User $receptionist = null;

    private ?User $collector = null;

    private ?User $technician = null;

    private ?User $pathologist = null;

    public function run(BranchContext $context): void
    {
        if (blank(config('center.demo_password')) || Test::query()->where('code', 'SGPT')->exists()) {
            return;
        }

        $branches = Branch::query()->orderBy('code')->get();
        $main = $branches->first();

        if ($main === null) {
            return;
        }

        mt_srand(2026);
        $context->setCurrentBranch($main->id);

        $this->receptionist = User::role(RoleName::Receptionist->value)->first();
        $this->collector = User::role(RoleName::SampleCollector->value)->first();
        $this->technician = User::role(RoleName::LabTechnician->value)->first();
        $this->pathologist = User::role(RoleName::Pathologist->value)->first();

        $this->seedCatalog();
        $this->seedReferrers();
        $this->seedDoctors($branches);
        $patients = $this->seedPatients();
        $this->seedBranchPrices($branches);

        $orders = $this->seedOrders($main, $patients, 130, 62);
        $second = $branches->get(1);

        if ($second !== null) {
            $context->setCurrentBranch($second->id);
            $this->seedOrders($second, $patients, 28, 40);
            $context->setCurrentBranch($main->id);
        }

        $this->seedDues($orders);
        $this->seedRefunds($main, $patients);
        $this->seedDiscountRequests($main, $patients);
        $this->seedAppointments($main, $patients);
        $this->seedAccessLog($patients);
        $this->seedHrExtras($branches);
    }

    private function seedCatalog(): void
    {
        $tests = app(SaveTestAction::class);
        $catalog = app(SaveCatalogItemAction::class);
        $dept = fn (string $code): int => (int) Department::query()->where('code', $code)->value('id');
        $usg = ReportTemplate::query()->where('name', 'Ultrasonography')->value('id');
        $any = fn (?string $low, ?string $high, ?string $cl = null, ?string $ch = null): array => ['sex' => 'any', 'age_min' => 0, 'age_max' => null, 'age_unit' => 'years', 'low' => $low, 'high' => $high, 'critical_low' => $cl, 'critical_high' => $ch];
        $p = fn (string $code, string $name, ?string $unit, array $ranges = [], string $type = 'number', array $extra = []): array => ['code' => $code, 'name' => $name, 'unit' => $unit, 'result_type' => $type, 'ranges' => $ranges] + $extra;
        $neg = fn (string $code, string $name, array $choices, array $abnormal): array => $p($code, $name, null, [], 'choice', ['choices' => $choices, 'abnormal_values' => $abnormal]);

        $definitions = [
            ['SGPT', 'SGPT (ALT)', 'BIO', 'serum', 'gel', '400.00', 4, null, [$p('ALT', 'ALT', 'U/L', [$any(null, '41')])]],
            ['SGOT', 'SGOT (AST)', 'BIO', 'serum', 'gel', '400.00', 4, null, [$p('AST', 'AST', 'U/L', [$any(null, '40')])]],
            ['BILT', 'Serum Bilirubin (Total)', 'BIO', 'serum', 'gel', '350.00', 4, null, [$p('BIL', 'Total bilirubin', 'mg/dL', [$any('0.2', '1.2', null, '15')])]],
            ['UA', 'Serum Uric Acid', 'BIO', 'serum', 'gel', '400.00', 4, null, [$p('URIC', 'Uric acid', 'mg/dL', [$any('2.6', '7.2')])]],
            ['UREA', 'Serum Urea', 'BIO', 'serum', 'gel', '350.00', 4, null, [$p('UREA', 'Urea', 'mg/dL', [$any('15', '45')])]],
            ['CA', 'Serum Calcium', 'BIO', 'serum', 'gel', '500.00', 6, null, [$p('CA', 'Calcium', 'mg/dL', [$any('8.6', '10.2', '6', '13')])]],
            ['ELEC', 'Serum Electrolytes', 'BIO', 'serum', 'gel', '900.00', 6, null, [
                $p('NA', 'Sodium', 'mmol/L', [$any('135', '145', '120', '160')]),
                $p('K', 'Potassium', 'mmol/L', [$any('3.5', '5.1', '2.5', '6.5')]),
                $p('CL', 'Chloride', 'mmol/L', [$any('98', '107')]),
            ]],
            ['VITD', 'Vitamin D (25-OH)', 'BIO', 'serum', 'gel', '2800.00', 24, null, [$p('VITD', '25-OH Vitamin D', 'ng/mL', [$any('30', '100')])]],
            ['T3', 'T3 (Total)', 'BIO', 'serum', 'gel', '700.00', 24, null, [$p('T3', 'T3', 'ng/dL', [$any('80', '200')])]],
            ['T4', 'T4 (Total)', 'BIO', 'serum', 'gel', '700.00', 24, null, [$p('T4', 'T4', 'ug/dL', [$any('5.1', '14.1')])]],
            ['PSA', 'PSA (Total)', 'BIO', 'serum', 'gel', '1500.00', 24, null, [$p('PSA', 'PSA', 'ng/mL', [$any(null, '4')])]],
            ['CRP', 'C-Reactive Protein (CRP)', 'SER', 'serum', 'gel', '700.00', 6, null, [$p('CRP', 'CRP', 'mg/L', [$any(null, '6')])]],
            ['WIDAL', 'Widal Test', 'SER', 'serum', 'plain', '400.00', 6, null, [
                $neg('TO', 'Salmonella typhi O', ['Negative', '1:80', '1:160', '1:320'], ['1:160', '1:320']),
                $neg('TH', 'Salmonella typhi H', ['Negative', '1:80', '1:160', '1:320'], ['1:160', '1:320']),
            ]],
            ['DENGNS1', 'Dengue NS1 Antigen', 'SER', 'serum', 'plain', '1200.00', 4, null, [$neg('NS1', 'Dengue NS1', ['Negative', 'Positive'], ['Positive'])]],
            ['MP', 'Malarial Parasite (MP)', 'HEM', 'blood', 'edta', '500.00', 4, null, [$neg('MP', 'Malarial parasite', ['Not seen', 'Seen'], ['Seen'])]],
            ['BG', 'Blood Group and Rh', 'HEM', 'blood', 'edta', '250.00', 2, null, [$neg('BG', 'Blood group', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'], [])]],
            ['UCS', 'Urine Culture and Sensitivity', 'MIC', 'urine', 'urine_cup', '1200.00', 48, 'Mid-stream clean-catch sample', [$neg('ORG', 'Organism', ['No growth', 'E. coli', 'Klebsiella', 'Staphylococcus'], ['E. coli', 'Klebsiella', 'Staphylococcus'])]],
            ['XRKUB', 'X-ray KUB', 'IMG', 'none', 'none', '600.00', 6, null, []],
            ['USGKUB', 'USG of KUB', 'IMG', 'none', 'none', '1000.00', 4, 'Full bladder', []],
            ['ECHO', 'Echocardiography', 'CAR', 'none', 'none', '2500.00', 6, null, []],
        ];

        $order = (int) Test::query()->max('sort_order');

        foreach ($definitions as $index => [$code, $name, $department, $sample, $container, $price, $tat, $prep, $parameters]) {
            $tests->execute([
                'code' => $code, 'name' => $name, 'department_id' => $dept($department), 'sample_type' => $sample, 'container' => $container,
                'price' => $price, 'tat_hours' => $tat, 'preparation' => $prep, 'home_collection' => $sample !== 'none',
                'report_template_id' => in_array($code, ['USGKUB'], true) ? $usg : null, 'sort_order' => $order + $index + 1, 'parameters' => $parameters,
            ]);
        }

        $ids = fn (array $codes): array => Test::query()->whereIn('code', $codes)->pluck('id')->all();
        $catalog->package(['code' => 'LIVER', 'name' => 'Liver Function Panel', 'price' => '1000.00', 'test_ids' => $ids(['SGPT', 'SGOT', 'BILT'])]);
        $catalog->package(['code' => 'FEVER', 'name' => 'Fever Panel', 'price' => '2400.00', 'test_ids' => $ids(['CBC', 'WIDAL', 'MP', 'DENGNS1', 'CRP'])]);
        $catalog->package(['code' => 'WOMEN', 'name' => 'Women Health Checkup', 'price' => '3500.00', 'test_ids' => $ids(['CBC', 'TSH', 'VITD', 'CA', 'FBS'])]);
        $catalog->package(['code' => 'CARDIAC', 'name' => 'Cardiac Screening', 'price' => '4000.00', 'test_ids' => $ids(['ECG', 'LIPID', 'FBS', 'ECHO'])]);
        $catalog->package(['code' => 'FULL', 'name' => 'Full Body Checkup', 'price' => '5500.00', 'test_ids' => $ids(['CBC', 'FBS', 'LIPID', 'CREAT', 'UA', 'SGPT', 'TSH', 'URE', 'VITD'])]);
    }

    private function seedReferrers(): void
    {
        $people = [
            ['Dr. Rezaul Karim', 'Square Hospital', 'Cardiology'], ['Dr. Tahmina Akter', 'United Hospital', 'Gynae'], ['Dr. Anisur Rahman', 'Evercare Hospital', 'Medicine'],
            ['Dr. Mahbuba Sultana', 'Ibn Sina Hospital', 'Pediatrics'], ['Dr. Sohel Mahmud', 'Central Hospital', 'Surgery'], ['Dr. Kaniz Fatema', 'Green Life Hospital', 'Medicine'],
        ];

        foreach ($people as $index => [$name, $organization, $specialty]) {
            Referrer::query()->create([
                'type' => ReferrerType::Doctor, 'code' => sprintf('R%03d', $index + 4), 'name' => $name, 'organization' => $organization, 'specialty' => $specialty,
                'mobile' => sprintf('8801812%06d', $index + 1), 'payment_method' => $index % 2 === 0 ? 'bkash' : 'bank', 'payment_account' => sprintf('01812%06d', $index + 1),
                'bank_name' => $index % 2 === 0 ? null : 'Dutch-Bangla Bank',
            ]);
        }

        foreach ([['Healthcare Agency', ReferrerType::Agent], ['City Clinic', ReferrerType::Institution], ['Mamun Traders (agent)', ReferrerType::Agent]] as $index => [$name, $type]) {
            Referrer::query()->create(['type' => $type, 'code' => sprintf('R%03d', $index + 10), 'name' => $name, 'mobile' => sprintf('8801913%06d', $index + 1), 'payment_method' => 'cash']);
        }
    }

    /**
     * @param  Collection<int, Branch>  $branches
     */
    private function seedDoctors(Collection $branches): void
    {
        $save = app(SaveDoctorAction::class);
        $schedule = app(ManageScheduleAction::class);

        $doctors = [
            ['name' => 'Dr. Imran Hossain', 'specialty' => 'Cardiology', 'qualifications' => 'MBBS, MD (Cardiology)', 'bmdc_no' => 'A-41022', 'consultation_fee' => '1500.00', 'follow_up_fee' => '800.00', 'days' => [0, 2, 4]],
            ['name' => 'Dr. Tahmina Chowdhury', 'specialty' => 'Dermatology', 'qualifications' => 'MBBS, DDV', 'bmdc_no' => 'A-38877', 'consultation_fee' => '900.00', 'follow_up_fee' => '450.00', 'days' => [1, 3, 5]],
            ['name' => 'Dr. Rezaul Islam', 'specialty' => 'Orthopedics', 'qualifications' => 'MBBS, MS (Ortho)', 'bmdc_no' => 'A-35412', 'consultation_fee' => '1100.00', 'follow_up_fee' => '600.00', 'days' => [0, 1, 6], 'is_visiting' => true],
        ];

        foreach ($doctors as $data) {
            $days = $data['days'];
            unset($data['days']);
            $doctor = $save->execute($data + ['slot_minutes' => 15]);

            foreach ($branches->take(1) as $branch) {
                foreach ($days as $weekday) {
                    $schedule->addSchedule($doctor, ['branch_id' => $branch->id, 'weekday' => $weekday, 'start_time' => '16:00', 'end_time' => '20:00']);
                }
            }
        }

        $all = Doctor::query()->orderBy('id')->get();
        $all[0]->leaves()->create(['start_date' => now()->addDays(9)->toDateString(), 'end_date' => now()->addDays(12)->toDateString(), 'reason' => 'Medical conference']);
        $all[2]->leaves()->create(['start_date' => now()->addDays(4)->toDateString(), 'end_date' => now()->addDays(4)->toDateString(), 'reason' => 'Personal leave']);
        $all[3]->leaves()->create(['start_date' => now()->subDays(20)->toDateString(), 'end_date' => now()->subDays(18)->toDateString(), 'reason' => 'Family event']);
    }

    /**
     * @return Collection<int, Patient>
     */
    private function seedPatients(): Collection
    {
        $register = app(RegisterPatientAction::class);
        $created = Patient::query()->orderBy('id')->get();
        $groups = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
        $mobile = 1000;
        $familyMobile = null;

        for ($i = 0; $i < 96; $i++) {
            $female = mt_rand(0, 1) === 1;
            $first = $female ? self::FEMALE[array_rand(self::FEMALE)] : self::MALE[array_rand(self::MALE)];
            $name = $first.' '.self::SURNAMES[array_rand(self::SURNAMES)];
            $age = match (true) {
                $i % 9 === 0 => mt_rand(1, 12),
                $i % 5 === 0 => mt_rand(60, 82),
                default => mt_rand(18, 58),
            };

            // About one in six registrations is a family member on a shared mobile.
            $shared = $familyMobile !== null && $i % 6 === 1;
            $number = $shared ? $familyMobile : sprintf('01%d%08d', mt_rand(3, 9), 20000000 + $mobile++ * 7);

            if (! $shared && $i % 6 === 0) {
                $familyMobile = $number;
            }

            $created->push($register->execute(array_filter([
                'name' => $name,
                'sex' => $female ? 'female' : 'male',
                'age_years' => $age,
                'mobile' => $number,
                'blood_group' => $groups[array_rand($groups)],
                'relation_to_mobile_owner' => $shared ? ['Wife', 'Son', 'Daughter', 'Father', 'Mother'][array_rand(['Wife', 'Son', 'Daughter', 'Father', 'Mother'])] : null,
                'allergies' => $i % 17 === 0 ? 'Penicillin' : null,
                'address' => $i % 3 === 0 ? 'Mirpur, Dhaka' : ($i % 3 === 1 ? 'Dhanmondi, Dhaka' : 'Uttara, Dhaka'),
            ]), true));
        }

        return $created;
    }

    /**
     * @param  Collection<int, Branch>  $branches
     */
    private function seedBranchPrices(Collection $branches): void
    {
        $second = $branches->get(1);

        if ($second === null) {
            return;
        }

        foreach (Test::query()->whereIn('code', ['CBC', 'FBS', 'URE', 'SGPT', 'CREAT', 'CRP'])->get() as $test) {
            BranchTestPrice::query()->create(['branch_id' => $second->id, 'test_id' => $test->id, 'price' => (string) round((float) $test->price * 0.9, 2)]);
        }
    }

    /**
     * @param  Collection<int, Patient>  $patients
     * @return Collection<int, Order>
     */
    private function seedOrders(Branch $branch, Collection $patients, int $count, int $days): Collection
    {
        $testIds = Test::query()->where('is_active', true)->pluck('id', 'code');
        $packageIds = Package::query()->pluck('id')->all();
        $referrerIds = Referrer::query()->pluck('id')->all();
        $orders = collect();

        for ($n = 0; $n < $count; $n++) {
            $age = (int) floor($days * (mt_rand(0, 100) / 100) ** 1.6);
            $patient = $patients[mt_rand(0, $patients->count() - 1)];

            $data = ['patient_id' => $patient->id];

            if (mt_rand(1, 100) <= 16) {
                $data['package_ids'] = [$packageIds[array_rand($packageIds)]];
            } else {
                $pick = $testIds->keys()->shuffle()->take(mt_rand(1, 3))->all();
                $data['test_ids'] = array_map(fn (string $code): int => (int) $testIds[$code], $pick);
            }

            if (mt_rand(1, 100) <= 35) {
                $data['referrer_id'] = $referrerIds[array_rand($referrerIds)];
            }

            if (mt_rand(1, 100) <= 8) {
                $data['urgency'] = 'urgent';
            }

            if (mt_rand(1, 100) <= 7) {
                $data['discount_type'] = 'percent';
                $data['discount_value'] = (string) [5, 10][mt_rand(0, 1)];
                $data['discount_category'] = 'standard';
            }

            try {
                $order = app(CreateOrderAction::class)->execute($branch, $data, $this->receptionist);
            } catch (DomainException) {
                continue;
            }

            $invoice = $order->invoice()->first();
            $total = (string) ($invoice?->due ?? '0');
            $roll = mt_rand(1, 100);
            $payment = match (true) {
                $roll <= 66 => $total,
                $roll <= 86 => number_format((float) $total * (mt_rand(35, 70) / 100), 2, '.', ''),
                default => null,
            };

            if ($payment !== null && (float) $payment > 0 && $invoice !== null) {
                try {
                    app(CollectPaymentAction::class)->execute($invoice, [['amount' => $payment]], $this->receptionist?->id);
                } catch (DomainException) {
                    // Discount waiting for approval: leave the invoice unpaid.
                }
            }

            $this->advance($order->fresh(['samples.items.test']), $age);

            if ($age > 0) {
                $this->backdate($order->fresh(['invoice.payments', 'samples', 'items']), $age);
            }

            $orders->push($order);
        }

        return $orders;
    }

    /**
     * Moves the samples of an order through the lab; older orders are
     * finished, recent ones stop at earlier steps.
     */
    private function advance(Order $order, int $age): void
    {
        $roll = mt_rand(1, 100);

        $stage = match (true) {
            $age >= 3 => $roll <= 93 ? 'done' : ($roll <= 97 ? 'rejected' : 'received'),
            $age >= 1 => $roll <= 55 ? 'done' : ($roll <= 75 ? 'received' : ($roll <= 90 ? 'collected' : 'none')),
            default => $roll <= 30 ? 'done' : ($roll <= 55 ? 'received' : ($roll <= 80 ? 'collected' : 'none')),
        };

        foreach ($order->samples as $sample) {
            if ($stage === 'none') {
                break;
            }

            $sample = app(CollectSampleAction::class)->execute($sample, $this->collector)->fresh(['items.test.parameters.ranges', 'items.order.patient']);

            if ($stage === 'collected') {
                continue;
            }

            if ($stage === 'rejected') {
                app(RejectSampleAction::class)->execute($sample, ['Hemolyzed sample', 'Insufficient volume', 'Wrong container'][mt_rand(0, 2)], true, $this->technician);

                continue;
            }

            $sample = app(ReceiveSampleAction::class)->execute($sample, $this->technician)->fresh(['items.test.parameters.ranges', 'items.order.patient']);

            if ($stage === 'received') {
                continue;
            }

            foreach ($sample->items as $item) {
                $this->enterResults($item);
            }
        }
    }

    private function enterResults(OrderItem $item): void
    {
        $item->loadMissing('test.parameters.ranges');
        $parameters = $item->test->parameters->where('is_active', true);

        if ($parameters->isEmpty()) {
            $text = $this->narrative((string) $item->code);
            $saved = app(SaveResultsAction::class)->execute($item, $text, $this->technician);
        } else {
            $values = [];

            foreach ($parameters as $parameter) {
                $values[$parameter->id] = ['value' => $this->valueFor($parameter), 'remarks' => null];
            }

            $saved = app(SaveResultsAction::class)->execute($item, ['results' => $values], $this->technician);
        }

        try {
            app(SubmitResultsAction::class)->execute($saved, $this->technician);
        } catch (DomainException) {
            app(AcknowledgeCriticalAction::class)->execute($saved, 'Informed the referring doctor by phone', $this->pathologist);
            app(SubmitResultsAction::class)->execute($saved, $this->technician);
        }
    }

    private function valueFor(TestParameter $parameter): ?string
    {
        if ($parameter->result_type === ResultType::Calculated) {
            return null;
        }

        if ($parameter->result_type === ResultType::Text) {
            return ['Straw', 'Pale yellow', 'Clear'][mt_rand(0, 2)];
        }

        if ($parameter->result_type === ResultType::Choice) {
            $choices = array_values((array) $parameter->choices);
            $abnormal = array_values((array) $parameter->abnormal_values);
            $normal = array_values(array_diff($choices, $abnormal));

            if ($abnormal !== [] && mt_rand(1, 100) <= 9) {
                return $abnormal[array_rand($abnormal)];
            }

            return $normal !== [] ? $normal[array_rand($normal)] : $choices[0];
        }

        $range = $parameter->ranges->first(fn ($r): bool => $r->low !== null || $r->high !== null);
        $low = $range?->low !== null ? (float) $range->low : null;
        $high = $range?->high !== null ? (float) $range->high : null;
        $roll = mt_rand(1, 1000);

        if ($low === null && $high === null) {
            $value = mt_rand(100, 1000) / 10;
        } elseif ($roll <= 15 && $range?->critical_high !== null) {
            $value = (float) $range->critical_high * 1.1;
        } elseif ($roll <= 110) {
            $value = $high !== null ? $high * (1.15 + mt_rand(0, 40) / 100) : $low * 0.7;
        } elseif ($roll <= 150 && $low !== null) {
            $value = $low * 0.75;
        } elseif ($low !== null && $high !== null) {
            $value = $low + ($high - $low) * (mt_rand(8, 92) / 100);
        } elseif ($high !== null) {
            $value = $high * (mt_rand(40, 90) / 100);
        } else {
            $value = $low * (1.1 + mt_rand(0, 50) / 100);
        }

        return number_format($value, $value >= 100 ? 0 : 1, '.', '');
    }

    /**
     * @return array{findings: string, impression: string}
     */
    private function narrative(string $code): array
    {
        return match ($code) {
            'XRCPA' => ['findings' => 'Both lung fields are clear. Cardiac size and contour are normal. Costophrenic angles are sharp. No bony abnormality.', 'impression' => 'Normal chest X-ray.'],
            'XRKUB' => ['findings' => 'No radio-opaque calculus along the urinary tract. Bowel gas pattern is normal.', 'impression' => 'No abnormality detected on plain KUB.'],
            'ECG' => ['findings' => 'Sinus rhythm, rate 76 per minute. Normal axis. PR and QRS intervals are normal. No ST-T change.', 'impression' => 'Normal ECG.'],
            'ECHO' => ['findings' => 'Normal chamber dimensions. LVEF 62 percent. No regional wall motion abnormality. Valves are normal. No pericardial effusion.', 'impression' => 'Normal echocardiogram with good LV systolic function.'],
            'USGKUB' => ['findings' => "Both kidneys are normal in size and echotexture. No calculus or hydronephrosis.\nUrinary bladder is well distended with normal wall thickness.", 'impression' => 'Normal ultrasonography of KUB.'],
            default => ['findings' => 'Liver is normal in size with uniform echotexture. Gallbladder and CBD are normal. Both kidneys are normal.', 'impression' => 'Ultrasonographic study is normal.'],
        };
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function seedDues(Collection $orders): void
    {
        $partial = $orders->filter(function (Order $order): bool {
            $invoice = $order->invoice()->first();

            return $invoice !== null && (float) $invoice->due > 0 && $order->created_at->lt(now()->subDays(4));
        })->values();

        // A third of old dues are collected later, the rest stay as aging dues.
        foreach ($partial->filter(fn ($order, $index): bool => $index % 3 === 0) as $order) {
            $invoice = $order->invoice()->first();
            app(CollectPaymentAction::class)->execute($invoice, [['amount' => (string) $invoice->due]], $this->receptionist?->id);
            $invoice->payments()->latest('id')->first()?->forceFill(['created_at' => $order->created_at->copy()->addDays(mt_rand(1, 3))])->save();
        }
    }

    /**
     * @param  Collection<int, Patient>  $patients
     */
    private function seedRefunds(Branch $branch, Collection $patients): void
    {
        $tests = fn (array $codes): array => Test::query()->whereIn('code', $codes)->pluck('id')->all();
        $decisions = ['approve', 'approve', 'pending', 'pending', 'reject'];

        foreach ($decisions as $index => $decision) {
            $ids = $tests($index % 2 === 0 ? ['CBC', 'UA'] : ['FBS', 'SGPT']);
            $order = app(CreateOrderAction::class)->execute($branch, [
                'patient_id' => $patients[$index + 20]->id, 'test_ids' => $ids,
                'payments' => [['amount' => number_format((float) Test::query()->whereIn('id', $ids)->sum('price'), 2, '.', '')]],
            ], $this->receptionist);

            $first = $order->items()->first();

            try {
                app(CancelItemsAction::class)->execute($order, [$first->id], 'Patient changed mind', null, null, $this->receptionist?->id);
                $invoice = Invoice::query()->where('order_id', $order->id)->firstOrFail();
                $excess = max(0, (float) $invoice->paid - (float) $invoice->total);

                if ($excess <= 0) {
                    continue;
                }

                $refund = app(RequestRefundAction::class)->execute($invoice, number_format($excess, 2, '.', ''), 'Test cancelled before sample collection', $this->receptionist?->id);

                if ($decision !== 'pending') {
                    app(DecideRefundAction::class)->execute($refund, $decision === 'approve', 'demo.manager', '1234');
                }
            } catch (DomainException) {
                continue;
            }
        }
    }

    /**
     * @param  Collection<int, Patient>  $patients
     */
    private function seedDiscountRequests(Branch $branch, Collection $patients): void
    {
        $tests = Test::query()->whereIn('code', ['LIPID', 'HBA1C', 'VITD', 'ECHO'])->pluck('id')->all();
        $decisions = ['pending', 'pending', 'pending', 'approve', 'approve', 'approve', 'reject'];

        foreach ($decisions as $index => $decision) {
            try {
                $order = app(CreateOrderAction::class)->execute($branch, [
                    'patient_id' => $patients[30 + $index]->id, 'test_ids' => [$tests[$index % count($tests)]],
                    'discount_type' => 'percent', 'discount_value' => (string) [20, 25, 30, 15, 20, 50, 40][$index],
                    'discount_category' => $index === 5 ? 'charity' : 'standard',
                    'discount_reason' => $index === 5 ? 'Charity case, referred by local mosque committee' : 'Regular patient, requested by the family',
                ], $this->receptionist);
            } catch (DomainException) {
                continue;
            }

            $request = DiscountRequest::query()->where('invoice_id', $order->invoice?->id)->where('status', ApprovalStatus::Pending)->first();

            if ($request !== null && $decision !== 'pending') {
                try {
                    app(DecideDiscountAction::class)->execute($request, $decision === 'approve', 'demo.manager', '1234');
                } catch (DomainException) {
                    continue;
                }
            }
        }

        unset($request);
    }

    /**
     * @param  Collection<int, Patient>  $patients
     */
    private function seedAppointments(Branch $branch, Collection $patients): void
    {
        $doctors = Doctor::query()->where('consultation_fee', '>', 0)->orderBy('id')->get();
        $sequences = app(SequenceGenerator::class);
        $sources = ['phone', 'walk-in', 'walk-in', 'online'];

        // A month of finished clinics.
        for ($day = 30; $day >= 1; $day--) {
            $date = now()->subDays($day)->startOfDay();

            foreach ($doctors as $doctor) {
                if (! $doctor->schedules()->withoutGlobalScope('branch')->where('weekday', $date->dayOfWeek)->exists()) {
                    continue;
                }

                $slots = mt_rand(2, 6);

                for ($slot = 0; $slot < $slots; $slot++) {
                    $patient = $patients[mt_rand(0, $patients->count() - 1)];
                    $time = Carbon::parse('17:00')->addMinutes($slot * 15)->format('H:i');
                    $roll = mt_rand(1, 100);
                    $status = $roll <= 82 ? AppointmentStatus::Completed : ($roll <= 91 ? AppointmentStatus::NoShow : AppointmentStatus::Cancelled);
                    $at = $date->copy()->setTimeFromTimeString($time);

                    Appointment::query()->create([
                        'branch_id' => $branch->id,
                        'doctor_id' => $doctor->id,
                        'patient_id' => $patient->id,
                        'appointment_date' => $date->toDateString(),
                        'start_time' => $time,
                        'token_no' => $sequences->next("token:{$branch->id}:{$doctor->id}:{$date->toDateString()}"),
                        'visit_type' => mt_rand(1, 100) <= 25 ? 'follow_up' : 'new',
                        'status' => $status,
                        'source' => $sources[array_rand($sources)],
                        'fee' => $doctor->consultation_fee,
                        'cancel_reason' => $status === AppointmentStatus::Cancelled ? 'Patient could not come' : null,
                        'booked_by' => $this->receptionist?->id,
                        'arrived_at' => $status === AppointmentStatus::Completed ? $at->copy()->subMinutes(10) : null,
                        'started_at' => $status === AppointmentStatus::Completed ? $at : null,
                        'completed_at' => $status === AppointmentStatus::Completed ? $at->copy()->addMinutes(14) : null,
                        'created_at' => $at->copy()->subDay(),
                        'updated_at' => $at,
                    ]);
                }
            }
        }

        // The next week is booked through the real slot service.
        $slots = app(SlotService::class);

        for ($ahead = 1; $ahead <= 7; $ahead++) {
            $date = now()->startOfDay()->addDays($ahead);

            foreach ($doctors as $doctor) {
                $free = collect($slots->slots($doctor, $branch->id, $date))->filter(fn (array $slot): bool => $slot['available'])->pluck('time')->values();

                foreach ($free->shuffle()->take(mt_rand(1, 4)) as $time) {
                    try {
                        app(BookAppointmentAction::class)->execute($patients[mt_rand(0, $patients->count() - 1)], $doctor, $branch->id, $date, (string) $time, $sources[array_rand($sources)], null, $this->receptionist?->id);
                    } catch (DomainException) {
                        continue;
                    }
                }
            }
        }
    }

    /**
     * @param  Collection<int, Patient>  $patients
     */
    private function seedAccessLog(Collection $patients): void
    {
        $users = User::query()->where('username', 'like', 'demo.%')->pluck('id');
        $actions = ['view', 'view', 'view', 'history', 'report', 'photo'];

        for ($i = 0; $i < 90; $i++) {
            $at = now()->subMinutes(mt_rand(5, 60 * 24 * 14));

            $log = PatientAccessLog::query()->create([
                'user_id' => $users[mt_rand(0, $users->count() - 1)],
                'patient_id' => $patients[mt_rand(0, $patients->count() - 1)]->id,
                'action' => $actions[array_rand($actions)],
                'channel' => 'staff',
                'ip_address' => '10.0.'.mt_rand(0, 3).'.'.mt_rand(10, 200),
                'user_agent' => 'Mozilla/5.0 (demo)',
            ]);

            $log->forceFill(['created_at' => $at])->save();
        }
    }

    /**
     * More staff, a second device, mixed leave and the national holidays.
     *
     * @param  Collection<int, Branch>  $branches
     */
    private function seedHrExtras(Collection $branches): void
    {
        $this->seedMoreEmployees($branches);

        foreach ([['2026-12-16', 'Victory Day'], ['2026-12-25', 'Christmas Day'], ['2027-02-21', 'International Mother Language Day'], ['2027-03-26', 'Independence Day'], ['2027-04-14', 'Pohela Boishakh'], ['2027-05-01', 'May Day']] as [$date, $name]) {
            Holiday::query()->firstOrCreate(['holiday_on' => $date], ['name' => $name]);
        }

        $employees = Employee::query()->withoutGlobalScopes()->orderBy('id')->get();
        $leave = app(RequestLeaveAction::class);
        $decide = app(DecideLeaveAction::class);
        $admin = User::query()->orderBy('id')->first();

        $plans = [
            [1, 'casual', -12, -11, 'approve', 'Family function'], [2, 'sick', -20, -18, 'approve', 'Fever'], [3, 'annual', 6, 10, 'pending', 'Village visit'],
            [5, 'casual', -5, -5, 'reject', 'Short notice'], [6, 'unpaid', -30, -28, 'approve', 'Personal work'], [7, 'sick', 3, 4, 'pending', 'Dental treatment'],
        ];

        foreach ($plans as [$index, $type, $from, $to, $decision, $reason]) {
            $employee = $employees[$index] ?? null;

            if ($employee === null) {
                continue;
            }

            try {
                $request = $leave->execute($employee, ['type' => $type, 'from_date' => now()->addDays($from)->toDateString(), 'to_date' => now()->addDays($to)->toDateString(), 'reason' => $reason]);

                if ($decision !== 'pending') {
                    $decide->execute($request, $decision === 'approve', (int) $admin?->id);
                }
            } catch (DomainException) {
                continue;
            }
        }
    }

    private function backdate(Order $order, int $days): void
    {
        $at = now()->subDays($days)->subMinutes(mt_rand(0, 480));
        $payments = $order->invoice?->payments->all() ?? [];

        foreach ([$order, $order->invoice, ...$payments, ...$order->samples->all()] as $model) {
            $model?->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
        }

        $shift = fn ($value) => $value?->copy()->subDays($days);

        foreach ($order->samples as $sample) {
            $sample->forceFill(['collected_at' => $shift($sample->collected_at), 'received_at' => $shift($sample->received_at)])->save();
        }

        foreach ($order->items as $item) {
            $item->forceFill(['expected_at' => $shift($item->expected_at)])->save();
        }
    }

    /**
     * Four more staff at the main branch and a small team with its own
     * shift and device at the second branch, with a month of punches.
     *
     * @param  Collection<int, Branch>  $branches
     */
    private function seedMoreEmployees(Collection $branches): void
    {
        $save = app(SaveEmployeeAction::class);
        $ingest = app(IngestAttendancePunchesAction::class);
        $payroll = app(GeneratePayrollRunAction::class);
        $finalize = app(FinalizePayrollRunAction::class);
        $admin = User::query()->orderBy('id')->first();
        $main = $branches->first();
        $second = $branches->get(1);

        $teams = [[$main, 9, [
            ['Arifa Sultana', 'Pathologist assistant', 'Laboratory', 32000], ['Mostafa Kamal', 'Driver', 'Support', 15000],
            ['Lutfun Nahar', 'Receptionist', 'Front desk', 21000], ['Babu Mia', 'IT officer', 'Administration', 28000],
        ]]];

        if ($second !== null) {
            $teams[] = [$second, 20, [
                ['Rashida Parvin', 'Branch receptionist', 'Front desk', 20000], ['Mizan Khan', 'Sample collector', 'Collection', 17000],
                ['Hasina Akter', 'Lab technician', 'Laboratory', 24000], ['Delwar Hossain', 'Peon', 'Support', 11000],
            ]];
        }

        foreach ($teams as [$branch, $firstPin, $people]) {
            $shift = $branch->id === $main->id
                ? WorkShift::query()->withoutGlobalScopes()->where('branch_id', $branch->id)->first()
                : WorkShift::query()->create(['branch_id' => $branch->id, 'name' => 'Morning shift', 'start_time' => '08:00', 'end_time' => '16:00', 'grace_minutes' => 10, 'weekly_off' => [5]]);

            $device = $branch->id === $main->id
                ? AttendanceDevice::query()->withoutGlobalScopes()->where('branch_id', $branch->id)->first()
                : AttendanceDevice::query()->create(['branch_id' => $branch->id, 'name' => 'Branch entrance', 'serial_number' => 'ZKDEMO0002', 'location' => 'Front desk']);

            $staff = [];

            foreach ($people as $index => [$name, $designation, $department, $basic]) {
                $staff[] = $save->execute([
                    'branch_id' => $branch->id, 'name' => $name, 'designation' => $designation, 'department' => $department,
                    'mobile' => sprintf('880172%07d', $firstPin * 100 + $index), 'joined_on' => now()->subMonths(mt_rand(3, 14))->toDateString(),
                    'device_pin' => (string) ($firstPin + $index), 'work_shift_id' => $shift?->id,
                    'basic_salary' => number_format($basic * 0.6, 2, '.', ''), 'house_allowance' => number_format($basic * 0.25, 2, '.', ''),
                    'medical_allowance' => number_format($basic * 0.1, 2, '.', ''), 'transport_allowance' => number_format($basic * 0.05, 2, '.', ''),
                    'other_allowance' => '0.00', 'overtime_rate' => '80.00', 'payment_method' => $index % 2 === 0 ? 'bkash' : 'cash', 'is_active' => true,
                ]);
            }

            $rows = [];
            $start = Carbon::parse($shift?->start_time ?? '09:00');
            $end = Carbon::parse($shift?->end_time ?? '18:00');

            for ($offset = 75; $offset >= 0; $offset--) {
                $day = now()->subDays($offset)->startOfDay();

                if ($day->dayOfWeek === 5) {
                    continue;
                }

                foreach ($staff as $employee) {
                    if (mt_rand(1, 100) <= 6 && $offset > 0) {
                        continue;
                    }

                    $rows[] = [(string) $employee->device_pin, $day->copy()->setTimeFromTimeString($start->format('H:i'))->subMinutes(15)->addMinutes(mt_rand(0, 40))->format('Y-m-d H:i:s')];

                    if (! $day->isToday()) {
                        $rows[] = [(string) $employee->device_pin, $day->copy()->setTimeFromTimeString($end->format('H:i'))->subMinutes(5)->addMinutes(mt_rand(0, 70))->format('Y-m-d H:i:s')];
                    }
                }
            }

            $ingest->execute($branch->id, $rows, 'device', $device);

            if ($branch->id !== $main->id) {
                $finalize->execute($payroll->execute($branch->id, now()->subMonthNoOverflow()->format('Y-m')), (int) $admin?->id);
                $payroll->execute($branch->id, now()->format('Y-m'));
            }
        }

        // The main branch payroll is regenerated so the new staff are included.
        $current = PayrollRun::query()->withoutGlobalScopes()->where('branch_id', $main->id)->where('month', now()->format('Y-m'))->first();

        if ($current !== null) {
            $payroll->execute($main->id, now()->format('Y-m'));
        }
    }
}
