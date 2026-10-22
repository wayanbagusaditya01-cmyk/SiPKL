<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal kerja tempat PKL (hari kerja, jam masuk, jam pulang), keterlambatan
     * absen masuk, dan foto bukti pengajuan izin.
     */
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->json('work_days')->nullable()->after('attendance_radius');
            $table->time('check_in_start')->default('07:00')->after('work_days');
            $table->time('check_in_end')->default('08:00')->after('check_in_start');
            $table->time('check_out_start')->default('16:00')->after('check_in_end');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedSmallInteger('late_minutes')->nullable()->after('time_out');
            $table->string('leave_photo_path')->nullable()->after('note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['late_minutes', 'leave_photo_path']);
        });

        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn(['work_days', 'check_in_start', 'check_in_end', 'check_out_start']);
        });
    }
};
