<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('performance_notes', function (Blueprint $table) {
            $table->foreignId('author_user_id')->nullable()->after('author_id')
                ->constrained('users')->nullOnDelete();
            // HRD boleh tidak punya data personel.
            $table->unsignedBigInteger('author_id')->nullable()->change();
        });

        // Isi akun pencatat untuk catatan yang sudah ada.
        DB::table('performance_notes as n')
            ->join('personnel as p', 'p.id', '=', 'n.author_id')
            ->update(['n.author_user_id' => DB::raw('p.user_id')]);
    }

    public function down(): void
    {
        Schema::table('performance_notes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('author_user_id');
        });
    }
};
