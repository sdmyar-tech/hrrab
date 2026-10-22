<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNotNull('divisi')->get(['id', 'divisi'])->each(function ($user) {
            DB::table('users')->where('id', $user->id)->update(['divisi' => json_encode([$user->divisi])]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('divisi')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('divisi', 100)->nullable()->change();
        });
    }
};
