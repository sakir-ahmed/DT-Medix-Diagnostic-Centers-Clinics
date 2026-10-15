<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Patients\RegisterPatientAction;
use App\Enums\ReferrerType;
use App\Enums\RoleName;
use App\Models\Branch;
use App\Models\Doctor;
use App\Models\Referrer;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Seeder;

/**
 * Doctors with schedules, referrers and a few patients for the demo tenants.
 */
class DemoClinicalDataSeeder extends Seeder
{
    public function run(TenantContext $context, RegisterPatientAction $register): void
    {
        foreach (Tenant::query()->whereIn('slug', ['demo', 'citycare'])->get() as $tenant) {
            $context->run($tenant, function () use ($register, $context): void {
                if (Doctor::query()->exists()) {
                    return;
                }

                $branches = Branch::query()->orderBy('code')->get();
                $context->setCurrentBranch($branches->first()?->id);
                $doctorUser = User::role(RoleName::Doctor->value)->first();

                $doctors = [
                    ['name' => 'Dr. Ayesha Rahman', 'specialty' => 'Medicine', 'qualifications' => 'MBBS, FCPS (Medicine)', 'bmdc_no' => 'A-23145', 'consultation_fee' => '1000.00', 'follow_up_fee' => '500.00', 'user_id' => $doctorUser?->id],
                    ['name' => 'Dr. Kamrul Hasan', 'specialty' => 'Pathology', 'qualifications' => 'MBBS, M.Phil (Pathology)', 'bmdc_no' => 'A-18820', 'consultation_fee' => '0.00', 'follow_up_fee' => '0.00'],
                    ['name' => 'Dr. Nusrat Jahan', 'specialty' => 'Gynae and Obs', 'qualifications' => 'MBBS, FCPS (Gynae)', 'bmdc_no' => 'A-30211', 'consultation_fee' => '1200.00', 'follow_up_fee' => '600.00', 'is_visiting' => true],
                ];

                foreach ($doctors as $data) {
                    $doctor = Doctor::query()->create($data + ['slot_minutes' => 15]);

                    foreach ($branches as $branch) {
                        foreach ([0, 1, 2, 3, 4, 6] as $weekday) {
                            $doctor->schedules()->create(['branch_id' => $branch->id, 'weekday' => $weekday, 'start_time' => '17:00', 'end_time' => '21:00']);
                        }
                    }
                }

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

                $patients = [
                    ['name' => 'Rahim Uddin', 'name_bn' => 'রহিম উদ্দিন', 'sex' => 'male', 'age_years' => 45, 'mobile' => '01711000001', 'blood_group' => 'B+'],
                    ['name' => 'Fatema Begum', 'name_bn' => 'ফাতেমা বেগম', 'sex' => 'female', 'age_years' => 38, 'mobile' => '01711000001', 'relation_to_mobile_owner' => 'Wife'],
                    ['name' => 'Arif Hossain', 'sex' => 'male', 'age_years' => 29, 'mobile' => '01711000002', 'allergies' => 'Penicillin'],
                    ['name' => 'Sumaiya Islam', 'sex' => 'female', 'age_years' => 6, 'mobile' => '01711000003', 'relation_to_mobile_owner' => 'Daughter'],
                ];

                foreach ($patients as $data) {
                    $register->execute($data, true);
                }
            });
        }
    }
}
