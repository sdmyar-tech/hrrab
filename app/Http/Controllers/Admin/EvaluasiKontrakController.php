<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluasiKontrak;
use App\Models\PengajuanCuti;
use App\Models\SuratPeringatan;
use App\Models\User;
use App\Support\Absensi\PenilaianAbsensi;
use App\Support\EvaluasiKontrakAlur;
use App\Support\Notifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Evaluasi kontrak: penilai memberi nilai (H-30), pemutus (Kepala SDM; GM untuk Kepala SDM) memutuskan
 * beserta jadwal & lokasi TTD kontrak. Penilai yang sekaligus pemutus mengisi keduanya sekali jalan.
 */
class EvaluasiKontrakController extends Controller
{
    public const TAB = ['tugas' => 'Perlu tindakan saya', 'menunggu' => 'Sedang berjalan', 'selesai' => 'Selesai', 'semua' => 'Semua'];

    public function index(Request $request): View
    {
        $user = $this->pengguna();
        abort_unless(EvaluasiKontrakAlur::bolehMembuka($user), 403, 'Anda tidak berwenang membuka evaluasi kontrak.');

        // Jaring pengaman bila penjadwal harian belum jalan: buat evaluasi yang sudah jatuh tempo (H-30).
        EvaluasiKontrakAlur::buatJatuhTempo();

        $semua = EvaluasiKontrak::with('dataPegawai.kepegawaian', 'pengisi')->get()
            ->filter(fn (EvaluasiKontrak $e) => EvaluasiKontrakAlur::bisaLihat($user, $e));
        $kelompok = [
            'tugas' => $semua->filter(fn ($e) => EvaluasiKontrakAlur::tugasUntuk($user, $e)),
            'menunggu' => $semua->where('status', '!=', EvaluasiKontrak::STATUS_SELESAI),
            'selesai' => $semua->where('status', EvaluasiKontrak::STATUS_SELESAI),
            'semua' => $semua,
        ];
        $tab = array_key_exists($request->query('tab'), self::TAB) ? $request->query('tab') : ($kelompok['tugas']->isNotEmpty() ? 'tugas' : 'menunggu');
        $kata = mb_strtolower(trim((string) $request->query('q')));

        $daftar = $kelompok[$tab]
            ->filter(fn ($e) => $kata === '' || str_contains(mb_strtolower(($e->dataPegawai?->nama_lengkap ?? '') . ' ' . $e->unit_kerja), $kata))
            // Yang berjalan: paling dekat habis dulu; yang selesai: terbaru dulu.
            ->sortBy(fn ($e) => $tab === 'selesai' ? -$e->diputuskan_pada?->timestamp : $e->tanggal_habis_kontrak->timestamp)
            ->values();

        return view('admin.evaluasi-kontrak.index', [
            'daftar' => $daftar,
            'tab' => $tab,
            'jumlahTab' => array_map(fn ($c) => $c->count(), $kelompok),
        ]);
    }

    public function show(EvaluasiKontrak $evaluasiKontrak): View
    {
        $user = $this->pengguna();
        $e = $evaluasiKontrak->load('dataPegawai.kepegawaian', 'pengisi', 'pemutus');
        abort_unless(EvaluasiKontrakAlur::bisaLihat($user, $e), 403, 'Anda tidak berwenang melihat evaluasi ini.');

        $pegawai = $e->dataPegawai;
        $k = $pegawai?->kepegawaian;
        // Data pendukung: absensi 6 bulan terakhir (sejak masuk bila lebih baru), cuti tahun ini, SP selama kontrak.
        $sampai = now()->subDay()->startOfDay();
        $dari = max(now()->subMonths(6)->startOfDay(), $k?->tanggal_masuk?->copy()->startOfDay() ?? now()->subMonths(6)->startOfDay());
        $absensi = $pegawai && $dari->lte($sampai)
            ? PenilaianAbsensi::rekap(PenilaianAbsensi::rentang(collect([$pegawai]), $dari, $sampai)[$pegawai->id] ?? [])
            : null;
        [$periodeMulai, $periodeSelesai] = $e->periode_mulai ? [$e->periode_mulai, $e->periode_selesai] : $e->periodeBawaan();

        return view('admin.evaluasi-kontrak.show', [
            'e' => $e,
            'absensi' => $absensi,
            'rentangAbsensi' => [$dari, $sampai],
            'periode' => [$periodeMulai, $periodeSelesai],
            'cutiTahunIni' => (int) PengajuanCuti::where('data_pegawai_id', $e->data_pegawai_id)->where('status', PengajuanCuti::STATUS_APPROVED)
                ->whereYear('tanggal_mulai', now()->year)->sum('lama_cuti'),
            // SP selama masa kontrak terakhir (periode penilaian).
            'spKontrak' => SuratPeringatan::with('pemberi')->where('data_pegawai_id', $e->data_pegawai_id)
                ->when($periodeMulai, fn ($q) => $q->whereDate('tanggal', '>=', $periodeMulai))
                ->whereDate('tanggal', '<=', $periodeSelesai ?? $e->tanggal_habis_kontrak)
                ->orderBy('tanggal')->orderBy('tingkat')->get(),
            'riwayat' => EvaluasiKontrak::where('data_pegawai_id', $e->data_pegawai_id)->whereKeyNot($e->id)->latest('tanggal_habis_kontrak')->get(),
            'penilai' => $pegawai ? EvaluasiKontrakAlur::penilai($pegawai, $e->unit_kerja) : collect(),
            'pemutus' => $pegawai ? EvaluasiKontrakAlur::pemutus($pegawai, $e->unit_kerja) : collect(),
            'bisaIsi' => EvaluasiKontrakAlur::bisaIsi($user, $e),
            'bisaPutuskan' => EvaluasiKontrakAlur::bisaPutuskan($user, $e),
            'satuLangkah' => EvaluasiKontrakAlur::satuLangkah($user, $e) && $e->status !== EvaluasiKontrak::STATUS_SELESAI,
            'bisaAturTtd' => EvaluasiKontrakAlur::bisaAturTtd($user, $e),
        ]);
    }

    /** Penilai memberi nilai 50 butir (+ catatan). Bila sekaligus pemutus, keputusan & jadwal TTD ikut diisi. */
    public function isi(Request $request, EvaluasiKontrak $evaluasiKontrak): RedirectResponse
    {
        $user = $this->pengguna();
        $e = $evaluasiKontrak->load('dataPegawai.kepegawaian');
        abort_unless(EvaluasiKontrakAlur::bisaIsi($user, $e), 403, 'Hanya penilai pegawai ini yang dapat mengisi evaluasi.');
        $satuLangkah = EvaluasiKontrakAlur::satuLangkah($user, $e);

        $butir = EvaluasiKontrak::butir();
        $data = $request->validate([
            'nilai' => ['required', 'array'],
            'nilai.*' => ['integer', Rule::in(array_keys(config('penilaian_kontrak.skala')))],
            'periode_mulai' => ['nullable', 'date'],
            'periode_selesai' => ['nullable', 'date', 'after_or_equal:periode_mulai'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'nilai.required' => 'Isi penilaian setiap butir.',
            'nilai.*.in' => 'Nilai hanya 1–4.',
            'periode_selesai.after_or_equal' => 'Akhir periode penilaian tidak boleh sebelum awalnya.',
        ]);

        // Semua butir wajib dinilai (butir di luar formulir diabaikan).
        $nilai = collect($data['nilai'])->only(array_keys($butir))->map(fn ($n) => (int) $n);
        $kosong = array_diff(array_keys($butir), $nilai->keys()->all());
        if ($kosong) {
            throw ValidationException::withMessages(['nilai' => 'Masih ada ' . count($kosong) . ' butir belum dinilai (no. ' . implode(', ', array_slice($kosong, 0, 10)) . (count($kosong) > 10 ? ', …' : '') . ').']);
        }
        $keputusan = $satuLangkah ? $this->validasiKeputusan($request) : null;

        $baruDiisi = $e->status === EvaluasiKontrak::STATUS_MENUNGGU_ATASAN;
        $e->update([
            'nilai' => $nilai->sortKeys()->all(),
            'total_nilai' => $nilai->sum(),
            'rata_nilai' => round($nilai->avg(), 2),
            'periode_mulai' => $data['periode_mulai'] ?? null,
            'periode_selesai' => $data['periode_selesai'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            'diisi_oleh' => $user->id,
            'diisi_pada' => now(),
            'status' => $baruDiisi ? EvaluasiKontrak::STATUS_MENUNGGU_SDM : $e->status,
        ]);

        if ($keputusan) {
            return redirect()->route('admin.evaluasi-kontrak.show', $e)
                ->with('success', 'Penilaian & keputusan tersimpan. ' . $this->simpanKeputusan($e, $keputusan, $user));
        }
        if ($baruDiisi) {
            Notifikasi::evaluasiKontrakDiisi($e);
        }

        return redirect()->route('admin.evaluasi-kontrak.show', $e)
            ->with('success', $baruDiisi ? 'Penilaian terkirim. Menunggu keputusan ' . ($e->dataPegawai && EvaluasiKontrakAlur::pemutus($e->dataPegawai)->every->isGm() ? 'GM.' : 'Kepala SDM.') : 'Penilaian diperbarui.');
    }

    /** Pemutus (Kepala SDM; GM untuk Kepala SDM) atau Admin memutuskan; data kontrak & jadwal TTD diperbarui. */
    public function putuskan(Request $request, EvaluasiKontrak $evaluasiKontrak): RedirectResponse
    {
        $user = $this->pengguna();
        $e = $evaluasiKontrak->load('dataPegawai.kepegawaian');
        abort_unless(EvaluasiKontrakAlur::bisaPutuskan($user, $e), 403, 'Keputusan diambil Kepala SDM (GM untuk kontrak Kepala SDM) setelah penilaian masuk.');

        return redirect()->route('admin.evaluasi-kontrak.show', $e)
            ->with('success', 'Keputusan tersimpan. ' . $this->simpanKeputusan($e, $this->validasiKeputusan($request), $user));
    }

    /** Atur / ubah jadwal & lokasi TTD kontrak setelah diputuskan; pegawai dikabari ulang. */
    public function jadwalTtd(Request $request, EvaluasiKontrak $evaluasiKontrak): RedirectResponse
    {
        $user = $this->pengguna();
        $e = $evaluasiKontrak->load('dataPegawai.kepegawaian');
        abort_unless(EvaluasiKontrakAlur::bisaAturTtd($user, $e), 403, 'Jadwal TTD diatur pemutus setelah keputusan perpanjang / angkat tetap.');

        $data = $request->validate([
            'ttd_jadwal' => ['required', 'date'],
            'ttd_lokasi' => ['required', 'string', 'max:150'],
        ], ['ttd_jadwal.required' => 'Isi tanggal & jam tanda tangan kontrak.', 'ttd_lokasi.required' => 'Isi lokasi tanda tangan kontrak.']);
        $e->update($data);
        Notifikasi::evaluasiKontrakJadwalTtd($e);

        return redirect()->route('admin.evaluasi-kontrak.show', $e)->with('success', 'Jadwal TTD kontrak tersimpan: ' . $e->labelTtd() . '.');
    }

    /** Validasi isian keputusan (dipakai form keputusan & form sekali isi). */
    private function validasiKeputusan(Request $request): array
    {
        // Nominal boleh diketik "Rp 300.000" / "300.000": simpan angkanya saja.
        if ($request->filled('keputusan_naik_gaji_nominal')) {
            $request->merge(['keputusan_naik_gaji_nominal' => preg_replace('/\D/', '', (string) $request->input('keputusan_naik_gaji_nominal'))]);
        }
        $tidak = $request->input('keputusan') === EvaluasiKontrak::TIDAK;
        $data = $request->validate([
            'keputusan' => ['required', Rule::in(array_keys(EvaluasiKontrak::PILIHAN))],
            'keputusan_lama_bulan' => ['nullable', 'required_if:keputusan,' . EvaluasiKontrak::PERPANJANG, 'integer', Rule::in(EvaluasiKontrak::LAMA_BULAN)],
            // Naik gaji: hanya catatan nominal (tidak terhubung ke penggajian).
            'keputusan_naik_gaji' => ['nullable', 'boolean'],
            'keputusan_naik_gaji_nominal' => ['nullable', 'required_if_accepted:keputusan_naik_gaji', 'integer', 'min:1', 'max:100000000'],
            'catatan_sdm' => ['nullable', 'required_if:keputusan,' . EvaluasiKontrak::TIDAK, 'string', 'max:1000'],
            // Perpanjang / angkat tetap: jadwal & lokasi tanda tangan kontrak wajib.
            'ttd_jadwal' => [$tidak ? 'nullable' : 'required', 'date'],
            'ttd_lokasi' => [$tidak ? 'nullable' : 'required', 'string', 'max:150'],
        ], [
            'keputusan.required' => 'Pilih keputusan.',
            'keputusan_lama_bulan.required_if' => 'Pilih lama perpanjangan.',
            'keputusan_naik_gaji_nominal.required_if_accepted' => 'Isi nominal kenaikan gaji.',
            'catatan_sdm.required_if' => 'Tulis alasan bila kontrak tidak diperpanjang.',
            'ttd_jadwal.required' => 'Isi tanggal & jam tanda tangan kontrak.',
            'ttd_lokasi.required' => 'Isi lokasi tanda tangan kontrak.',
        ]);
        $naik = ! $tidak && $request->boolean('keputusan_naik_gaji');

        return [
            'keputusan' => $data['keputusan'],
            'keputusan_lama_bulan' => $data['keputusan'] === EvaluasiKontrak::PERPANJANG ? (int) $data['keputusan_lama_bulan'] : null,
            'keputusan_naik_gaji' => $naik,
            'keputusan_naik_gaji_nominal' => $naik ? (int) $data['keputusan_naik_gaji_nominal'] : null,
            'catatan_sdm' => $data['catatan_sdm'] ?? null,
            'ttd_jadwal' => $tidak ? null : $data['ttd_jadwal'],
            'ttd_lokasi' => $tidak ? null : $data['ttd_lokasi'],
        ];
    }

    /** Simpan keputusan, terapkan ke data kontrak, kabari penilai & pegawai. @return string ringkasan hasil */
    private function simpanKeputusan(EvaluasiKontrak $e, array $keputusan, User $user): string
    {
        $e->update([...$keputusan, 'diputuskan_oleh' => $user->id, 'diputuskan_pada' => now(), 'status' => EvaluasiKontrak::STATUS_SELESAI]);
        $hasil = EvaluasiKontrakAlur::terapkan($e);
        Notifikasi::evaluasiKontrakDiputuskan($e);

        return $hasil . ($e->labelTtd() ? ' TTD kontrak: ' . $e->labelTtd() . '.' : '');
    }

    private function pengguna(): User
    {
        /** @var User */
        return Auth::user();
    }
}
