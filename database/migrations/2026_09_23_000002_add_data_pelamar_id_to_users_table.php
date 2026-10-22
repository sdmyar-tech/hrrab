<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('data_pelamar_id')->nullable()->after('data_pegawai_id')
                ->constrained('data_pelamars')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['data_pelamar_id']);
            $table->dropColumn('data_pelamar_id');
        });
    }
};
