<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->foreignId('gm_approved_by')->nullable()->after('kepala_approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('gm_approved_at')->nullable()->after('gm_approved_by');
        });

        Schema::table('pengajuan_lemburs', function (Blueprint $table) {
            $table->foreignId('gm_approved_by')->nullable()->after('kepala_approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('gm_approved_at')->nullable()->after('gm_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->dropForeign(['gm_approved_by']);
            $table->dropColumn(['gm_approved_by', 'gm_approved_at']);
        });

        Schema::table('pengajuan_lemburs', function (Blueprint $table) {
            $table->dropForeign(['gm_approved_by']);
            $table->dropColumn(['gm_approved_by', 'gm_approved_at']);
        });
    }
};
