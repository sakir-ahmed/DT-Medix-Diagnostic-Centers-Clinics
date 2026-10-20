<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 80);
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();

            $table->unique(['key']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('designation')->nullable()->after('name');
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('specialty')->nullable();
            $table->string('qualifications')->nullable();
            $table->string('designation')->nullable();
            $table->string('bmdc_no', 30)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('email')->nullable();
            $table->decimal('consultation_fee', 12, 2)->default(0);
            $table->decimal('follow_up_fee', 12, 2)->default(0);
            $table->unsignedSmallInteger('follow_up_days')->nullable();
            $table->unsignedSmallInteger('slot_minutes')->default(15);
            $table->boolean('is_visiting')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('signature_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['name']);
            $table->unique(['bmdc_no']);
        });

        Schema::create('doctor_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('slot_minutes')->nullable();
            $table->unsignedSmallInteger('max_patients')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['doctor_id', 'weekday']);
        });

        Schema::create('doctor_leaves', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['doctor_id', 'start_date']);
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('registered_branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('patient_code', 20);
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('sex', 10);
            $table->date('date_of_birth')->nullable();
            $table->boolean('dob_estimated')->default(false);
            $table->string('mobile', 20)->nullable();
            $table->string('relation_to_mobile_owner', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('blood_group', 5)->nullable();
            $table->text('nid')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_mobile', 20)->nullable();
            $table->string('photo_path')->nullable();
            $table->text('allergies')->nullable();
            $table->text('notes')->nullable();
            $table->string('preferred_locale', 5)->default('en');
            $table->boolean('messages_opt_out')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['patient_code']);
            $table->index(['mobile']);
            $table->index(['name']);
        });

        Schema::create('referrers', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20)->default('doctor');
            $table->string('code', 20)->nullable();
            $table->string('name');
            $table->string('organization')->nullable();
            $table->string('specialty')->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->text('payment_account')->nullable();
            $table->string('bank_name')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['name']);
            $table->index(['mobile']);
            $table->unique(['code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrers');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('doctor_leaves');
        Schema::dropIfExists('doctor_schedules');
        Schema::dropIfExists('doctors');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('designation'));
        Schema::dropIfExists('sequences');
    }
};
