<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Alur persetujuan khusus per pegawai (menimpa aturan otomatis unit): tahap yang dilalui + atasan/GM tertentu.
 * 2. Penanda akun wajib mengganti password sebelum bisa memakai sistem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alur_pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pegawai_id')->unique()->constrained('data_pegawais')->cascadeOnDelete();
            $table->json('tahap');                  // urutan level, mis. ["kepala_divisi","kepala_sdm"]
            $table->foreignId('atasan_user_id')->nullable()->constrained('users')->nullOnDelete(); // null = kepala unit otomatis
            $table->foreignId('gm_user_id')->nullable()->constrained('users')->nullOnDelete();     // null = GM mana pun
            $table->string('catatan', 255)->nullable();
            $table->foreignId('diubah_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('wajib_ganti_password')->default(false)->after('password');
            $table->timestamp('password_diganti_pada')->nullable()->after('wajib_ganti_password');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['wajib_ganti_password', 'password_diganti_pada']);
        });
        Schema::dropIfExists('alur_pegawais');
    }
};
