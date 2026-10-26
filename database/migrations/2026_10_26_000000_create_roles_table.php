<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master Role. Baris "bawaan" = penyesuaian role sistem (nama tampilan, keterangan, hak akses) — tanpa baris, role
     * bawaan memakai nilai di kode. Baris kustom = role baru yang mengikuti jenis dasar (users.role) tapi hak aksesnya sendiri.
     * Instalasi baru: tabel kosong.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 60)->unique();
            $table->string('nama', 100);
            $table->string('keterangan', 255)->nullable();
            $table->string('jenis_dasar', 30);
            $table->json('hak_akses');
            $table->boolean('bawaan')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
        Schema::dropIfExists('roles');
    }
};
