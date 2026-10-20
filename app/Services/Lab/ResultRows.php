<?php

declare(strict_types=1);

namespace App\Services\Lab;

use App\Models\OrderItem;
use App\Models\Patient;
use App\Models\ReferenceRange;
use App\Models\TestParameter;
use App\Models\TestResult;
use App\Services\Catalog\ReferenceRangeResolver;
use Illuminate\Database\Eloquent\Collection;

/**
 * Builds the rows of the result entry grid: the resolved reference range,
 * the stored value and the patient's previous result for every parameter.
 */
final class ResultRows
{
    public function __construct(private readonly ReferenceRangeResolver $ranges) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function forItem(OrderItem $item): array
    {
        $patient = $item->order->patient;
        $item->loadMissing(['test.parameters.ranges', 'results']);

        $parameters = $item->test->parameters->where('is_active', true)->values();
        $previous = $this->previousResults($patient, $item, $parameters);
        $stored = $item->results->keyBy('test_parameter_id');

        $rows = [];

        /** @var TestParameter $parameter */
        foreach ($parameters as $parameter) {
            /** @var ReferenceRange|null $range */
            $range = $this->ranges->resolve($parameter, $patient->sex, $patient->date_of_birth);
            /** @var TestResult|null $result */
            $result = $stored->get($parameter->id);

            $rows[] = [
                'id' => $parameter->id,
                'code' => $parameter->code,
                'name' => $parameter->name,
                'group_label' => $parameter->group_label,
                'unit' => $parameter->unit,
                'result_type' => $parameter->result_type->value,
                'choices' => $parameter->choices,
                'abnormal_values' => $parameter->abnormal_values,
                'formula' => $parameter->formula,
                'decimals' => $parameter->decimals,
                'range' => $range === null ? null : [
                    'low' => $range->low,
                    'high' => $range->high,
                    'critical_low' => $range->critical_low,
                    'critical_high' => $range->critical_high,
                    'text' => $range->text(),
                ],
                'value' => $result?->value,
                'remarks' => $result?->remarks,
                'critical_acknowledged' => $result !== null && $result->flag === 'C' && $result->critical_ack_at !== null,
                'previous' => $previous->get($parameter->id),
            ];
        }

        return $rows;
    }

    /**
     * The most recent value of every parameter for this patient from
     * another order item.
     *
     * @param  Collection<int, TestParameter>  $parameters
     * @return \Illuminate\Support\Collection<int, array{value: string, date: string|null}>
     */
    private function previousResults(Patient $patient, OrderItem $item, Collection $parameters): \Illuminate\Support\Collection
    {
        if ($parameters->isEmpty()) {
            return collect();
        }

        $results = TestResult::query()
            ->where('patient_id', $patient->id)
            ->whereIn('test_parameter_id', $parameters->pluck('id'))
            ->whereNot('order_item_id', $item->id)
            ->whereNotNull('value')
            ->where('value', '!=', '')
            ->orderByDesc('id')
            ->get()
            ->unique('test_parameter_id');

        return $results->mapWithKeys(fn (TestResult $result): array => [
            $result->test_parameter_id => ['value' => (string) $result->value, 'date' => $result->updated_at?->toIso8601String()],
        ]);
    }

    /**
     * Whether this test is entered as text (findings and impression)
     * instead of a parameter grid.
     */
    public function isNarrative(OrderItem $item): bool
    {
        $item->loadMissing('test.parameters');

        return $item->test->parameters->where('is_active', true)->isEmpty();
    }
}
