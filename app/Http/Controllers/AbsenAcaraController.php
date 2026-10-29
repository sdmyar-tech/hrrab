<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Models\PengumumanPeserta;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use App\Support\AbsenAcara;
use App\Support\ExcelExporter;
use App\Support\Notifikasi;
use App\Support\TugasNotifikasi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Absen acara: monitoring & QR (SDM/Admin/GM) serta scan + konfirmasi hadir (peserta). */
class AbsenAcaraController extends Controller
{
    private const FILTER = [
        'semua' => 'Semua',
        'sudah' => 'Sudah absen',
        'belum' => 'Belum absen',
        'berhalangan' => 'Izin / sakit / dinas / cuti',
        'manual' => 'Ditandai manual',
        'kedaluwarsa' => 'Kedaluwarsa',
    ];

    // ================================================================ Pengelola

    public function pantau(Request $request, Pengumuman $pengumuman): View
    {
        $user = $this->pengelola($pengumuman);
        AbsenAcara::sinkronBerhalangan($pengumuman);
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
            'bisaDitetapkan' => PengumumanPeserta::BISA_DITETAPKAN,
        ]);
    }

    /** Ekspor Excel daftar kehadiran sesuai filter/cari/urut yang sedang aktif + lembar ringkasan. */
    public function export(Request $request, Pengumuman $pengumuman): BinaryFileResponse
    {
        $this->pengelola($pengumuman);
        AbsenAcara::sinkronBerhalangan($pengumuman);
        ['baris' => $baris, 'filter' => $filter] = $this->saring($request, $pengumuman);
        $r = AbsenAcara::ringkasan($pengumuman);

        $peserta = $baris->values()->map(fn (PengumumanPeserta $p, int $i) => [
            $i + 1,
            $this->nama($p),
            $p->dataPegawai?->nip,
            $this->unit($p),
            $this->jabatan($p),
            $p->tampilan()['label'],
            $p->waktu_absen?->format('d-m-Y H:i'),
            match (true) {
                $p->status === PengumumanPeserta::STATUS_MANUAL => "Ditandai {$p->penanda?->name}: {$p->catatan}",
                $p->berhalangan() => trim($p->catatan . ' (' . $p->sumberLabel() . ')'),
                default => null,
            },
            $p->diingatkan_pada?->format('d-m-Y H:i'),
        ]);

        $ringkasan = [
            ['Acara', $pengumuman->judul],
            ['Waktu absen', $pengumuman->absenJendelaLabel()],
            ['Tempat', $pengumuman->lokasi],
            ['Diundang', $r['total']],
            ['Sudah absen', $r['sudah']],
            ['  - di antaranya ditandai manual', $r['manual']],
            ['Berhalangan', $r['berhalangan']],
            ['  - izin', $r['izin']],
            ['  - sakit', $r['sakit']],
            ['  - dinas luar', $r['dinas']],
            ['  - cuti', $r['cuti']],
            ['Belum absen', $r['belum']],
            ['Persentase hadir', $r['total'] ? round($r['sudah'] / $r['total'] * 100) . '%' : '-'],
            ['Filter ekspor', self::FILTER[$filter] . ($request->filled('q') ? ' · cari "' . $request->query('q') . '"' : '')],
            ['Diekspor', now()->format('d-m-Y H:i') . ' oleh ' . Auth::user()->name],
        ];

        return ExcelExporter::downloadMultiSheet(ExcelExporter::namaFile('absen-acara-' . Str::slug($pengumuman->judul)), [
            ['name' => 'Kehadiran', 'headers' => ['No', 'Nama', 'NIP', 'Bagian', 'Jabatan', 'Status', 'Waktu Absen', 'Keterangan', 'Diingatkan'], 'rows' => $peserta],
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
        AbsenAcara::sinkronBerhalangan($pengumuman);

        return response()->json([
            'url' => AbsenAcara::urlScan($pengumuman),
            'sisa' => AbsenAcara::sisaDetik(),
            'fase' => $pengumuman->absenFase(),
            ...AbsenAcara::ringkasan($pengumuman),
        ]);
    }

    /**
     * Tetapkan status peserta oleh panitia: hadir (mis. HP mati), izin, sakit, dinas luar — atau batalkan.
     * Status cuti tidak ditetapkan di sini: selalu otomatis dari pengajuan cuti yang disetujui.
     */
    public function tandai(Request $request, Pengumuman $pengumuman, PengumumanPeserta $peserta): RedirectResponse
    {
        $user = $this->pengelola($pengumuman);
        abort_unless($peserta->pengumuman_id === $pengumuman->id, 404);

        $v = $request->validate([
            'aksi' => ['required', Rule::in(['hadir', 'batal', ...array_keys(PengumumanPeserta::BISA_DITETAPKAN)])],
            'catatan' => ['required_unless:aksi,batal', 'nullable', 'string', 'max:255'],
        ], ['catatan.required_unless' => 'Isi alasan / keterangan terlebih dahulu.']);
        $nama = $this->nama($peserta->loadMissing('dataPegawai', 'user'));

        if ($v['aksi'] === 'batal') {
            if ($peserta->status === PengumumanPeserta::STATUS_CUTI && $peserta->otomatis()) {
                return back()->with('info', "{$nama} tercatat cuti dari data cuti yang disetujui. Ubah/batalkan pengajuan cutinya bila keliru.");
            }
            $tadinyaHadir = $peserta->sudahAbsen();
            $peserta->update(['status' => PengumumanPeserta::STATUS_BELUM, 'sumber' => null, 'sumber_id' => null,
                'waktu_absen' => null, 'catatan' => null, 'ditandai_oleh' => null]);

            return back()->with('success', $tadinyaHadir
                ? "{$nama} dibatalkan kehadirannya."
                : "Status {$nama} dikembalikan menjadi belum absen.");
        }

        if ($v['aksi'] === 'hadir') {
            if ($peserta->sudahAbsen()) {
                return back()->with('info', 'Pegawai ini sudah tercatat hadir.');
            }
            $peserta->update(['status' => PengumumanPeserta::STATUS_MANUAL, 'sumber' => PengumumanPeserta::SUMBER_MANUAL, 'sumber_id' => null,
                'waktu_absen' => now(), 'catatan' => $v['catatan'], 'ditandai_oleh' => $user->id]);

            return back()->with('success', "{$nama} ditandai hadir.");
        }

        // Izin / sakit / dinas luar oleh panitia (menimpa status otomatis, tidak ditimpa sinkron berikutnya).
        $peserta->update(['status' => $v['aksi'], 'sumber' => PengumumanPeserta::SUMBER_MANUAL, 'sumber_id' => null,
            'waktu_absen' => null, 'catatan' => $v['catatan'], 'ditandai_oleh' => $user->id]);

        return back()->with('success', "{$nama} ditetapkan " . mb_strtolower(PengumumanPeserta::BISA_DITETAPKAN[$v['aksi']]) . '.');
    }

    /** Kirim pengingat ke peserta yang belum absen (lewati yang baru diingatkan < 15 menit). */
    public function ingatkan(Pengumuman $pengumuman): RedirectResponse
    {
        $this->pengelola($pengumuman);
        AbsenAcara::sinkronBerhalangan($pengumuman); // yang cuti / izin di absensi harian tidak ikut diingatkan

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
            route('pengumuman.index') . '#pengumuman-' . $pengumuman->id, Pemberitahuan::JENIS_PENGINGAT, 'bi-qr-code-scan',
            null, TugasNotifikasi::penanda(TugasNotifikasi::ABSEN_ACARA, $pengumuman->id));
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

            // Tombol ke halaman login hanya untuk yang memegang QR sah (sedang di lokasi acara),
            // sehingga alamat login staf tidak terbuka bagi tautan lama/sembarang.
            return view('pengumuman.absen-masuk', [
                'pengumuman' => $pengumuman,
                'urlMasuk' => AbsenAcara::tokenSah($pengumuman, $request->query('t')) ? route('login') : null,
            ]);
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

        if ($keadaan['kode'] !== 'siap') {
            return $kembali->with('error', match ($keadaan['kode']) {
                'sudah' => 'Anda sudah tercatat hadir.',
                'tutup' => 'Waktu absen sudah ditutup.',
                'belum_buka' => 'Absen belum dibuka.',
                'bukan_peserta' => 'Anda tidak termasuk peserta absen acara ini.',
                default => 'Scan ulang QR di lokasi acara terlebih dahulu.',
            });
        }

        // Peserta yang tercatat izin/sakit/dinas/cuti tetap boleh datang: scan mengubahnya menjadi hadir.
        $keadaan['peserta']->update(['status' => PengumumanPeserta::STATUS_HADIR, 'waktu_absen' => now(),
            'sumber' => null, 'sumber_id' => null, 'catatan' => null, 'ditandai_oleh' => null]);
        $request->session()->forget("acara_scan.{$pengumuman->id}");

        return redirect()->to(route('pengumuman.index') . '#pengumuman-' . $pengumuman->id)
            ->with('success', 'Absen berhasil pukul ' . now()->format('H:i') . '.');
    }

    // ================================================================ Bantuan

    /**
     * Keadaan absen pengguna untuk acara ini.
     * Scan QR yang sah disimpan di sesi beberapa menit (cukup untuk login dulu lalu menekan konfirmasi).
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
            $scanMasihBerlaku => 'siap',
            default => 'qr_kedaluwarsa',
        };

        return ['kode' => $kode, 'peserta' => $peserta];
    }

    /**
     * Peserta + filter status, pencarian, dan urutan (dipakai halaman monitoring & ekspor).
     *
     * @return array{semua: Collection, baris: Collection, filter: string, urut: string}
     */
    private function saring(Request $request, Pengumuman $pengumuman): array
    {
        $filter = array_key_exists($request->query('status'), self::FILTER) ? $request->query('status') : 'semua';
        $cari = mb_strtolower(trim((string) $request->query('q')));
        $urut = in_array($request->query('urut'), ['nama', 'waktu', 'status'], true) ? $request->query('urut') : 'status';

        $semua = $pengumuman->peserta()
            ->with(['user:id,name', 'dataPegawai:id,nama_lengkap,nip', 'dataPegawai.kepegawaian:id,data_pegawai_id,unit_kerja,jabatan', 'dataPegawai.penugasanAktif', 'penanda:id,name'])
            ->get()
            ->each(fn (PengumumanPeserta $p) => $p->setRelation('pengumuman', $pengumuman));

        $baris = $semua
            ->filter(fn (PengumumanPeserta $p) => $this->cocokFilter($p, $filter))
            ->filter(fn (PengumumanPeserta $p) => $cari === '' || str_contains(mb_strtolower($this->nama($p) . ' ' . $this->unit($p) . ' ' . $p->dataPegawai?->nip), $cari));

        // Bawaan: yang perlu perhatian dulu (belum → kedaluwarsa → berhalangan → sudah), lalu nama.
        $peringkat = ['belum' => 0, 'kedaluwarsa' => 1, 'izin' => 2, 'sakit' => 2, 'dinas' => 2, 'cuti' => 2, 'manual' => 3, 'valid' => 4];
        $baris = match ($urut) {
            'nama' => $baris->sortBy(fn ($p) => mb_strtolower($this->nama($p))),
            'waktu' => $baris->sortBy(fn ($p) => $p->waktu_absen?->getTimestamp() ?? PHP_INT_MAX),
            default => $baris->sortBy(fn ($p) => sprintf('%d-%s', $peringkat[$p->tampilan()['kunci']] ?? 9, mb_strtolower($this->nama($p)))),
        };

        return ['semua' => $semua, 'baris' => $baris->values(), 'filter' => $filter, 'urut' => $urut];
    }

    private function cocokFilter(PengumumanPeserta $p, string $filter): bool
    {
        return match ($filter) {
            'sudah' => $p->sudahAbsen(),
            'belum' => $p->status === PengumumanPeserta::STATUS_BELUM,
            'berhalangan' => $p->berhalangan(),
            'manual', 'kedaluwarsa' => $p->tampilan()['kunci'] === $filter,
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
}
