<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNotNull('kepala_unit')->get(['id', 'kepala_unit'])->each(function ($u) {
            DB::table('users')->where('id', $u->id)->update(['kepala_unit' => json_encode([$u->kepala_unit])]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('kepala_unit')->nullable()->change();
        });
    }

    public function down(): void
    {
        $lama = DB::table('users')->whereNotNull('kepala_unit')->get(['id', 'kepala_unit']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('kepala_unit', 100)->nullable()->change();
        });

        $lama->each(fn ($u) => DB::table('users')->where('id', $u->id)->update(['kepala_unit' => json_decode($u->kepala_unit, true)[0] ?? null]));
    }
};
