<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->join('data_pegawais', 'data_pegawais.id', '=', 'users.data_pegawai_id')
            ->join('kepegawaians', 'kepegawaians.data_pegawai_id', '=', 'data_pegawais.id')
            ->where('users.role', 'admin')
            ->whereRaw('UPPER(TRIM(kepegawaians.jabatan)) != ?', ['KEPALA SDM'])
            ->update(['users.role' => 'pegawai_2']);
    }

    public function down(): void
    {
        DB::table('users')
            ->join('data_pegawais', 'data_pegawais.id', '=', 'users.data_pegawai_id')
            ->join('kepegawaians', 'kepegawaians.data_pegawai_id', '=', 'data_pegawais.id')
            ->where('users.role', 'pegawai_2')
            ->update(['users.role' => 'admin']);
    }
};
