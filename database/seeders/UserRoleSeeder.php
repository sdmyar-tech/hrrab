<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Akun awal saat sistem pertama kali dipasang: hanya Admin, belum ditautkan ke data pegawai.
 * Akun lain (Kepala SDM, dst.) dibuat Admin lewat Kelola Akun.
 * Wajib ganti password saat login pertama. Aman dijalankan ulang: akun yang sudah ada tidak ditimpa.
 */
class UserRoleSeeder extends Seeder
{
    private const AKUN = [
        ['email' => 'admin@example.com', 'name' => 'Administrator', 'role' => User::ROLE_ADMIN],
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
