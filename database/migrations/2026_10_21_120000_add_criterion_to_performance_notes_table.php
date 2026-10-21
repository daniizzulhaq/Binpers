<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penilaian harian: setiap catatan menunjuk satu kriteria periode.
        // Catatan lama (tanpa kriteria) dihitung sebagai "Umum".
        Schema::table('performance_notes', function (Blueprint $table) {
            $table->foreignId('assessment_criterion_id')->nullable()->after('unit_id')
                ->constrained('assessment_criteria')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('performance_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assessment_criterion_id');
        });
    }
};
