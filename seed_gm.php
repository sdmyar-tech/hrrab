<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DataPegawai;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

DB::transaction(function () {
    $pegawai = DataPegawai::create([
        'email' => 'ahmad.baihaqi@hrrab.id',
        'nip' => 'GM' . date('Y') . '0001',
        'nama_lengkap' => 'Susiana',
        'nik' => '1471040505780001',
        'npwp' => '09.123.456.7-891.000',
        'no_kk' => '1471040505780010',
        'no_hp' => '081234567890',
        'tempat_lahir' => 'Pekanbaru',
        'tanggal_lahir' => '1978-05-05',
        'jenis_kelamin' => 'Laki-laki',
        'agama' => 'Islam',
        'golongan_darah' => 'O+',
        'status_pernikahan' => 'Menikah',
        'jumlah_tanggungan' => 2,
        'nama_ibu_kandung' => 'Siti Aminah',
        'pendidikan_terakhir' => 'S2 Manajemen',
        'alamat_ktp' => 'Jl. Soekarno Hatta No. 10, Pekanbaru',
        'alamat_domisili' => 'Jl. Soekarno Hatta No. 10, Pekanbaru',
    ]);

    $pegawai->kepegawaian()->create([
        'jabatan' => 'PEMBINA YAYASAN',
        'jabatan_tambahan' => null,
        'unit_kerja' => 'YAYASAN',
        'lokasi_kerja' => 'YAYASAN',
        'tanggal_masuk' => now()->subYears(5)->format('Y-m-d'),
        'status_rekrutmen' => 'Aktif',
        'status_kerja' => 'Tetap',
        'tanggal_habis_kontrak' => null,
    ]);

    $pegawai->kontakDarurat()->create([
        'no_hp_kerabat' => '081298765432',
        'nama_kerabat' => 'Siti Rahmawati',
        'hubungan_kerabat' => 'Istri',
    ]);

    $pegawai->bpjs()->create([
        'no_bpjs_ketenagakerjaan' => '1234567890123',
        'no_bpjs_kesehatan' => '0001234567890',
        'faskes_tingkat_1' => 'Klinik Yayasan Pekanbaru',
    ]);

    $user = User::create([
        'name' => 'Susiana',
        'email' => $pegawai->email,
        'password' => Hash::make('password123'),
        'role' => 'pembina',
        'nip' => $pegawai->nip,
        'data_pegawai_id' => $pegawai->id,
    ]);

    echo "DataPegawai ID: {$pegawai->id}\n";
    echo "User ID: {$user->id}\n";
    echo "Email: {$user->email}\n";
    echo "Role: {$user->role}\n";
});
