<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tingkat & poin yang disimpan di setiap catatan (snapshot saat dibuat).
        Schema::table('performance_notes', function (Blueprint $table) {
            $table->string('level', 10)->nullable()->after('type');
            $table->smallInteger('points')->default(0)->after('level');
        });

        // Catatan lama dianggap tingkat Ringan.
        DB::table('performance_notes')->where('type', 'positive')->update(['level' => 'light', 'points' => 1]);
        DB::table('performance_notes')->where('type', 'negative')->update(['level' => 'light', 'points' => -1]);

        // Pengaturan poin per tingkat (diatur HRD).
        Schema::create('note_point_settings', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('level', 10);
            $table->unsignedSmallInteger('points');
            $table->timestamps();

            $table->unique(['type', 'level']);
        });

        $now = now();
        foreach (['positive', 'negative'] as $type) {
            foreach (['light' => 1, 'medium' => 3, 'heavy' => 5] as $level => $points) {
                DB::table('note_point_settings')->insert([
                    'type' => $type, 'level' => $level, 'points' => $points,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }

        // Penguncian rekap bulanan.
        Schema::table('assessment_periods', function (Blueprint $table) {
            $table->timestamp('recap_locked_at')->nullable()->after('status');
            $table->foreignId('recap_locked_by')->nullable()->after('recap_locked_at')
                ->constrained('users')->nullOnDelete();
        });

        // Snapshot nilai bulanan saat rekap dikunci.
        Schema::create('monthly_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_period_id')->constrained('assessment_periods')->cascadeOnDelete();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->decimal('base_score', 5, 2)->nullable();
            $table->unsignedSmallInteger('assessment_count')->default(0);
            $table->unsignedInteger('bonus')->default(0);
            $table->unsignedInteger('penalty')->default(0);
            $table->decimal('total', 5, 2)->nullable();
            $table->string('grade', 30)->nullable();
            $table->timestamps();

            $table->unique(['assessment_period_id', 'personnel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_scores');

        Schema::table('assessment_periods', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recap_locked_by');
            $table->dropColumn('recap_locked_at');
        });

        Schema::dropIfExists('note_point_settings');

        Schema::table('performance_notes', function (Blueprint $table) {
            $table->dropColumn(['level', 'points']);
        });
    }
};
