<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personnel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->restrictOnDelete();
            $table->string('nrp', 20)->unique();
            $table->string('rank', 50)->nullable();
            $table->char('gender', 1)->nullable();
            $table->string('place_of_birth', 100)->nullable();
            $table->date('birth_date')->nullable();
            $table->date('join_date')->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        Schema::create('personnel_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('personnel_id')->constrained('personnel')->cascadeOnDelete();
            $table->foreignId('organizational_unit_id')->constrained('organizational_units')->restrictOnDelete();
            $table->foreignId('position_id')->constrained('positions')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('decree_number', 100)->nullable();
            $table->string('notes', 500)->nullable();
            $table->timestamps();

            $table->index(['personnel_id', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personnel_assignments');
        Schema::dropIfExists('personnel');
    }
};
