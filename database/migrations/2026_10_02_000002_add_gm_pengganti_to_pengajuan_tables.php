<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['pengajuan_lemburs', 'pengajuan_cutis'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->boolean('gm_pengganti')->default(false)->after('gm_approved_at');
            });
        }
    }

    public function down(): void
    {
        foreach (['pengajuan_lemburs', 'pengajuan_cutis'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('gm_pengganti');
            });
        }
    }
};
