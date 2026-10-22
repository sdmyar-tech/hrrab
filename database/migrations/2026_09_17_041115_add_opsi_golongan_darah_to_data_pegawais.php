<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `data_pegawais`
            MODIFY `golongan_darah` ENUM(
                'A', 'A+', 'A-',
                'B', 'B+', 'B-',
                'AB', 'AB+', 'AB-',
                'O', 'O+', 'O-',
                'Belum Diketahui'
            ) NULL DEFAULT 'Belum Diketahui'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `data_pegawais`
            MODIFY `golongan_darah` ENUM('A', 'B', 'AB', 'O', 'Belum Diketahui')
            NULL DEFAULT 'Belum Diketahui'");
    }
};
