<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Models\PengumumanPeserta;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use App\Support\AbsenAcara;
use App\Support\ExcelExporter;
use App\Support\Notifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Absen acara: monitoring & QR (SDM/Admin/GM) serta scan + kirim lokasi (peserta). */
class AbsenAcaraController extends Controller
{
    private const FILTER = [
        'semua' => 'Semua',
        'sudah' => 'Sudah absen',
        'belum' => 'Belum absen',
        'radius' => 'Ditolak: di luar radius',
        'kedaluwarsa' => 'Kedaluwarsa',
    ];

    // ================================================================ Pengelola

    public function pantau(Request $request, Pengumuman $pengumuman): View
    {
        $user = $this->pengelola($pengumuman);
        ['semua' => $semua, 'baris' => $baris, 'filter' => $filter, 'urut' => $urut] = $this->saring($request, $pengumuman);

        return view('pengumuman.absen', [
            'layout' => $user->panelLayout(),
            'pengumuman' => $pengumuman,
            'baris' => $baris,
            'ringkas' => AbsenAcara::ringkasan($pengumuman),
            'hitungFilter' => collect(self::FILTER)->map(fn ($l, $k) => $semua->filter(fn ($p) => $this->cocokFilter($p, $k))->count()),
            'filterLabel' => self::FILTER,
            'filter' => $filter,
            'urut' => $urut,
            'nama' => fn (PengumumanPeserta $p) => $this->nama($p),
            'unit' => fn (PengumumanPeserta $p) => $this->unit($p),
        ]);
    }

    /** Ekspor Excel daftar kehadiran sesuai filter/cari/urut yang sedang aktif + lembar ringkasan. */
    public function export(Request $request, Pengumuman $pengumuman): BinaryFileResponse
    {
        $this->pengelola($pengumuman);
        ['baris' => $baris, 'filter' => $filter] = $this->saring($request, $pengumuman);
        $r = AbsenAcara::ringkasan($pengumuman);

        $peserta = $baris->values()->map(function (PengumumanPeserta $p, int $i) {
            $gagal = $p->gagal_terakhir;

            return [
                $i + 1,
                $this->nama($p),
                $p->dataPegawai?->nip,
                $this->unit($p),
                $this->jabatan($p),
                $p->tampilan()['label'],
                $p->waktu_absen?->format('d-m-Y H:i'),
                $p->jarak,
                $p->akurasi,
                match (true) {
                    $p->status === PengumumanPeserta::STATUS_MANUAL => "Ditandai {$p->penanda?->name}: {$p->catatan}",
                    ! $p->sudahAbsen() && $gagal => 'Ditolak ' . \Carbon\Carbon::parse($gagal['waktu'])->format('H:i') . ": {$gagal['jarak']} m dari lokasi"
                        . (! empty($gagal['akurasi']) ? " (akurasi ±{$gagal['akurasi']} m)" : ''),
                    default => null,
                },
                $p->diingatkan_pada?->format('d-m-Y H:i'),
            ];
        });

        $ringkasan = [
            ['Acara', $pengumuman->judul],
            ['Waktu absen', $pengumuman->absenJendelaLabel()],
            ['Lokasi', $pengumuman->lokasi],
            ['Titik (lat, lng)', "{$pengumuman->absen_lat}, {$pengumuman->absen_lng}"],
            ['Radius (m)', $pengumuman->absen_radius],
            ['Diundang', $r['total']],
            ['Sudah absen', $r['sudah']],
            ['Belum absen', $r['belum']],
            ['Ditolak di luar radius (belum hadir)', $r['radius']],
            ['Persentase hadir', $r['total'] ? round($r['sudah'] / $r['total'] * 100) . '%' : '-'],
            ['Filter ekspor', self::FILTER[$filter] . ($request->filled('q') ? ' · cari "' . $request->query('q') . '"' : '')],
            ['Diekspor', now()->format('d-m-Y H:i') . ' oleh ' . Auth::user()->name],
        ];

        return ExcelExporter::downloadMultiSheet(ExcelExporter::namaFile('absen-acara-' . \Illuminate\Support\Str::slug($pengumuman->judul)), [
            ['name' => 'Kehadiran', 'headers' => ['No', 'Nama', 'NIP', 'Unit', 'Jabatan', 'Status', 'Waktu Absen', 'Jarak (m)', 'Akurasi GPS (m)', 'Keterangan', 'Diingatkan'], 'rows' => $peserta],
            ['name' => 'Ringkasan', 'headers' => ['Keterangan', 'Nilai'], 'rows' => $ringkasan],
        ]);
    }
    /** Layar QR untuk ditampilkan panitia di lokasi (proyektor / HP / tablet). */
    public function qr(Pengumuman $pengumuman): View
    {
        $this->pengelola($pengumuman);
        AbsenAcara::pastikanKunci($pengumuman);

        return view('pengumuman.absen-qr', ['pengumuman' => $pengumuman, 'langkah' => AbsenAcara::LANGKAH_QR]);
    }

    /** Token QR terbaru + angka kehadiran (dipanggil berkala oleh layar QR). */
    public function qrToken(Pengumuman $pengumuman): JsonResponse
    {
        $this->pengelola($pengumuman);
        AbsenAcara::pastikanKunci($pengumuman);

        return response()->json([
            'url' => AbsenAcara::urlScan($pengumuman),
            'sisa' => AbsenAcara::sisaDetik(),
            'fase' => $pengumuman->absenFase(),
            ...AbsenAcara::ringkasan($pengumuman),
        ]);
    }

    /** Tandai hadir manual (mis. HP mati / GPS bermasalah) atau batalkan kehadiran. */
    public function tandai(Request $request, Pengumuman $pengumuman, PengumumanPeserta $peserta): RedirectResponse
    {
        $user = $this->pengelola($pengumuman);
        abort_unless($peserta->pengumuman_id === $pengumuman->id, 404);

        $v = $request->validate([
            'aksi' => ['required', Rule::in(['hadir', 'batal'])],
            'catatan' => ['required_if:aksi,hadir', 'nullable', 'string', 'max:255'],
        ], ['catatan.required_if' => 'Isi alasan menandai hadir secara manual.']);

        if ($v['aksi'] === 'hadir') {
            if ($peserta->sudahAbsen()) {
                return back()->with('info', 'Pegawai ini sudah tercatat hadir.');
            }
            $peserta->update([
                'status' => PengumumanPeserta::STATUS_MANUAL, 'waktu_absen' => now(), 'catatan' => $v['catatan'],
                'ditandai_oleh' => $user->id, 'lat' => null, 'lng' => null, 'jarak' => null, 'akurasi' => null,
            ]);
            $pesan = 'ditandai hadir';
        } else {
            $peserta->update([
                'status' => PengumumanPeserta::STATUS_BELUM, 'waktu_absen' => null, 'catatan' => null, 'ditandai_oleh' => null,
                'lat' => null, 'lng' => null, 'jarak' => null, 'akurasi' => null,
            ]);
            $pesan = 'dibatalkan kehadirannya';
        }

        return back()->with('success', $this->nama($peserta->load('dataPegawai', 'user')) . " {$pesan}.");
    }

    /** Kirim pengingat ke peserta yang belum absen (lewati yang baru diingatkan < 15 menit). */
    public function ingatkan(Pengumuman $pengumuman): RedirectResponse
    {
        $this->pengelola($pengumuman);

        if ($pengumuman->absenFase() === Pengumuman::ABSEN_TUTUP) {
            return back()->with('error', 'Absen sudah ditutup — pengingat tidak dikirim.');
        }

        $target = $pengumuman->peserta()->with('user')->where('status', PengumumanPeserta::STATUS_BELUM)
            ->where(fn ($q) => $q->whereNull('diingatkan_pada')->orWhere('diingatkan_pada', '<', now()->subMinutes(15)))
            ->get();

        if ($target->isEmpty()) {
            return back()->with('info', 'Tidak ada yang perlu diingatkan (semua sudah absen atau baru saja diingatkan).');
        }

        $jumlah = Notifikasi::kirim($target->pluck('user')->filter(), "Pengingat absen: {$pengumuman->judul}",
            "Anda belum absen. Waktu absen: {$pengumuman->absenJendelaLabel()}. Scan QR di lokasi acara.",
            route('pengumuman.index') . '#pengumuman-' . $pengumuman->id, Pemberitahuan::JENIS_PENGINGAT, 'bi-qr-code-scan');
        PengumumanPeserta::whereIn('id', $target->pluck('id'))->update(['diingatkan_pada' => now()]);

        return back()->with('success', "Pengingat dikirim ke {$jumlah} pegawai.");
    }

    // ================================================================ Peserta

    /** Tujuan QR. Tamu diminta masuk dulu; setelah login otomatis kembali ke sini. */
    public function scan(Request $request, Pengumuman $pengumuman): View
    {
        abort_unless($pengumuman->absen_aktif, 404);

        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            $request->session()->put('acara_tujuan', $request->fullUrl());

            return view('pengumuman.absen-masuk', ['pengumuman' => $pengumuman]);
        }

        $keadaan = $this->keadaan($request, $pengumuman, $user, $request->query('t'));
        $peserta = $keadaan['peserta'];

        return view('pengumuman.absen-scan', [
            'layout' => $user->isPelamar() ? 'layouts.guest.app' : $user->panelLayout(),
            'pengumuman' => $pengumuman,
            'peserta' => $peserta,
            'keadaan' => $keadaan['kode'],
        ]);
    }

    public function simpan(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        abort_unless($pengumuman->absen_aktif, 404);
        $user = Auth::user();
        $keadaan = $this->keadaan($request, $pengumuman, $user, null);
        $kembali = redirect()->route('acara.absen', $pengumuman);

        if ($keadaan['kode'] !== 'lokasi') {
            return $kembali->with('error', match ($keadaan['kode']) {
                'sudah' => 'Anda sudah tercatat hadir.',
                'tutup' => 'Waktu absen sudah ditutup.',
                'belum_buka' => 'Absen belum dibuka.',
                'bukan_peserta' => 'Anda tidak termasuk peserta absen acara ini.',
                default => 'Scan ulang QR di lokasi acara terlebih dahulu.',
            });
        }

        $v = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'akurasi' => ['nullable', 'numeric', 'min:0'],
        ], ['lat.required' => 'Lokasi belum terbaca. Izinkan akses lokasi lalu coba lagi.']);

        $peserta = $keadaan['peserta'];
        $jarak = AbsenAcara::jarak((float) $v['lat'], (float) $v['lng'], $pengumuman->absen_lat, $pengumuman->absen_lng);
        $akurasi = isset($v['akurasi']) ? (int) round((float) $v['akurasi']) : null;

        if ($jarak > $pengumuman->absen_radius) {
            $peserta->update(['gagal_terakhir' => ['alasan' => 'radius', 'jarak' => $jarak, 'akurasi' => $akurasi, 'waktu' => now()->toDateTimeString()]]);
            $saran = $akurasi && $akurasi > $pengumuman->absen_radius
                ? " Akurasi GPS HP Anda rendah (±{$akurasi} m): nyalakan GPS / mode akurasi tinggi, tunggu sebentar di area terbuka, lalu coba lagi."
                : ' Pastikan Anda berada di lokasi acara, lalu coba lagi.';

            return $kembali->with('error', "Absen ditolak: Anda berjarak {$this->meter($jarak)} dari lokasi acara (maksimal {$this->meter($pengumuman->absen_radius)}).{$saran}");
        }

        $peserta->update([
            'status' => PengumumanPeserta::STATUS_HADIR, 'waktu_absen' => now(),
            'lat' => round((float) $v['lat'], 7), 'lng' => round((float) $v['lng'], 7), 'jarak' => $jarak, 'akurasi' => $akurasi,
            'gagal_terakhir' => null,
        ]);
        $request->session()->forget("acara_scan.{$pengumuman->id}");

        return redirect()->to(route('pengumuman.index') . '#pengumuman-' . $pengumuman->id)
            ->with('success', 'Absen berhasil pukul ' . now()->format('H:i') . " ({$this->meter($jarak)} dari lokasi acara).");
    }

    // ================================================================ Bantuan

    /**
     * Keadaan absen pengguna untuk acara ini.
     * Scan QR yang sah disimpan di sesi beberapa menit, jadi pegawai bisa mencoba kirim lokasi ulang tanpa scan lagi.
     *
     * @return array{kode: string, peserta: ?PengumumanPeserta}
     */
    private function keadaan(Request $request, Pengumuman $pengumuman, User $user, ?string $token): array
    {
        $peserta = $pengumuman->peserta()->where('user_id', $user->id)->first();
        $kunciSesi = "acara_scan.{$pengumuman->id}";

        if ($token !== null && $peserta && AbsenAcara::tokenSah($pengumuman, $token)) {
            $request->session()->put($kunciSesi, now()->getTimestamp());
        }
        $scanMasihBerlaku = now()->getTimestamp() - (int) $request->session()->get($kunciSesi, 0) <= AbsenAcara::MENIT_SCAN_BERLAKU * 60;

        $kode = match (true) {
            ! $peserta => 'bukan_peserta',
            $peserta->sudahAbsen() => 'sudah',
            $pengumuman->absenFase() === Pengumuman::ABSEN_BELUM_BUKA => 'belum_buka',
            $pengumuman->absenFase() === Pengumuman::ABSEN_TUTUP => 'tutup',
            $scanMasihBerlaku => 'lokasi',
            default => 'qr_kedaluwarsa',
        };

        return ['kode' => $kode, 'peserta' => $peserta];
    }

    /**
     * Peserta + filter status, pencarian, dan urutan (dipakai halaman monitoring & ekspor).
     *
     * @return array{semua: \Illuminate\Support\Collection, baris: \Illuminate\Support\Collection, filter: string, urut: string}
     */
    private function saring(Request $request, Pengumuman $pengumuman): array
    {
        $filter = array_key_exists($request->query('status'), self::FILTER) ? $request->query('status') : 'semua';
        $cari = mb_strtolower(trim((string) $request->query('q')));
        $urut = in_array($request->query('urut'), ['nama', 'waktu', 'status', 'jarak'], true) ? $request->query('urut') : 'status';

        $semua = $pengumuman->peserta()
            ->with(['user:id,name', 'dataPegawai:id,nama_lengkap,nip', 'dataPegawai.kepegawaian:id,data_pegawai_id,unit_kerja,jabatan', 'dataPegawai.penugasanAktif', 'penanda:id,name'])
            ->get()
            ->each(fn (PengumumanPeserta $p) => $p->setRelation('pengumuman', $pengumuman));

        $baris = $semua
            ->filter(fn (PengumumanPeserta $p) => $this->cocokFilter($p, $filter))
            ->filter(fn (PengumumanPeserta $p) => $cari === '' || str_contains(mb_strtolower($this->nama($p) . ' ' . $this->unit($p) . ' ' . $p->dataPegawai?->nip), $cari));

        // Bawaan: yang perlu perhatian dulu (ditolak radius → belum → sudah), lalu nama.
        $peringkat = ['radius' => 0, 'belum' => 1, 'kedaluwarsa' => 2, 'manual' => 3, 'valid' => 4];
        $baris = match ($urut) {
            'nama' => $baris->sortBy(fn ($p) => mb_strtolower($this->nama($p))),
            'waktu' => $baris->sortBy(fn ($p) => $p->waktu_absen?->getTimestamp() ?? PHP_INT_MAX),
            'jarak' => $baris->sortBy(fn ($p) => $p->jarak ?? $p->gagal_terakhir['jarak'] ?? PHP_INT_MAX),
            default => $baris->sortBy(fn ($p) => sprintf('%d-%s', $peringkat[$p->tampilan()['kunci']] ?? 9, mb_strtolower($this->nama($p)))),
        };

        return ['semua' => $semua, 'baris' => $baris->values(), 'filter' => $filter, 'urut' => $urut];
    }

    private function cocokFilter(PengumumanPeserta $p, string $filter): bool
    {
        return match ($filter) {
            'sudah' => $p->sudahAbsen(),
            'belum' => ! $p->sudahAbsen(),
            'radius', 'kedaluwarsa' => $p->tampilan()['kunci'] === $filter,
            default => true,
        };
    }

    private function jabatan(PengumumanPeserta $p): string
    {
        $tugas = $p->dataPegawai?->penugasanAktif;

        return ($tugas && $tugas->isNotEmpty() ? $tugas->pluck('jabatan')->filter()->unique()->implode(', ') : null)
            ?: ($p->dataPegawai?->kepegawaian?->jabatan ?? '-');
    }

    private function pengelola(Pengumuman $pengumuman): User
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless(AbsenAcara::bolehPantau($user, $pengumuman), 403);

        return $user;
    }

    private function nama(PengumumanPeserta $p): string
    {
        return $p->dataPegawai?->nama_lengkap ?: ($p->user?->name ?? '-');
    }

    private function unit(PengumumanPeserta $p): string
    {
        $tugas = $p->dataPegawai?->penugasanAktif;

        return ($tugas && $tugas->isNotEmpty() ? $tugas->pluck('unit_kerja')->unique()->implode(', ') : null)
            ?? $p->dataPegawai?->kepegawaian?->unit_kerja ?? '-';
    }

    private function meter(int $m): string
    {
        return $m >= 1000 ? number_format($m / 1000, 1, ',', '.') . ' km' : "{$m} m";
    }
}
