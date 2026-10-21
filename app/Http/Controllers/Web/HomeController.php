<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Test;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public landing page: the center's website, or the product page before
 * the center profile exists. Rendered with SSR.
 */
class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $configured = settings()->has('center_name');
        $title = $configured ? (string) setting('center_name') : (string) config('app.name');

        return Inertia::render('Public/Home', [
            'center' => $configured ? $this->center() : null,
        ])->withViewData(['meta_title' => $title]);
    }

    /**
     * @return array<string, mixed>
     */
    private function center(): array
    {
        $locale = app()->getLocale();

        $tests = Test::query()
            ->where('is_active', true)
            ->where('is_outsourced', false)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'name_bn', 'price', 'tat_hours']);

        $departments = Department::query()
            ->where('is_active', true)
            ->withCount(['tests' => fn ($query) => $query->where('is_active', true)])
            ->orderBy('name')
            ->get(['id', 'name', 'name_bn', 'category']);

        $doctors = Doctor::query()
            ->where('is_active', true)
            ->with(['schedules' => fn ($query) => $query->where('is_active', true)->orderBy('weekday')])
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name', 'name_bn', 'specialty', 'qualifications', 'designation']);

        $weekdays = collect(range(0, 6))->mapWithKeys(
            fn (int $day): array => [$day => now()->startOfWeek()->addDays($day)->locale($locale)->shortDayName]
        );

        return [
            'name' => setting('center_name'),
            'name_bn' => setting('center_name_bn'),
            'address' => setting('center_address'),
            'phone' => setting('center_contact_mobile'),
            'stats' => [
                'tests' => Test::query()->where('is_active', true)->count(),
                'doctors' => Doctor::query()->where('is_active', true)->count(),
                'departments' => Department::query()->where('is_active', true)->count(),
            ],
            'tests' => $tests->map(fn (Test $test): array => [
                'name' => $locale === 'bn' && filled($test->name_bn) ? $test->name_bn : $test->name,
                'price' => (string) $test->price,
                'tat_hours' => $test->tat_hours,
            ])->all(),
            'departments' => $departments->map(fn (Department $department): array => [
                'name' => $locale === 'bn' && filled($department->name_bn) ? $department->name_bn : $department->name,
                'category' => $department->category->value,
                'tests_count' => $department->tests_count,
            ])->all(),
            'doctors' => $doctors->map(fn (Doctor $doctor): array => [
                'id' => $doctor->id,
                'name' => $locale === 'bn' && filled($doctor->name_bn) ? $doctor->name_bn : $doctor->name,
                'specialty' => $doctor->specialty,
                'qualifications' => $doctor->qualifications,
                'designation' => $doctor->designation,
                'consultation_fee' => (string) $doctor->consultation_fee,
                'days' => $doctor->schedules
                    ->unique('weekday')
                    ->map(fn ($schedule): string => (string) $weekdays[$schedule->weekday])
                    ->values()
                    ->all(),
            ])->all(),
        ];
    }
}
