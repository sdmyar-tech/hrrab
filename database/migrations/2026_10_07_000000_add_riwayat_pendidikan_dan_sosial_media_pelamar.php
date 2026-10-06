<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_pendidikan_pelamars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_pelamar_id')->constrained('data_pelamars')->cascadeOnDelete();
            $table->string('jenjang', 20);
            $table->string('nama_institusi', 150);
            $table->string('jurusan', 150)->nullable();
            $table->unsignedSmallInteger('tahun_masuk')->nullable();
            $table->unsignedSmallInteger('tahun_lulus')->nullable();
            $table->string('nilai', 20)->nullable();
            $table->timestamps();
        });

        Schema::table('data_pelamars', function (Blueprint $table) {
            $table->json('sosial_media')->nullable()->after('portofolio_link');
        });

        DB::table('data_pelamars')->whereNotNull('portofolio_link')->where('portofolio_link', '!=', '')
            ->get(['id', 'portofolio_link'])
            ->each(fn ($p) => DB::table('data_pelamars')->where('id', $p->id)
                ->update(['sosial_media' => json_encode([['platform' => 'Portofolio', 'url' => $p->portofolio_link]])]));

        Schema::table('data_pelamars', function (Blueprint $table) {
            $table->dropColumn('portofolio_link');
        });
    }

    public function down(): void
    {
        Schema::table('data_pelamars', function (Blueprint $table) {
            $table->string('portofolio_link')->nullable()->after('cv_path');
        });

        DB::table('data_pelamars')->whereNotNull('sosial_media')->get(['id', 'sosial_media'])
            ->each(fn ($p) => DB::table('data_pelamars')->where('id', $p->id)
                ->update(['portofolio_link' => json_decode($p->sosial_media, true)[0]['url'] ?? null]));

        Schema::table('data_pelamars', function (Blueprint $table) {
            $table->dropColumn('sosial_media');
        });

        Schema::dropIfExists('riwayat_pendidikan_pelamars');
    }
};
