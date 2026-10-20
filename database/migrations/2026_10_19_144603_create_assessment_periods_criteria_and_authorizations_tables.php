<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('draft')->index();
            $table->string('description', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('assessment_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_period_id')->constrained('assessment_periods')->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('description', 500)->nullable();
            $table->decimal('weight', 5, 2);
            $table->decimal('min_score', 6, 2)->default(0);
            $table->decimal('max_score', 6, 2)->default(100);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('assessment_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessor_id')->constrained('personnel')->cascadeOnDelete();
            $table->foreignId('assessee_id')->constrained('personnel')->cascadeOnDelete();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('notes', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['assessor_id', 'assessee_id'], 'authorizations_unique_pair');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_authorizations');
        Schema::dropIfExists('assessment_criteria');
        Schema::dropIfExists('assessment_periods');
    }
};
