<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const TABEL = [
        'kepegawaians' => 'unit_kerja',
        'lowongans' => 'unit_kerja',
        'pengajuan_formasis' => 'unit_kerja',
        'lamarans' => 'unit_kerja_tujuan',
    ];

    public function up(): void
    {
        foreach (self::TABEL as $tabel => $kolom) {
            DB::table($tabel)->where($kolom, 'Keuangan')->update([$kolom => 'KEUANGAN YAR/YAYASAN']);
        }
    }

    public function down(): void
    {
        //
    }
};
