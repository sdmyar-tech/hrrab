<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class Pemberitahuan extends Notification
{
    public const JENIS_TUGAS = 'tugas';
    public const JENIS_INFO = 'info';
    public const JENIS_PENGINGAT = 'pengingat';

    public function __construct(
        public string $judul,
        public string $pesan,
        public ?string $url = null,
        public string $jenis = self::JENIS_INFO,
        public string $ikon = 'bi-bell',
        public ?string $pengirim = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'judul' => $this->judul,
            'pesan' => $this->pesan,
            'url' => $this->url,
            'jenis' => $this->jenis,
            'ikon' => $this->ikon,
            'pengirim' => $this->pengirim,
        ];
    }
}
