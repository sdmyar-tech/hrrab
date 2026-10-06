<?php
namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PegawaiSeeder extends Seeder
{
    public function run(): void
    {
        if (DB::table('data_pegawais')->exists()) {
            return;
        }

        $namaPria = [
            'Ahmad Fauzi', 'Muhammad Rizky', 'Bambang Supriyanto', 'Nur Hidayat', 'Fajar Ramadhan',
            'Taufik Hidayat', 'Eko Prasetyo', 'Hendra Wijaya', 'Agus Setiawan', 'Arif Rahman',
            'Dedi Kurniawan', 'M. Haikal Fadhil', 'Abdul Malik', 'Zainal Abidin', 'Rahmat Hidayatullah',
        ];

        $namaWanita = [
            'Siti Nurhaliza', 'Dewi Rahmawati', 'Anisa Putri', 'Rina Kartika', 'Fitriani',
            'Sri Wahyuni', 'Laila Sari', 'Nurul Aini', 'Khadijah', 'Fatimah Azzahra',
            'Umi Kalsum', 'Tri Murti', 'Endang Rahayu', 'Indah Permata', 'Yulia Safitri',
        ];

        $tempatLahir = ['Jakarta', 'Bandung', 'Surabaya', 'Yogyakarta', 'Semarang', 'Malang', 'Solo', 'Bogor'];
        $pendidikan  = ['S1 Pendidikan', 'S1 Teknik Informatika', 'S1 Ekonomi Islam', 'S2 Pendidikan Islam', 'S2 Manajemen', 'D3 Administrasi'];
        $golDarah    = ['A', 'B', 'AB', 'O'];
        $statusNikah = ['Menikah', 'Belum Menikah', 'Cerai Hidup'];

        $unitKerjaList = [
            'Sekretariat Yayasan', 'TK Islam Integrated', 'SD Islam Integrated',
            'SMP Islam Integrated', 'SMA Islam Integrated', 'Universitas Islam (STAI)',
        ];

        $jabatanList = [
            'Sekretariat Yayasan'      => ['Staff HRD', 'Staff Keuangan', 'Staff IT', 'Kepala Tata Usaha'],
            'TK Islam Integrated'      => ['Guru Pengajar TK', 'Kepala Sekolah TK'],
            'SD Islam Integrated'      => ['Guru Kelas SD', 'Guru PAI SD', 'Guru Olahraga SD'],
            'SMP Islam Integrated'     => ['Guru Matematika SMP', 'Guru Bahasa Inggris SMP', 'Kepala Sekolah SMP'],
            'SMA Islam Integrated'     => ['Guru Fisika SMA', 'Guru Biologi SMA', 'Kepala Laboratorium'],
            'Universitas Islam (STAI)' => ['Dosen Pengajar', 'Staff Akademik', 'Dekan Fakultas', 'Ketua Program Studi'],
        ];

        for ($i = 1; $i <= 30; $i++) {
            $isPria       = $i <= 15;
            $nama         = $isPria ? $namaPria[$i - 1] : $namaWanita[$i - 16];
            $jenisKelamin = $isPria ? 'Laki-laki' : 'Perempuan';
            $namaIbu      = $namaWanita[array_rand($namaWanita)];

            $pegawaiId = DB::table('data_pegawais')->insertGetId([
                'email'               => strtolower(str_replace([' ', '.'], '', $nama)) . $i . '@yayasanislam.or.id',
                'nip'                 => 'YPI' . date('Y') . str_pad($i, 4, '0', STR_PAD_LEFT),
                'nama_lengkap'        => $nama,
                'nik'                 => '3171' . rand(100000000000, 999999999999),
                'npwp'                => '09.' . rand(100, 999) . '.' . rand(100, 999) . '.' . rand(1, 9) . '-' . rand(100, 999) . '.000',
                'no_kk'               => '3171' . rand(100000000000, 999999999999),
                'no_hp'               => '08' . rand(11, 59) . rand(1000000, 9999999),
                'tempat_lahir'        => $tempatLahir[array_rand($tempatLahir)],
                'tanggal_lahir'       => Carbon::now()->subYears(rand(23, 50))->subDays(rand(1, 365))->format('Y-m-d'),
                'jenis_kelamin'       => $jenisKelamin,
                'agama'               => 'Islam',
                'golongan_darah'      => $golDarah[array_rand($golDarah)],
                'status_pernikahan'   => $statusNikah[array_rand($statusNikah)],
                'jumlah_tanggungan'   => rand(0, 3),
                'nama_ibu_kandung'    => $namaIbu,
                'pendidikan_terakhir' => $pendidikan[array_rand($pendidikan)],
                'alamat_ktp'          => 'Jl. Pendidikan Islam No. ' . $i . ', DKI Jakarta',
                'alamat_domisili'     => 'Jl. Pendidikan Islam No. ' . $i . ', DKI Jakarta',
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            $unit            = $unitKerjaList[array_rand($unitKerjaList)];
            $jabatan         = $jabatanList[$unit][array_rand($jabatanList[$unit])];
            $tglMasuk        = Carbon::now()->subMonths(rand(3, 60));
            $statusKerja     = ['Tetap', 'Kontrak', 'Probation', 'Magang'][rand(0, 3)];
            $tglHabisKontrak = ($statusKerja === 'Kontrak' || $statusKerja === 'Probation')
                ? Carbon::parse($tglMasuk)->addYear()->format('Y-m-d')
                : null;

            DB::table('kepegawaians')->insert([
                'data_pegawai_id'       => $pegawaiId,
                'jabatan'               => $jabatan,
                'jabatan_tambahan'      => rand(0, 1) ? 'Pembina Ekstrakurikuler' : null,
                'unit_kerja'            => $unit,
                'lokasi_kerja'          => 'Kampus Utama Yayasan',
                'tanggal_masuk'         => $tglMasuk->format('Y-m-d'),
                'status_rekrutmen'      => ($i == 30) ? 'Tidak Aktif' : 'Aktif',
                'status_kerja'          => $statusKerja,
                'tanggal_habis_kontrak' => $tglHabisKontrak,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            DB::table('kontak_darurats')->insert([
                'data_pegawai_id'  => $pegawaiId,
                'no_hp_kerabat'    => '08' . rand(11, 59) . rand(1000000, 9999999),
                'nama_kerabat'     => $namaWanita[array_rand($namaWanita)],
                'hubungan_kerabat' => ['Istri', 'Suami', 'Ibu Kandung', 'Kakak Kandung'][rand(0, 3)],
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::table('bpjs_pegawais')->insert([
                'data_pegawai_id'         => $pegawaiId,
                'no_bpjs_ketenagakerjaan' => '000' . rand(1000000000, 9999999999),
                'no_bpjs_kesehatan'       => '000' . rand(1000000000, 9999999999),
                'faskes_tingkat_1'        => 'Klinik Pratama Yayasan Islam',
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);
        }
    }
}
