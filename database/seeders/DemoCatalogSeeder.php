<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Catalog\SaveCatalogItemAction;
use App\Actions\Catalog\SaveTestAction;
use App\Models\Department;
use App\Models\ReportTemplate;
use App\Models\Test;
use Illuminate\Database\Seeder;

/**
 * A realistic starter catalog for the demo center.
 */
class DemoCatalogSeeder extends Seeder
{
    public function run(SaveTestAction $tests, SaveCatalogItemAction $catalog): void
    {
        if (Test::query()->exists()) {
            return;
        }

        $dept = fn (string $code): int => (int) Department::query()->where('code', $code)->value('id');
        $usg = ReportTemplate::query()->where('name', 'Ultrasonography')->value('id');
        $any = fn (?string $low, ?string $high, ?string $cl = null, ?string $ch = null): array => ['sex' => 'any', 'age_min' => 0, 'age_max' => null, 'age_unit' => 'years', 'low' => $low, 'high' => $high, 'critical_low' => $cl, 'critical_high' => $ch];
        $p = fn (string $code, string $name, ?string $unit, array $ranges = [], string $type = 'number', array $extra = []): array => ['code' => $code, 'name' => $name, 'unit' => $unit, 'result_type' => $type, 'ranges' => $ranges] + $extra;

        $definitions = [
            ['CBC', 'Complete Blood Count', 'HEM', 'blood', 'edta', '400.00', 4, null, [
                $p('HB', 'Hemoglobin', 'g/dL', [
                    ['sex' => 'male', 'age_min' => 18, 'age_max' => null, 'age_unit' => 'years', 'low' => '13', 'high' => '17', 'critical_low' => '7', 'critical_high' => '20'],
                    ['sex' => 'female', 'age_min' => 18, 'age_max' => null, 'age_unit' => 'years', 'low' => '12', 'high' => '15', 'critical_low' => '7', 'critical_high' => '20'],
                    ['sex' => 'any', 'age_min' => 0, 'age_max' => 1, 'age_unit' => 'months', 'low' => '14', 'high' => '24', 'critical_low' => '9', 'critical_high' => '26'],
                    ['sex' => 'any', 'age_min' => 1, 'age_max' => 18, 'age_unit' => 'years', 'low' => '11', 'high' => '16', 'critical_low' => '7', 'critical_high' => '20'],
                ]),
                $p('WBC', 'Total WBC count', '10^3/uL', [$any('4', '11', '2', '30')]),
                $p('RBC', 'RBC count', '10^6/uL', [$any('4.2', '5.9')]),
                $p('PLT', 'Platelet count', '10^3/uL', [$any('150', '450', '20', '1000')]),
                $p('PCV', 'Hematocrit (PCV)', '%', [$any('36', '50')]),
                $p('ESR', 'ESR', 'mm/1st hr', [$any(null, '20')]),
            ]],
            ['FBS', 'Fasting Blood Sugar', 'BIO', 'plasma', 'fluoride', '150.00', 2, 'Fasting 8 to 10 hours', [$p('GLU', 'Glucose (fasting)', 'mmol/L', [$any('3.9', '6.1', '2.5', '25')])]],
            ['HBA1C', 'HbA1c', 'BIO', 'blood', 'edta', '900.00', 6, null, [$p('HBA1C', 'HbA1c', '%', [$any('4', '5.6')])]],
            ['CREAT', 'Serum Creatinine', 'BIO', 'serum', 'gel', '350.00', 4, null, [$p('CREAT', 'Creatinine', 'mg/dL', [
                ['sex' => 'male', 'age_min' => 0, 'age_max' => null, 'age_unit' => 'years', 'low' => '0.7', 'high' => '1.3', 'critical_low' => null, 'critical_high' => '10'],
                ['sex' => 'female', 'age_min' => 0, 'age_max' => null, 'age_unit' => 'years', 'low' => '0.6', 'high' => '1.1', 'critical_low' => null, 'critical_high' => '10'],
            ])]],
            ['LIPID', 'Lipid Profile', 'BIO', 'serum', 'gel', '1200.00', 6, 'Fasting 12 hours', [
                $p('TC', 'Total cholesterol', 'mg/dL', [$any(null, '200')]),
                $p('TG', 'Triglycerides', 'mg/dL', [$any(null, '150')]),
                $p('HDL', 'HDL cholesterol', 'mg/dL', [$any('40', null)]),
                $p('LDL', 'LDL cholesterol (calculated)', 'mg/dL', [$any(null, '130')], 'calculated', ['formula' => '{TC} - {HDL} - {TG} / 5', 'decimals' => 0]),
            ]],
            ['TSH', 'TSH', 'BIO', 'serum', 'gel', '800.00', 24, null, [$p('TSH', 'TSH', 'uIU/mL', [$any('0.4', '4.0')])]],
            ['HBSAG', 'HBsAg', 'SER', 'serum', 'plain', '500.00', 4, null, [$p('HBSAG', 'HBsAg', null, [], 'choice', ['choices' => ['Negative', 'Positive'], 'abnormal_values' => ['Positive']])]],
            ['URE', 'Urine Routine Examination', 'CLP', 'urine', 'urine_cup', '200.00', 4, 'First morning sample preferred', [
                $p('COLOR', 'Color', null, [], 'text'),
                $p('PROT', 'Protein', null, [], 'choice', ['choices' => ['Nil', 'Trace', '+', '++', '+++'], 'abnormal_values' => ['+', '++', '+++']]),
                $p('SUGAR', 'Sugar', null, [], 'choice', ['choices' => ['Nil', 'Trace', '+', '++', '+++'], 'abnormal_values' => ['+', '++', '+++']]),
                $p('PUS', 'Pus cells', '/HPF', [$any('0', '5')]),
            ]],
            ['XRCPA', 'X-ray Chest PA view', 'IMG', 'none', 'none', '500.00', 6, null, []],
            ['USGWA', 'USG of Whole Abdomen', 'IMG', 'none', 'none', '1500.00', 4, 'Full bladder, fasting 6 hours', []],
            ['ECG', 'ECG (12 lead)', 'CAR', 'none', 'none', '400.00', 2, null, []],
        ];

        foreach ($definitions as $index => [$code, $name, $department, $sample, $container, $price, $tat, $prep, $parameters]) {
            $tests->execute([
                'code' => $code, 'name' => $name, 'department_id' => $dept($department), 'sample_type' => $sample, 'container' => $container,
                'price' => $price, 'tat_hours' => $tat, 'preparation' => $prep, 'home_collection' => $sample !== 'none',
                'report_template_id' => $code === 'USGWA' ? $usg : null, 'sort_order' => $index, 'parameters' => $parameters,
            ]);
        }

        $ids = fn (array $codes): array => Test::query()->whereIn('code', $codes)->pluck('id')->all();
        $catalog->package(['code' => 'DIAB', 'name' => 'Diabetes Panel', 'price' => '1200.00', 'test_ids' => $ids(['FBS', 'HBA1C', 'CREAT'])]);
        $catalog->package(['code' => 'BASIC', 'name' => 'Basic Health Checkup', 'price' => '2500.00', 'test_ids' => $ids(['CBC', 'FBS', 'LIPID', 'CREAT', 'URE'])]);
    }
}
