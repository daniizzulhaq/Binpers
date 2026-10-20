<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_period_id')->constrained('assessment_periods')->restrictOnDelete();
            $table->foreignId('assessor_id')->constrained('personnel')->restrictOnDelete();
            $table->foreignId('assessee_id')->constrained('personnel')->restrictOnDelete();
            // Snapshot unit & jabatan anggota saat dinilai.
            $table->foreignId('assessee_unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->foreignId('assessee_position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->string('grade', 30)->nullable();
            $table->text('general_notes')->nullable();   // catatan internal
            $table->text('member_notes')->nullable();    // catatan yang boleh dilihat anggota
            $table->unsignedTinyInteger('revision_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['assessment_period_id', 'assessor_id', 'assessee_id'], 'assessments_unique_pair');
        });

        Schema::create('assessment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('assessment_criterion_id')->constrained('assessment_criteria')->restrictOnDelete();
            $table->decimal('score', 6, 2);
            $table->string('note', 1000)->nullable();
            $table->string('evidence_path', 255)->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'assessment_criterion_id'], 'assessment_details_unique');
        });

        Schema::create('assessment_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('action', 20);
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_approvals');
        Schema::dropIfExists('assessment_details');
        Schema::dropIfExists('assessments');
    }
};
