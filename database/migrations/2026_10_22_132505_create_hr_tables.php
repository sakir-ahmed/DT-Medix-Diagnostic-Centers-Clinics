<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_shifts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('grace_minutes')->default(10);
            $table->json('weekly_off')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id']);
        });

        Schema::create('holidays', function (Blueprint $table): void {
            $table->id();
            $table->date('holiday_on')->unique();
            $table->string('name', 150);
            $table->timestamps();
        });

        Schema::create('employees', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('work_shift_id')->nullable()->constrained('work_shifts')->nullOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->string('name_bn', 150)->nullable();
            $table->string('designation', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->date('joined_on');
            $table->string('device_pin', 20)->nullable();
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('house_allowance', 12, 2)->default(0);
            $table->decimal('medical_allowance', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('other_allowance', 12, 2)->default(0);
            $table->decimal('overtime_rate', 12, 2)->default(0);
            $table->string('payment_method', 20)->nullable();
            $table->text('payment_account')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'is_active']);
            $table->unique(['branch_id', 'device_pin']);
        });

        Schema::create('attendance_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('serial_number', 60)->unique();
            $table->string('location', 150)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('attendance_punches', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('attendance_device_id')->nullable()->constrained()->nullOnDelete();
            $table->string('device_pin', 20);
            $table->dateTime('punched_at');
            $table->string('source', 10)->default('device');
            $table->timestamps();

            $table->unique(['branch_id', 'device_pin', 'punched_at']);
            $table->index(['employee_id', 'punched_at']);
        });

        Schema::create('attendance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('work_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('status', 20);
            $table->unsignedSmallInteger('late_minutes')->default(0);
            $table->unsignedSmallInteger('early_minutes')->default(0);
            $table->unsignedSmallInteger('worked_minutes')->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->string('source', 10)->default('device');
            $table->boolean('is_manual')->default(false);
            $table->string('note', 255)->nullable();
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'work_date']);
            $table->index(['branch_id', 'work_date']);
        });

        Schema::create('leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->date('from_date');
            $table->date('to_date');
            $table->unsignedSmallInteger('days');
            $table->string('reason', 500)->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
            $table->index(['employee_id', 'from_date']);
        });

        Schema::create('payroll_runs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->char('month', 7);
            $table->string('status', 20)->default('draft');
            $table->decimal('total_gross', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('total_net', 14, 2)->default(0);
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'month']);
        });

        Schema::create('payslips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('employee_code', 20);
            $table->string('employee_name', 150);
            $table->string('designation', 100)->nullable();
            $table->unsignedSmallInteger('month_days');
            $table->unsignedSmallInteger('present_days')->default(0);
            $table->unsignedSmallInteger('late_count')->default(0);
            $table->unsignedSmallInteger('paid_leave_days')->default(0);
            $table->decimal('absent_days', 4, 1)->default(0);
            $table->unsignedSmallInteger('overtime_minutes')->default(0);
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('allowances', 12, 2)->default(0);
            $table->decimal('overtime_pay', 12, 2)->default(0);
            $table->decimal('gross', 12, 2)->default(0);
            $table->decimal('absence_deduction', 12, 2)->default(0);
            $table->decimal('late_deduction', 12, 2)->default(0);
            $table->decimal('advance_deduction', 12, 2)->default(0);
            $table->decimal('other_deduction', 12, 2)->default(0);
            $table->decimal('net', 12, 2)->default(0);
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('payroll_runs');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_punches');
        Schema::dropIfExists('attendance_devices');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('work_shifts');
    }
};
