<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tujuan persetujuan ditetapkan saat penilaian dikirim.
        Schema::table('assessments', function (Blueprint $table) {
            $table->string('review_level', 20)->nullable()->after('status'); // structural | leadership
            $table->foreignId('reviewer_unit_id')->nullable()->after('review_level')
                ->constrained('organizational_units')->nullOnDelete();
        });

        // Penilaian lama yang sudah dikirim tetap diperiksa pimpinan.
        DB::table('assessments')
            ->whereIn('status', ['submitted', 'approved', 'finalized'])
            ->update(['review_level' => 'leadership']);
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewer_unit_id');
            $table->dropColumn('review_level');
        });
    }
};
