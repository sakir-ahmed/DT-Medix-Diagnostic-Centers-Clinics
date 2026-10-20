<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('appointment_date');
            $table->time('start_time');
            $table->unsignedSmallInteger('token_no');
            $table->string('visit_type', 20)->default('new');
            $table->string('status', 20)->default('booked');
            $table->string('source', 20)->default('walk-in');
            $table->decimal('fee', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->foreignId('booked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'appointment_date']);
            $table->index(['doctor_id', 'appointment_date', 'start_time']);
            $table->index(['patient_id', 'doctor_id']);
        });

        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('referrer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('urgency', 20)->default('routine');
            $table->string('status', 20)->default('active');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['branch_id', 'created_at']);
            $table->index(['patient_id']);
            $table->index(['referrer_id']);
        });

        Schema::create('invoices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_no', 40);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->string('discount_type', 10)->nullable();
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_reason')->nullable();
            $table->foreignId('discount_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('paid', 12, 2)->default(0);
            $table->decimal('refunded', 12, 2)->default(0);
            $table->decimal('due', 12, 2)->default(0);
            $table->string('status', 20)->default('due');
            $table->timestamp('fully_paid_at')->nullable();
            $table->timestamps();

            $table->unique(['invoice_no']);
            $table->index(['branch_id', 'status']);
            $table->index(['patient_id', 'status']);
        });

        Schema::create('samples', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('sample_code', 40);
            $table->string('sample_type', 30)->nullable();
            $table->string('container', 30)->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('collected_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable();
            $table->string('reject_reason')->nullable();
            $table->foreignId('recollection_of_id')->nullable()->constrained('samples')->nullOnDelete();
            $table->timestamps();

            $table->unique(['sample_code']);
            $table->index(['branch_id', 'status']);
        });

        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->restrictOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sample_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->boolean('vat_applicable')->default(true);
            $table->unsignedSmallInteger('tat_hours')->default(24);
            $table->timestamp('expected_at')->nullable();
            $table->string('status', 20)->default('ordered');
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['test_id']);
        });

        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('receipt_no', 40);
            $table->string('method', 20);
            $table->decimal('amount', 12, 2);
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('completed');
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->unique(['receipt_no']);
            $table->index(['branch_id', 'paid_at']);
            $table->index(['invoice_id']);
        });

        Schema::create('discount_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('discount_type', 10);
            $table->decimal('discount_value', 12, 2);
            $table->string('reason');
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });

        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method', 20)->default('cash');
            $table->string('reason');
            $table->string('status', 20)->default('pending');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['refunds', 'discount_requests', 'payments', 'order_items', 'samples', 'invoices', 'orders', 'appointments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
