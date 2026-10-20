<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_parameter_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('code', 60);
            $table->string('name');
            $table->string('unit', 40)->nullable();
            $table->string('result_type', 20);
            $table->string('value', 500)->nullable();
            $table->string('flag', 2)->nullable();
            $table->string('range_low', 20)->nullable();
            $table->string('range_high', 20)->nullable();
            $table->string('range_critical_low', 20)->nullable();
            $table->string('range_critical_high', 20)->nullable();
            $table->string('range_text')->nullable();
            $table->string('remarks', 500)->nullable();
            $table->string('critical_informed_name')->nullable();
            $table->timestamp('critical_informed_at')->nullable();
            $table->foreignId('critical_ack_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('critical_ack_at')->nullable();
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['order_item_id', 'test_parameter_id']);
            $table->index(['patient_id', 'test_parameter_id']);
            $table->index(['flag']);
        });

        Schema::create('narrative_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->text('findings')->nullable();
            $table->text('impression')->nullable();
            $table->timestamps();

            $table->unique(['order_item_id']);
        });

        Schema::create('result_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['order_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_attachments');
        Schema::dropIfExists('narrative_results');
        Schema::dropIfExists('test_results');
    }
};
