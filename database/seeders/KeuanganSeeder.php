<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class KeuanganSeeder extends Seeder
{
    public function run(): void
    {
        $daftar = [
            [
                'urutan' => 31,
                'nama' => 'Bayu Kusuma',
                'jabatan' => 'Kepala Keuangan',
                'unit_kerja' => 'KEUANGAN YAR/YAYASAN',
                'role' => 'kepala_bagian',
                'tahunMasuk' => 4,
            ],
            [
                'urutan' => 32,
                'nama' => 'Wahyu Nugroho',
                'jabatan' => 'Staff Keuangan',
                'unit_kerja' => 'KEUANGAN YAR/YAYASAN',
                'role' => 'staff',
                'tahunMasuk' => 2,
            ],
            [
                'urutan' => 33,
                'nama' => 'Rendra Saputra',
                'jabatan' => 'Staff Keuangan',
                'unit_kerja' => 'KEUANGAN YAR/YAYASAN',
                'role' => 'staff',
                'tahunMasuk' => 2,
            ],
            [
                'urutan' => 34,
                'nama' => 'Fajar Nugraha',
                'jabatan' => 'Kepala SDM',
                'unit_kerja' => 'SDM',
                'role' => 'kepala_sdm',
                'tahunMasuk' => 5,
            ],
        ];

        foreach ($daftar as $data) {
            $urutan = $data['urutan'];
            $email = strtolower(str_replace([' ', '.'], '', $data['nama'])) . $urutan . '@yayasanislam.or.id';
            $nip = 'YPI' . date('Y') . str_pad((string) $urutan, 4, '0', STR_PAD_LEFT);

            $pegawaiId = DB::table('data_pegawais')->updateOrInsert(
                ['email' => $email],
                [
                    'nip' => $nip,
                    'nama_lengkap' => $data['nama'],
                    'nik' => '3171' . rand(100000000000, 999999999999),
                    'npwp' => '09.' . rand(100, 999) . '.' . rand(100, 999) . '.' . rand(1, 9) . '-' . rand(100, 999) . '.000',
                    'no_kk' => '3171' . rand(100000000000, 999999999999),
                    'no_hp' => '08' . rand(11, 59) . rand(1000000, 9999999),
                    'tempat_lahir' => 'Jakarta',
                    'tanggal_lahir' => Carbon::now()->subYears(rand(28, 45))->format('Y-m-d'),
                    'jenis_kelamin' => 'Laki-laki',
                    'agama' => 'Islam',
                    'golongan_darah' => 'O',
                    'status_pernikahan' => 'Menikah',
                    'jumlah_tanggungan' => rand(1, 3),
                    'nama_ibu_kandung' => 'Siti Aminah',
                    'pendidikan_terakhir' => 'S1 Ekonomi',
                    'alamat_ktp' => 'Jl. Pendidikan Islam No. ' . $urutan . ', DKI Jakarta',
                    'alamat_domisili' => 'Jl. Pendidikan Islam No. ' . $urutan . ', DKI Jakarta',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $pegawaiId = DB::table('data_pegawais')->where('email', $email)->value('id');

            $tglMasuk = Carbon::now()->subYears($data['tahunMasuk']);

            DB::table('kepegawaians')->updateOrInsert(
                ['data_pegawai_id' => $pegawaiId],
                [
                    'jabatan' => $data['jabatan'],
                    'jabatan_tambahan' => null,
                    'unit_kerja' => $data['unit_kerja'],
                    'lokasi_kerja' => 'Kampus Utama Yayasan',
                    'tanggal_masuk' => $tglMasuk->format('Y-m-d'),
                    'status_rekrutmen' => 'Aktif',
                    'status_kerja' => 'Tetap',
                    'tanggal_habis_kontrak' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('kontak_darurats')->updateOrInsert(
                ['data_pegawai_id' => $pegawaiId],
                [
                    'no_hp_kerabat' => '08' . rand(11, 59) . rand(1000000, 9999999),
                    'nama_kerabat' => 'Istri/Suami',
                    'hubungan_kerabat' => 'Istri',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('bpjs_pegawais')->updateOrInsert(
                ['data_pegawai_id' => $pegawaiId],
                [
                    'no_bpjs_ketenagakerjaan' => '000' . rand(1000000000, 9999999999),
                    'no_bpjs_kesehatan' => '000' . rand(1000000000, 9999999999),
                    'faskes_tingkat_1' => 'Klinik Pratama Yayasan Islam',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            DB::table('users')->updateOrInsert(
                ['email' => $email],
                [
                    'name' => $data['nama'],
                    'password' => Hash::make('password123'),
                    'role' => $data['role'],
                    'nip' => $nip,
                    'data_pegawai_id' => $pegawaiId,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $this->command->info("✅ {$data['jabatan']} : {$email} / password123");
        }
    }
}
