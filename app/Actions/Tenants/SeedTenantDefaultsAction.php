<?php

declare(strict_types=1);

namespace App\Actions\Tenants;

use App\Enums\DepartmentCategory;
use App\Enums\MessageKey;
use App\Enums\ReportStyle;
use App\Models\Department;
use App\Models\MessageTemplate;
use App\Models\ReportTemplate;

/**
 * Default departments, report templates and message templates for a new
 * tenant. Runs inside the tenant context. Safe to run again.
 */
final class SeedTenantDefaultsAction
{
    public function execute(): void
    {
        $departments = [
            ['HEM', 'Hematology', 'রক্তবিজ্ঞান', DepartmentCategory::Pathology, ReportStyle::Table],
            ['BIO', 'Biochemistry', 'বায়োকেমিস্ট্রি', DepartmentCategory::Pathology, ReportStyle::Table],
            ['SER', 'Serology and Immunology', 'সেরোলজি ও ইমিউনোলজি', DepartmentCategory::Pathology, ReportStyle::Table],
            ['CLP', 'Clinical Pathology', 'ক্লিনিক্যাল প্যাথলজি', DepartmentCategory::Pathology, ReportStyle::Table],
            ['MIC', 'Microbiology', 'মাইক্রোবায়োলজি', DepartmentCategory::Pathology, ReportStyle::Narrative],
            ['IMG', 'Imaging', 'ইমেজিং', DepartmentCategory::Imaging, ReportStyle::Narrative],
            ['CAR', 'Cardiology', 'কার্ডিওলজি', DepartmentCategory::Cardiology, ReportStyle::Narrative],
        ];

        foreach ($departments as $index => [$code, $name, $nameBn, $category, $style]) {
            Department::query()->firstOrCreate(['code' => $code], [
                'name' => $name, 'name_bn' => $nameBn, 'category' => $category, 'report_style' => $style, 'sort_order' => $index,
            ]);
        }

        if (ReportTemplate::query()->doesntExist()) {
            ReportTemplate::query()->create(['name' => 'Standard pathology table', 'type' => ReportStyle::Table, 'options' => ReportTemplate::DEFAULT_OPTIONS]);
            $imaging = ReportTemplate::query()->create([
                'name' => 'Ultrasonography',
                'type' => ReportStyle::Narrative,
                'department_id' => Department::query()->where('code', 'IMG')->value('id'),
                'default_findings' => "Liver: Normal in size and echotexture.\nGall bladder: Normal.\nKidneys: Normal in size and position.",
                'default_impression' => 'Normal study.',
            ]);
            $imaging->phrases()->createMany([
                ['shortcut' => 'normal-liver', 'section' => 'findings', 'text' => 'Liver is normal in size, shape and echotexture. No focal lesion is seen.'],
                ['shortcut' => 'fatty-liver', 'section' => 'findings', 'text' => 'Liver is mildly enlarged with diffusely increased echotexture.'],
                ['shortcut' => 'normal-study', 'section' => 'impression', 'text' => 'Normal study.'],
            ]);
        }

        foreach (MessageKey::cases() as $key) {
            [$en, $bn] = $key->defaults();

            MessageTemplate::query()->firstOrCreate(['key' => $key->value, 'channel' => 'sms'], ['body_en' => $en, 'body_bn' => $bn]);
        }
    }
}
