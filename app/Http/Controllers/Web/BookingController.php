<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Appointments\BookAppointmentAction;
use App\Actions\Patients\RegisterPatientAction;
use App\Exceptions\DomainException;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicBookingRequest;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\SlotService;
use App\Support\LocalName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public appointment booking for the center's main branch.
 */
final class BookingController extends Controller
{
    private ?Branch $mainBranch = null;

    public function __construct(
        private readonly SlotService $slots,
        private readonly RegisterPatientAction $registerPatient,
        private readonly BookAppointmentAction $bookAppointment,
    ) {}

    public function create(Request $request): Response|RedirectResponse
    {
        if (! settings()->has('center_name')) {
            return redirect()->route('home');
        }

        $bookedId = (int) $request->query('booked', 0);
        $confirmedBookedId = (int) $request->session()->get('public.booked_id', 0);
        $booked = $bookedId > 0 && $bookedId === $confirmedBookedId ? $this->bookedSummary($bookedId) : null;

        $selectedId = (int) $request->query('doctor', 0);

        return Inertia::render('Public/Booking', [
            'doctors' => $this->bookableDoctors()->all(),
            'selectedDoctorId' => $selectedId > 0 ? $selectedId : null,
            'booked' => $booked,
            'centerName' => setting('center_name'),
        ])->withViewData(['meta_title' => __('public.booking_page_title')]);
    }

    public function slots(Request $request): JsonResponse
    {
        $data = $request->validate([
            'doctor' => ['required', 'integer', Rule::exists('doctors', 'id')->where('is_active', true)],
            'date' => ['required', 'date', 'after_or_equal:today', 'before:'.now()->addDays(15)->toDateString()],
        ]);

        $doctor = Doctor::query()->whereKey((int) $data['doctor'])->where('is_active', true)->firstOrFail();

        return response()->json([
            'slots' => $this->slots->slots($doctor, $this->mainBranch()->id, Carbon::parse($data['date'])),
        ]);
    }

    public function store(PublicBookingRequest $request): RedirectResponse
    {
        $doctor = Doctor::query()->whereKey($request->integer('doctor_id'))->where('is_active', true)->firstOrFail();
        $branchId = $this->mainBranch()->id;
        $date = Carbon::parse($request->string('date')->toString());
        $time = $request->string('time')->toString();

        if (! $this->slots->isAvailable($doctor, $branchId, $date, $time)) {
            throw DomainException::make('SLOT_UNAVAILABLE', 409);
        }

        $patient = $this->findOrRegisterPatient($request);

        $appointment = $this->bookAppointment->execute($patient, $doctor, $branchId, $date, $time, 'online');

        $request->session()->put('public.booked_id', $appointment->id);

        return redirect()->route('public.booking', ['booked' => $appointment->id]);
    }

    /**
     * A visitor with a known mobile and a similar name is the same returning
     * patient; anything else is registered fresh.
     */
    private function findOrRegisterPatient(PublicBookingRequest $request): Patient
    {
        /** @var array<string, mixed> $data */
        $data = [
            'name' => $request->string('name')->trim()->toString(),
            'mobile' => $request->string('mobile')->toString(),
            'sex' => $request->string('sex')->toString(),
            'age_years' => $request->integer('age_years'),
            'preferred_locale' => app()->getLocale(),
        ];

        try {
            return $this->registerPatient->execute($data);
        } catch (DomainException $exception) {
            if ($exception->errorCode !== 'DUPLICATE_PATIENT_SUSPECTED') {
                throw $exception;
            }

            $candidateId = (int) ($exception->details['candidates'][0]['id'] ?? 0);

            return $candidateId > 0
                ? Patient::query()->findOrFail($candidateId)
                : $this->registerPatient->execute($data, confirmNew: true);
        }
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function bookableDoctors(): Collection
    {
        $weekdays = SiteController::weekdays();

        return Doctor::query()
            ->where('is_active', true)
            ->where('consultation_fee', '>', 0)
            ->with(['schedules' => fn ($query) => $query->withoutGlobalScope('branch')->where('is_active', true)])
            ->orderBy('name')
            ->get(['id', 'name', 'name_bn', 'specialty', 'qualifications', 'designation', 'consultation_fee'])
            ->map(fn (Doctor $doctor): array => [
                'id' => $doctor->id,
                'name' => LocalName::of($doctor->name, $doctor->name_bn),
                'specialty' => $doctor->specialty,
                'qualifications' => $doctor->qualifications,
                'designation' => $doctor->designation,
                'consultation_fee' => (string) $doctor->consultation_fee,
                'days' => $doctor->schedules
                    ->unique('weekday')
                    ->map(fn ($schedule): string => (string) $weekdays[$schedule->weekday])
                    ->values()
                    ->all(),
            ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function bookedSummary(int $appointmentId): ?array
    {
        $appointment = Appointment::query()
            ->withoutGlobalScope('branch')
            ->with(['doctor', 'patient'])
            ->find($appointmentId);

        if ($appointment === null || $appointment->patient === null || $appointment->doctor === null) {
            return null;
        }

        return [
            'token_no' => $appointment->token_no,
            'patient_name' => $appointment->patient->name,
            'patient_code' => $appointment->patient->patient_code,
            'doctor_name' => LocalName::of($appointment->doctor->name, $appointment->doctor->name_bn),
            'doctor_specialty' => $appointment->doctor->specialty,
            'date' => $appointment->appointment_date->toDateString(),
            'time' => substr($appointment->start_time, 0, 5),
            'fee' => (string) $appointment->fee,
        ];
    }

    private function mainBranch(): Branch
    {
        return $this->mainBranch ??= Branch::query()->orderBy('code')->firstOrFail();
    }
}
