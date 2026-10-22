<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->string('current_level', 20)->nullable()->after('status');
            $table->foreignId('kepala_approved_by')->nullable()->after('current_level')->constrained('users')->nullOnDelete();
            $table->timestamp('kepala_approved_at')->nullable()->after('kepala_approved_by');
            $table->dropColumn('pengganti_setuju');
        });

        Schema::table('pengajuan_lemburs', function (Blueprint $table) {
            $table->string('current_level', 20)->nullable()->after('status');
            $table->foreignId('kepala_approved_by')->nullable()->after('current_level')->constrained('users')->nullOnDelete();
            $table->timestamp('kepala_approved_at')->nullable()->after('kepala_approved_by');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_cutis', function (Blueprint $table) {
            $table->dropForeign(['kepala_approved_by']);
            $table->dropColumn(['current_level', 'kepala_approved_by', 'kepala_approved_at']);
            $table->boolean('pengganti_setuju')->default(false);
        });

        Schema::table('pengajuan_lemburs', function (Blueprint $table) {
            $table->dropForeign(['kepala_approved_by']);
            $table->dropColumn(['current_level', 'kepala_approved_by', 'kepala_approved_at']);
        });
    }
};
