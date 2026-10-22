<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Hr\FinalizePayrollRunAction;
use App\Actions\Hr\GeneratePayrollRunAction;
use App\Actions\Hr\MarkPayslipPaidAction;
use App\Actions\Hr\UpdatePayslipDeductionsAction;
use App\Http\Controllers\Controller;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Support\BranchContext;
use App\Support\ValidationRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PayrollController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Hr/Payroll', [
            'runs' => PayrollRun::query()->with('branch:id,code')->orderByDesc('month')->limit(36)->get()->map(fn (PayrollRun $run): array => [
                'id' => $run->id,
                'month' => $run->month,
                'branch' => $run->branch?->code,
                'status' => $run->status->value,
                'total_gross' => $run->total_gross,
                'total_deductions' => $run->total_deductions,
                'total_net' => $run->total_net,
            ]),
            'defaultMonth' => now()->format('Y-m'),
        ]);
    }

    public function generate(Request $request, GeneratePayrollRunAction $generate, BranchContext $context): RedirectResponse
    {
        $data = $request->validate(['month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/']]);
        $branchId = $context->currentBranchId();
        abort_if($branchId === null, 422);

        $run = $generate->execute($branchId, $data['month']);

        return redirect()->route('admin.payroll.show', $run)->with('success', __('hr.payroll_generated'));
    }

    public function show(PayrollRun $run): Response
    {
        return Inertia::render('Admin/Hr/PayrollShow', [
            'run' => [
                'id' => $run->id,
                'month' => $run->month,
                'status' => $run->status->value,
                'total_gross' => $run->total_gross,
                'total_deductions' => $run->total_deductions,
                'total_net' => $run->total_net,
            ],
            'payslips' => $run->payslips()->orderBy('employee_code')->get(),
        ]);
    }

    public function finalize(Request $request, PayrollRun $run, FinalizePayrollRunAction $action): RedirectResponse
    {
        $action->execute($run, (int) $request->user()?->id);

        return back()->with('success', __('hr.payroll_finalized'));
    }

    public function updatePayslip(Request $request, Payslip $payslip, UpdatePayslipDeductionsAction $action): RedirectResponse
    {
        $data = $request->validate([
            'advance_deduction' => ['required', ValidationRules::MONEY, 'min:0'],
            'other_deduction' => ['required', ValidationRules::MONEY, 'min:0'],
        ]);

        $action->execute($payslip, (string) $data['advance_deduction'], (string) $data['other_deduction']);

        return back()->with('success', __('hr.payslip_updated'));
    }

    public function pay(Request $request, Payslip $payslip, MarkPayslipPaidAction $action): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['required', Rule::in(['cash', 'bkash', 'nagad', 'bank'])],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $action->execute($payslip, $data['payment_method'], $data['payment_reference'] ?? null);

        return back()->with('success', __('hr.payslip_paid'));
    }

    public function payslip(Payslip $payslip): Response
    {
        return Inertia::render('Admin/Hr/Payslip', [
            'payslip' => $payslip->load('run:id,month,status'),
        ]);
    }
}
