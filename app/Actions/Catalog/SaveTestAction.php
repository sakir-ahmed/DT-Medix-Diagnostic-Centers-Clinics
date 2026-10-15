<?php

declare(strict_types=1);

namespace App\Actions\Catalog;

use App\Enums\ResultType;
use App\Exceptions\DomainException;
use App\Models\BranchTestPrice;
use App\Models\Test;
use App\Models\TestParameter;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Saves a test with its parameters, reference ranges and branch prices in
 * one transaction. Parameters missing from the payload are removed.
 */
final class SaveTestAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, ?Test $test = null): Test
    {
        /** @var list<array<string, mixed>> $parameters */
        $parameters = $data['parameters'] ?? [];
        /** @var array<int|string, string|null> $branchPrices */
        $branchPrices = $data['branch_prices'] ?? [];
        $syncParameters = array_key_exists('parameters', $data);

        $this->validateFormulas($parameters);

        return DB::transaction(function () use ($data, $test, $parameters, $branchPrices, $syncParameters): Test {
            $test ??= new Test;
            $test->fill(Arr::except($data, ['parameters', 'branch_prices']));
            $test->code = strtoupper((string) $data['code']);
            $test->save();

            if ($syncParameters) {
                $keep = [];

                foreach (array_values($parameters) as $index => $row) {
                    /** @var TestParameter $parameter */
                    $parameter = isset($row['id'])
                        ? $test->parameters()->whereKey($row['id'])->firstOrNew()
                        : new TestParameter;

                    $parameter->fill(Arr::except($row, ['id', 'ranges']) + ['sort_order' => $index]);
                    $parameter->code = strtoupper((string) $row['code']);
                    $parameter->test()->associate($test);
                    $parameter->save();
                    $keep[] = $parameter->id;

                    $parameter->ranges()->delete();

                    foreach ($row['ranges'] ?? [] as $range) {
                        $parameter->ranges()->create($range);
                    }
                }

                $test->parameters()->whereNotIn('id', $keep)->delete();
            }

            foreach ($branchPrices as $branchId => $price) {
                if ($price === null || $price === '') {
                    BranchTestPrice::query()->where('branch_id', $branchId)->where('test_id', $test->id)->delete();
                } else {
                    BranchTestPrice::query()->updateOrCreate(['branch_id' => (int) $branchId, 'test_id' => $test->id], ['price' => $price]);
                }
            }

            return $test;
        });
    }

    /**
     * A formula may only reference parameter codes of the same test.
     *
     * @param  list<array<string, mixed>>  $parameters
     */
    private function validateFormulas(array $parameters): void
    {
        $codes = array_map(fn (array $p): string => strtoupper((string) $p['code']), $parameters);

        foreach ($parameters as $index => $parameter) {
            if (($parameter['result_type'] ?? null) !== ResultType::Calculated->value) {
                continue;
            }

            $formula = (string) ($parameter['formula'] ?? '');
            preg_match_all('/\{([A-Za-z0-9_]+)\}/', $formula, $matches);
            $stripped = preg_replace('/\{[A-Za-z0-9_]+\}/', '0', $formula) ?? '';

            $unknown = array_diff(array_map('strtoupper', $matches[1]), $codes);

            if ($matches[1] === [] || $unknown !== [] || preg_match('/^[\d\s.+\-*\/()]+$/', $stripped) !== 1) {
                throw DomainException::make('INVALID_FORMULA', 422, ['parameter' => $index]);
            }
        }
    }
}
