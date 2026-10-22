<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Absensi Hadir dicatat dari lokasi GPS siswa yang harus berada di dalam
     * radius tempat PKL, sehingga posisi saat masuk dan pulang ikut disimpan.
     */
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->unsignedSmallInteger('attendance_radius')->default(150)->after('longitude');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('check_in_latitude', 10, 7)->nullable()->after('time_out');
            $table->decimal('check_in_longitude', 10, 7)->nullable()->after('check_in_latitude');
            $table->unsignedSmallInteger('check_in_accuracy')->nullable()->after('check_in_longitude');
            $table->unsignedInteger('check_in_distance')->nullable()->after('check_in_accuracy');
            $table->decimal('check_out_latitude', 10, 7)->nullable()->after('check_in_distance');
            $table->decimal('check_out_longitude', 10, 7)->nullable()->after('check_out_latitude');
            $table->unsignedSmallInteger('check_out_accuracy')->nullable()->after('check_out_longitude');
            $table->unsignedInteger('check_out_distance')->nullable()->after('check_out_accuracy');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'check_in_latitude', 'check_in_longitude', 'check_in_accuracy', 'check_in_distance',
                'check_out_latitude', 'check_out_longitude', 'check_out_accuracy', 'check_out_distance',
            ]);
        });

        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn('attendance_radius');
        });
    }
};
