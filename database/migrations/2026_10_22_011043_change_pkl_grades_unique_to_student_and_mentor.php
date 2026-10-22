<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Setiap siswa dapat dinilai oleh Pembina Sekolah dan Pembina Industri,
     * sehingga keunikan nilai berlaku per pasangan siswa dan pembina.
     */
    public function up(): void
    {
        Schema::table('pkl_grades', function (Blueprint $table) {
            $table->unique(['student_id', 'mentor_id']);
        });

        Schema::table('pkl_grades', function (Blueprint $table) {
            $table->dropUnique(['student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pkl_grades', function (Blueprint $table) {
            $table->unique('student_id');
        });

        Schema::table('pkl_grades', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'mentor_id']);
        });
    }
};
