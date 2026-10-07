<?php

use App\Models\Lowongan;
use App\Support\PenugasanService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lowongan:tutup-kedaluwarsa', function () {
    $jumlah = Lowongan::tutupYangKedaluwarsa();
    $this->info("{$jumlah} lowongan yang lewat tanggal tutup telah ditutup.");
})->purpose('Tutup otomatis lowongan yang sudah lewat tanggal tutup');

Schedule::command('lowongan:tutup-kedaluwarsa')->dailyAt('00:01');

Artisan::command('penugasan:sinkron', function () {
    $jumlah = PenugasanService::sinkronTerjadwal();
    $this->info("Penugasan disinkronkan untuk {$jumlah} pegawai.");
})->purpose('Berlakukan mutasi/rangkap tugas terjadwal (unit kerja, role kepala, interviewer tahap 2)');

Schedule::command('penugasan:sinkron')->dailyAt('00:02');

Artisan::command('rekrutmen:pengingat', function () {
    $hasil = App\Support\Notifikasi::pengingatRekrutmen();
    $this->info("Pengingat terkirim — onboarding: {$hasil['onboarding']}, interview: {$hasil['interview']}, kontrak: {$hasil['kontrak']}.");
})->purpose('Ingatkan konfirmasi onboarding/interview/kontrak yang tanggalnya sudah sampai atau lewat');

Schedule::command('rekrutmen:pengingat')->weekdays()->at('07:30');
