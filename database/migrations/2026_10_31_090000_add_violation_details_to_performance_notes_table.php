<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Riwayat Pelanggaran: klasifikasi (disiplin / kode etik), putusan sidang, tanggal putusan.
        // Bentuk pelanggaran disimpan di kolom title.
        Schema::table('performance_notes', function (Blueprint $table) {
            $table->string('violation_category', 20)->nullable()->after('level');
            $table->text('verdict')->nullable()->after('note');
            $table->date('verdict_date')->nullable()->after('verdict');
        });
    }

    public function down(): void
    {
        Schema::table('performance_notes', function (Blueprint $table) {
            $table->dropColumn(['violation_category', 'verdict', 'verdict_date']);
        });
    }
};
