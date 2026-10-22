<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Akun awal saat sistem pertama kali dipasang: Super Admin & Kepala SDM, belum ditautkan ke data pegawai.
 * Wajib ganti password saat login pertama. Aman dijalankan ulang: akun yang sudah ada tidak ditimpa.
 */
class UserRoleSeeder extends Seeder
{
    private const AKUN = [
        ['email' => 'superadmin@abdurrab.id', 'name' => 'Administrator', 'role' => User::ROLE_ADMIN],
['email' => 'kepala.sdm@abdurrab.id', 'name' => 'Kepala SDM', 'role' => User::ROLE_KEPALA_SDM],

    ];

    private const PASSWORD_AWAL = 'password123';

    public function run(): void
    {
        foreach (self::AKUN as $akun) {
            if (DB::table('users')->where('email', $akun['email'])->exists()) {
                $this->command->warn("⏭️  {$akun['email']} sudah ada — dilewati.");

                continue;
            }

            DB::table('users')->insert($akun + [
                'password' => Hash::make(self::PASSWORD_AWAL),
                'nip' => null,
                'data_pegawai_id' => null,
                'wajib_ganti_password' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info('✅ ' . User::roleLabelFor($akun['role']) . " : {$akun['email']} / " . self::PASSWORD_AWAL);
        }
    }
}
