<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\Package;
use App\Models\Test;
use App\Support\LocalName;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public website pages for the configured center.
 */
final class SiteController extends Controller
{
    public function doctors(): Response|RedirectResponse
    {
        if (! settings()->has('center_name')) {
            return redirect()->route('home');
        }

        $weekdays = self::weekdays();

        $doctors = Doctor::query()
            ->where('is_active', true)
            ->where('consultation_fee', '>', 0)
            ->with(['schedules' => fn ($query) => $query->withoutGlobalScope('branch')->where('is_active', true)])
            ->orderBy('name')
            ->get(['id', 'name', 'name_bn', 'specialty', 'qualifications', 'designation', 'consultation_fee', 'follow_up_fee', 'is_visiting'])
            ->map(fn (Doctor $doctor): array => [
                'id' => $doctor->id,
                'name' => LocalName::of($doctor->name, $doctor->name_bn),
                'specialty' => $doctor->specialty,
                'qualifications' => $doctor->qualifications,
                'designation' => $doctor->designation,
                'consultation_fee' => (string) $doctor->consultation_fee,
                'follow_up_fee' => (string) $doctor->follow_up_fee,
                'is_visiting' => $doctor->is_visiting,
                'days' => $doctor->schedules
                    ->unique('weekday')
                    ->map(fn ($schedule): string => (string) $weekdays[$schedule->weekday])
                    ->values()
                    ->all(),
            ]);

        return Inertia::render('Public/Doctors', [
            'centerName' => setting('center_name'),
            'doctors' => $doctors->all(),
        ])->withViewData(['meta_title' => __('public.doctors_page_title')]);
    }

    public function tests(): Response|RedirectResponse
    {
        if (! settings()->has('center_name')) {
            return redirect()->route('home');
        }

        $departments = Department::query()
            ->where('is_active', true)
            ->with(['tests' => fn ($query) => $query->where('is_active', true)->where('is_outsourced', false)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $packages = Package::query()
            ->where('is_active', true)
            ->with('tests')
            ->orderBy('name')
            ->get();

        return Inertia::render('Public/Tests', [
            'centerName' => setting('center_name'),
            'departments' => $departments
                ->filter(fn (Department $department): bool => $department->tests->isNotEmpty())
                ->map(fn (Department $department): array => [
                    'id' => $department->id,
                    'name' => LocalName::of($department->name, $department->name_bn),
                    'category' => $department->category->value,
                    'tests' => $department->tests->map(fn ($test): array => [
                        'name' => LocalName::of($test->name, $test->name_bn),
                        'price' => (string) $test->price,
                        'tat_hours' => $test->tat_hours,
                        'preparation' => $test->preparation,
                    ])->all(),
                ])->values()->all(),
            'packages' => $packages->map(fn (Package $package): array => [
                'id' => $package->id,
                'name' => LocalName::of($package->name, $package->name_bn),
                'price' => (string) $package->price,
                'description' => $package->description,
                'tests' => $package->tests
                    ->map(fn ($test): string => LocalName::of($test->name, $test->name_bn))
                    ->all(),
            ])->all(),
        ])->withViewData(['meta_title' => __('public.tests_page_title')]);
    }

    public function about(): Response|RedirectResponse
    {
        return $this->staticPage('Public/About', 'about');
    }

    public function services(): Response|RedirectResponse
    {
        if (! settings()->has('center_name')) {
            return redirect()->route('home');
        }

        $departments = Department::query()
            ->where('is_active', true)
            ->with(['tests' => fn ($query) => $query->where('is_active', true)->where('is_outsourced', false)->orderBy('sort_order')->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Public/Services', [
            'departments' => $departments
                ->filter(fn (Department $department): bool => $department->tests->isNotEmpty())
                ->map(fn (Department $department): array => [
                    'id' => $department->id,
                    'name' => LocalName::of($department->name, $department->name_bn),
                    'category' => $department->category->value,
                    'tests_count' => $department->tests->count(),
                    'sample_tests' => $department->tests->take(5)
                        ->map(fn (Test $test): string => LocalName::of($test->name, $test->name_bn))
                        ->all(),
                ])->values()->all(),
        ])->withViewData(['meta_title' => __('site.services.title')]);
    }

    public function homeCollection(): Response|RedirectResponse
    {
        if (! settings()->has('center_name')) {
            return redirect()->route('home');
        }

        $tests = Test::query()
            ->where('is_active', true)
            ->where('is_outsourced', false)
            ->where('home_collection', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'name_bn', 'price', 'preparation']);

        return Inertia::render('Public/HomeCollection', [
            'tests' => $tests->map(fn (Test $test): array => [
                'name' => LocalName::of($test->name, $test->name_bn),
                'price' => (string) $test->price,
                'preparation' => $test->preparation,
            ])->all(),
        ])->withViewData(['meta_title' => __('site.home_collection.title')]);
    }

    public function reports(): Response|RedirectResponse
    {
        return $this->staticPage('Public/Reports', 'reports');
    }

    public function contact(): Response|RedirectResponse
    {
        return $this->staticPage('Public/Contact', 'contact');
    }

    public function faq(): Response|RedirectResponse
    {
        return $this->staticPage('Public/Faq', 'faq');
    }

    public function guide(): Response|RedirectResponse
    {
        return $this->staticPage('Public/Guide', 'guide');
    }

    public function privacy(): Response|RedirectResponse
    {
        return $this->staticPage('Public/Privacy', 'privacy');
    }

    private function staticPage(string $component, string $section): Response|RedirectResponse
    {
        if (! settings()->has('center_name')) {
            return redirect()->route('home');
        }

        return Inertia::render($component)->withViewData(['meta_title' => __("site.{$section}.title")]);
    }

    /**
     * @return Collection<int, string>
     */
    public static function weekdays(): Collection
    {
        $locale = app()->getLocale();

        return collect(range(0, 6))->mapWithKeys(
            fn (int $day): array => [$day => now()->startOfWeek()->addDays($day)->locale($locale)->shortDayName]
        );
    }
}
