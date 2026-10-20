<?php

declare(strict_types=1);

namespace App\Actions\Lab;

use App\Enums\OrderItemStatus;
use App\Enums\ResultType;
use App\Exceptions\DomainException;
use App\Models\NarrativeResult;
use App\Models\OrderItem;
use App\Models\ReferenceRange;
use App\Models\TestParameter;
use App\Models\TestResult;
use App\Models\User;
use App\Services\Catalog\ReferenceRangeResolver;
use App\Services\Catalog\ResultFlagger;
use App\Services\Lab\FormulaEvaluator;
use Illuminate\Support\Facades\DB;

/**
 * Saves result values as a draft. The server recalculates every flag from
 * the resolved reference range and stores the range and flag snapshots, so
 * later catalog changes never alter old results.
 */
final class SaveResultsAction
{
    public function __construct(
        private readonly ReferenceRangeResolver $ranges,
        private readonly ResultFlagger $flagger,
        private readonly FormulaEvaluator $formulas,
    ) {}

    /**
     * @param  array{
     *     results?: array<int, array{value?: string|null, remarks?: string|null}>,
     *     findings?: string|null,
     *     impression?: string|null,
     * }  $data
     */
    public function execute(OrderItem $item, array $data, ?User $actor = null): OrderItem
    {
        if (! in_array($item->status, [OrderItemStatus::Received, OrderItemStatus::Entered], true)) {
            throw DomainException::make('INVALID_STATE', 422);
        }

        $patient = $item->order->patient;
        $item->loadMissing('test.parameters.ranges');

        $parameters = $item->test->parameters->where('is_active', true)->values();
        $submitted = $data['results'] ?? [];

        return DB::transaction(function () use ($item, $parameters, $submitted, $data, $patient, $actor): OrderItem {
            $valuesByCode = [];

            foreach ($parameters as $parameter) {
                $input = $submitted[$parameter->id] ?? [];
                $value = self::text($input['value'] ?? null);

                if ($parameter->result_type === ResultType::Calculated) {
                    continue;
                }

                if ($value !== null && in_array($parameter->result_type, [ResultType::Number], true)
                    && preg_match('/^-?\d+(\.\d+)?$/', $value) !== 1) {
                    throw DomainException::make('RESULT_VALUE_INVALID', 422, ['parameter' => $parameter->code]);
                }

                if ($value !== null) {
                    $valuesByCode[$parameter->code] = $value;
                }
            }

            foreach ($parameters as $parameter) {
                $input = $submitted[$parameter->id] ?? [];
                $value = self::text($input['value'] ?? null);

                if ($parameter->result_type === ResultType::Calculated) {
                    $value = $this->calculatedValue($parameter, $valuesByCode);
                }

                /** @var ReferenceRange|null $range */
                $range = $this->ranges->resolve($parameter, $patient->sex, $patient->date_of_birth);
                $flag = $this->flagger->flag($value, $parameter->result_type, $range, $parameter->abnormal_values);

                TestResult::query()->updateOrCreate(
                    [
                        'order_item_id' => $item->id,
                        'test_parameter_id' => $parameter->id,
                    ],
                    [
                        'patient_id' => $patient->id,
                        'code' => $parameter->code,
                        'name' => $parameter->name,
                        'unit' => $parameter->unit,
                        'result_type' => $parameter->result_type->value,
                        'value' => $value,
                        'flag' => $flag,
                        'range_low' => $range?->low,
                        'range_high' => $range?->high,
                        'range_critical_low' => $range?->critical_low,
                        'range_critical_high' => $range?->critical_high,
                        'range_text' => $range?->text(),
                        'remarks' => self::text($input['remarks'] ?? null),
                        'entered_by' => $actor?->id,
                    ],
                );
            }

            if ($parameters->isEmpty() || ($data['findings'] ?? null) !== null || ($data['impression'] ?? null) !== null) {
                NarrativeResult::query()->updateOrCreate(
                    ['order_item_id' => $item->id],
                    [
                        'findings' => self::text($data['findings'] ?? null),
                        'impression' => self::text($data['impression'] ?? null),
                    ],
                );
            }

            return $item->fresh(['results', 'narrative']) ?? $item;
        });
    }

    /**
     * @param  array<string, string>  $valuesByCode
     */
    private function calculatedValue(TestParameter $parameter, array $valuesByCode): ?string
    {
        if ($parameter->formula === null || $parameter->formula === '') {
            return null;
        }

        $result = $this->formulas->evaluate($parameter->formula, fn (string $code): ?string => $valuesByCode[$code] ?? null);

        if ($result === null) {
            return null;
        }

        $decimals = $parameter->decimals ?? 2;

        return number_format(round($result, $decimals), $decimals, '.', '');
    }

    private static function text(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
