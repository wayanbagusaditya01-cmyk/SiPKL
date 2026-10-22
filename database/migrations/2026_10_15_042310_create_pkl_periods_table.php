<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pkl_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('academic_year', 9);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 24)->default('Menunggu')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pkl_periods');
    }
};
