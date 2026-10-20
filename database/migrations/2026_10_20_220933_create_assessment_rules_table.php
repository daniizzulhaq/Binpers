<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessment_rules', function (Blueprint $table) {
            $table->id();
            $table->string('assessor_role', 30)->index();
            // unit = hanya personel di unit penilai beserta sub-unitnya; polres = seluruh polres.
            $table->string('scope', 10);
            // Role yang boleh dinilai; ["*"] = semua role kecuali HRD.
            $table->json('target_roles');
            // Jabatan yang dikecualikan (mis. Kanit Provost untuk Kapolsek).
            $table->json('excluded_position_ids')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_rules');
    }
};
