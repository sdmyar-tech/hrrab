<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('karyawan')->after('password');
            $table->string('nip', 50)->nullable()->after('role');
            $table->foreignId('data_pegawai_id')->nullable()->after('nip')
                ->constrained('data_pegawais')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['data_pegawai_id']);
            $table->dropColumn(['role', 'nip', 'data_pegawai_id']);
        });
    }
};
