<?php

declare(strict_types=1);

use App\Enums\ResultType;
use App\Enums\Sex;
use App\Models\ReferenceRange;
use App\Models\TestParameter;
use App\Services\Catalog\ReferenceRangeResolver;
use App\Services\Catalog\ResultFlagger;
use Illuminate\Support\Carbon;

function parameterWithRanges(array $ranges): TestParameter
{
    $parameter = new TestParameter(['code' => 'HB', 'name' => 'Hemoglobin', 'result_type' => 'number']);
    $parameter->setRelation('ranges', collect($ranges)->map(fn (array $r) => new ReferenceRange($r + ['age_unit' => 'years', 'age_min' => 0, 'age_max' => null, 'sex' => 'any'])));

    return $parameter;
}

beforeEach(function (): void {
    $this->parameter = parameterWithRanges([
        ['sex' => 'any', 'age_min' => 0, 'age_max' => 1, 'age_unit' => 'months', 'low' => '14', 'high' => '24'],
        ['sex' => 'any', 'age_min' => 1, 'age_max' => 18, 'age_unit' => 'years', 'low' => '11', 'high' => '16'],
        ['sex' => 'male', 'age_min' => 18, 'age_max' => null, 'age_unit' => 'years', 'low' => '13', 'high' => '17'],
        ['sex' => 'female', 'age_min' => 18, 'age_max' => null, 'age_unit' => 'years', 'low' => '12', 'high' => '15'],
        ['sex' => 'any', 'age_min' => 0, 'age_max' => null, 'age_unit' => 'years', 'low' => '10', 'high' => '18'],
    ]);
    $this->resolver = new ReferenceRangeResolver;
    $this->today = Carbon::parse('2026-10-15');
});

it('chooses the range for age and sex at the edges', function (string $dob, Sex $sex, string $expectedLow): void {
    $range = $this->resolver->resolve($this->parameter, $sex, Carbon::parse($dob), $this->today);

    expect(ReferenceRange::trim((string) $range?->low))->toBe($expectedLow);
})->with([
    'newborn day 0' => ['2026-10-15', Sex::Male, '14'],
    'newborn 30 days, still under 1 month' => ['2026-09-16', Sex::Female, '14'],
    'exactly 1 month is no longer neonatal' => ['2026-09-15', Sex::Female, '10'],
    'exactly 1 year is a child' => ['2025-10-15', Sex::Male, '11'],
    'one day before the 18th birthday is still a child' => ['2008-10-16', Sex::Male, '11'],
    'on the 18th birthday a man gets the adult male range' => ['2008-10-15', Sex::Male, '13'],
    'adult woman gets the female range' => ['1990-01-01', Sex::Female, '12'],
    'adult of other sex falls back to the any range' => ['1990-01-01', Sex::Other, '10'],
]);

it('uses only the all-ages range when the date of birth is unknown', function (): void {
    expect(ReferenceRange::trim((string) $this->resolver->resolve($this->parameter, Sex::Male, null, $this->today)?->low))->toBe('10');
});

it('returns null when no range matches', function (): void {
    $parameter = parameterWithRanges([['sex' => 'female', 'low' => '1', 'high' => '2']]);

    expect($this->resolver->resolve($parameter, Sex::Male, Carbon::parse('1990-01-01'), $this->today))->toBeNull();
});

it('flags numeric results including the limits', function (string $value, ?string $flag): void {
    $range = new ReferenceRange(['low' => '13', 'high' => '17', 'critical_low' => '7', 'critical_high' => '20']);

    expect((new ResultFlagger)->flag($value, ResultType::Number, $range))->toBe($flag);
})->with([
    ['13', 'N'], ['17', 'N'], ['12.9', 'L'], ['17.1', 'H'], ['7', 'C'], ['6.5', 'C'], ['20', 'C'], ['25', 'C'], ['abc', null], ['', null],
]);

it('flags abnormal choices and leaves free text unflagged', function (): void {
    $flagger = new ResultFlagger;

    expect($flagger->flag('Positive', ResultType::Choice, null, ['Positive']))->toBe('A')
        ->and($flagger->flag('negative', ResultType::Choice, null, ['Positive']))->toBe('N')
        ->and($flagger->flag('Straw', ResultType::Text, null, []))->toBeNull();
});

it('prints the range text', function (): void {
    expect((new ReferenceRange(['low' => '13.0000', 'high' => '17.5000']))->text())->toBe('13 - 17.5')
        ->and((new ReferenceRange(['high' => '200.0000']))->text())->toBe('< 200')
        ->and((new ReferenceRange(['low' => '40.0000']))->text())->toBe('> 40')
        ->and((new ReferenceRange(['low' => '1', 'display_text' => 'Negative']))->text())->toBe('Negative');
});
