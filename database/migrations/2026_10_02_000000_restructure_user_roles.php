<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('divisi', 100)->nullable()->after('role');
            $table->string('role', 20)->default('staff')->change();
        });

        DB::table('users')->where('role', 'admin')->update(['role' => 'kepala_sdm']);
        DB::table('users')->where('role', 'superadmin')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'pegawai_2')->update(['role' => 'kepala_bagian']);
        DB::table('users')->whereIn('role', ['pegawai', 'karyawan'])->update(['role' => 'staff']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'staff')->update(['role' => 'pegawai']);
        DB::table('users')->where('role', 'kepala_bagian')->update(['role' => 'pegawai_2']);
        DB::table('users')->where('role', 'admin')->update(['role' => 'superadmin']);
        DB::table('users')->where('role', 'kepala_sdm')->update(['role' => 'admin']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('divisi');
            $table->string('role', 20)->default('karyawan')->change();
        });
    }
};
