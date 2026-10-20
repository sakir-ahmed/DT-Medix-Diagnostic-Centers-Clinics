<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20);
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('category', 20)->default('pathology');
            $table->string('report_style', 20)->default('table');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['code']);
        });

        Schema::create('report_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('table');
            $table->json('options')->nullable();
            $table->text('default_findings')->nullable();
            $table->text('default_impression')->nullable();
            $table->text('footer_note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('quick_phrases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('report_template_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('shortcut', 40);
            $table->string('section', 20)->default('findings');
            $table->text('text');
            $table->timestamps();

            $table->index(['shortcut']);
        });

        Schema::create('tests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('report_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('sample_type', 30)->nullable();
            $table->string('container', 30)->nullable();
            $table->decimal('price', 12, 2)->default(0);
            $table->unsignedSmallInteger('tat_hours')->default(24);
            $table->text('preparation')->nullable();
            $table->text('method')->nullable();
            $table->text('interpretation')->nullable();
            $table->boolean('home_collection')->default(false);
            $table->boolean('is_outsourced')->default(false);
            $table->string('vendor_name')->nullable();
            $table->decimal('vendor_cost', 12, 2)->nullable();
            $table->boolean('vat_applicable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['code']);
            $table->index(['name']);
        });

        Schema::create('test_parameters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('group_label')->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('result_type', 20)->default('number');
            $table->json('choices')->nullable();
            $table->json('abnormal_values')->nullable();
            $table->string('formula')->nullable();
            $table->unsignedTinyInteger('decimals')->nullable();
            $table->string('default_value')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['test_id', 'code']);
        });

        Schema::create('reference_ranges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('test_parameter_id')->constrained()->cascadeOnDelete();
            $table->string('sex', 10)->default('any');
            $table->unsignedSmallInteger('age_min')->default(0);
            $table->unsignedSmallInteger('age_max')->nullable();
            $table->string('age_unit', 10)->default('years');
            $table->decimal('low', 14, 4)->nullable();
            $table->decimal('high', 14, 4)->nullable();
            $table->decimal('critical_low', 14, 4)->nullable();
            $table->decimal('critical_high', 14, 4)->nullable();
            $table->string('display_text')->nullable();
            $table->timestamps();

            $table->index(['test_parameter_id', 'sex']);
        });

        Schema::create('packages', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20);
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->decimal('price', 12, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['code']);
        });

        Schema::create('package_test', function (Blueprint $table): void {
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->primary(['package_id', 'test_id']);
        });

        Schema::create('branch_test_prices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->timestamps();

            $table->unique(['branch_id', 'test_id']);
        });

        Schema::create('message_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 40);
            $table->string('channel', 20)->default('sms');
            $table->text('body_en');
            $table->text('body_bn');
            $table->string('whatsapp_template')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['key', 'channel']);
        });
    }

    public function down(): void
    {
        foreach (['message_templates', 'branch_test_prices', 'package_test', 'packages', 'reference_ranges', 'test_parameters', 'tests', 'quick_phrases', 'report_templates', 'departments'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
