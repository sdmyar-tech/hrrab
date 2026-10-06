<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_jadwals', function (Blueprint $table) {
            $table->unsignedTinyInteger('tahap_interview')->default(1)->after('pertemuan_ke');
        });

        DB::table('interview_jadwals')->where('pertemuan_ke', '>=', 2)->update(['tahap_interview' => 2]);
    }

    public function down(): void
    {
        Schema::table('interview_jadwals', function (Blueprint $table) {
            $table->dropColumn('tahap_interview');
        });
    }
};
