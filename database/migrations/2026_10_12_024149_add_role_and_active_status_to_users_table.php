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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 32)->default('siswa');
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->boolean('status')->default(true);
            }
        });
    }

    /**
     * Preserve role and status columns because this migration may adopt an existing schema.
     */
    public function down(): void {}
};
