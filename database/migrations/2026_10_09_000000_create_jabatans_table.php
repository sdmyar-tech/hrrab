<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jabatans', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150)->unique();
            $table->timestamps();
        });

        $sekarang = now();
        $daftar = collect(config('kepegawaian.jabatan', []))
            ->map(fn ($j) => mb_strtoupper(trim($j)))->filter()->unique()
            ->map(fn ($j) => ['nama' => $j, 'created_at' => $sekarang, 'updated_at' => $sekarang]);

        DB::table('jabatans')->insert($daftar->values()->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('jabatans');
    }
};
