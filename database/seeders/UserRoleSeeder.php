<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        $kepala = DB::table('kepegawaians')
            ->join('data_pegawais', 'data_pegawais.id', '=', 'kepegawaians.data_pegawai_id')
            ->where('kepegawaians.jabatan', 'LIKE', 'Kepala%')
            ->select(
                'data_pegawais.id',
                'data_pegawais.nama_lengkap',
                'data_pegawais.email',
                'data_pegawais.nip',
                'kepegawaians.jabatan',
                'kepegawaians.unit_kerja'
            )
            ->first();

        $staff = DB::table('kepegawaians')
            ->join('data_pegawais', 'data_pegawais.id', '=', 'kepegawaians.data_pegawai_id')
            ->where('kepegawaians.jabatan', 'NOT LIKE', 'Kepala%')
            ->select(
                'data_pegawais.id',
                'data_pegawais.nama_lengkap',
                'data_pegawais.email',
                'data_pegawais.nip',
                'kepegawaians.jabatan',
                'kepegawaians.unit_kerja'
            )
            ->first();

        DB::table('users')->updateOrInsert(
            ['email' => 'superadmin@example.com'],
            [
                'name'            => 'Administrator',
                'email'           => 'superadmin@example.com',
                'password'        => Hash::make('password123'),
                'role'            => 'admin',
                'nip'             => null,
                'data_pegawai_id' => null,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]
        );
        $this->command->info('✅ Admin      : superadmin@example.com / password123');

        foreach (array_keys(config('kepegawaian.gm_bidang')) as $i => $bidang) {
            $email = $i === 0 ? 'gm@example.com' : 'gm.' . Str::slug(Str::after($bidang, 'GM ')) . '@example.com';

            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name'            => $i === 0 ? 'Ahmad Wildan' : $bidang,
                    'email'           => $email,
                    'password'        => Hash::make('password123'),
                    'role'            => 'gm',
                    'divisi'          => json_encode(config("kepegawaian.gm_bidang.{$bidang}")),
                    'nip'             => null,
                    'data_pegawai_id' => null,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );
            $this->command->info("✅ {$bidang} : {$email} / password123");
        }

        if ($kepala) {
            DB::table('users')->updateOrInsert(
                ['email' => $kepala->email],
                [
                    'name'            => $kepala->nama_lengkap,
                    'email'           => $kepala->email,
                    'password'        => Hash::make('password123'),
                    'role'            => 'kepala_bagian',
                    'nip'             => $kepala->nip,
                    'data_pegawai_id' => $kepala->id,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );
            $this->command->info("✅ Kepala Bagian : {$kepala->email} / password123");
            $this->command->info("   └─ {$kepala->nama_lengkap} ({$kepala->jabatan} - {$kepala->unit_kerja})");
        } else {
            $this->command->warn('⚠️  Tidak ada pegawai jabatan "Kepala ..." — Kepala Bagian dilewati.');
        }

        if ($staff) {
            DB::table('users')->updateOrInsert(
                ['email' => $staff->email],
                [
                    'name'            => $staff->nama_lengkap,
                    'email'           => $staff->email,
                    'password'        => Hash::make('password123'),
                    'role'            => 'staff',
                    'nip'             => $staff->nip,
                    'data_pegawai_id' => $staff->id,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );
            $this->command->info("✅ Staff      : {$staff->email} / password123");
            $this->command->info("   └─ {$staff->nama_lengkap} ({$staff->jabatan} - {$staff->unit_kerja})");
        } else {
            $this->command->warn('⚠️  Tidak ada pegawai non-Kepala — Staff dilewati.');
        }
    }
}
