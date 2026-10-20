<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('personnel')->restrictOnDelete();
            $table->foreignId('subject_id')->constrained('personnel')->restrictOnDelete();
            // Snapshot unit anggota saat dicatat.
            $table->foreignId('unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->string('type', 20)->index();
            $table->string('title', 150);
            $table->text('note');
            $table->date('incident_date')->index();
            $table->timestamps();

            $table->index(['subject_id', 'incident_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_notes');
    }
};
