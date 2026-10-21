<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penilaian harian disetujui atasan seperti rekap bulanan.
 * Catatan lama dan catatan HRD dianggap sudah disetujui.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_notes', function (Blueprint $table) {
            $table->string('status', 20)->default('approved')->after('incident_date');
            $table->string('review_level', 20)->nullable()->after('status');
            $table->foreignId('reviewer_unit_id')->nullable()->after('review_level')
                ->constrained('organizational_units')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->after('reviewer_unit_id')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_notes')->nullable()->after('reviewed_at');

            $table->index(['status', 'review_level', 'reviewer_unit_id']);
        });
    }

    public function down(): void
    {
        Schema::table('performance_notes', function (Blueprint $table) {
            $table->dropIndex(['status', 'review_level', 'reviewer_unit_id']);
            $table->dropConstrainedForeignId('reviewer_unit_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['status', 'review_level', 'reviewed_at', 'review_notes']);
        });
    }
};
