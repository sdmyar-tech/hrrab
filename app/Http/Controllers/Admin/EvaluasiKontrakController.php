<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EvaluasiKontrak;
use App\Models\PengajuanCuti;
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

/** Evaluasi kontrak: atasan mengisi rekomendasi + penilaian (H-30), Kepala SDM memutuskan. */
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
        // Data pendukung: absensi 6 bulan terakhir (sejak masuk bila lebih baru), cuti tahun ini, evaluasi sebelumnya.
        $sampai = now()->subDay()->startOfDay();
        $dari = max(now()->subMonths(6)->startOfDay(), $k?->tanggal_masuk?->copy()->startOfDay() ?? now()->subMonths(6)->startOfDay());
        $absensi = $pegawai && $dari->lte($sampai)
            ? PenilaianAbsensi::rekap(PenilaianAbsensi::rentang(collect([$pegawai]), $dari, $sampai)[$pegawai->id] ?? [])
            : null;

        return view('admin.evaluasi-kontrak.show', [
            'e' => $e,
            'absensi' => $absensi,
            'rentangAbsensi' => [$dari, $sampai],
            'cutiTahunIni' => (int) PengajuanCuti::where('data_pegawai_id', $e->data_pegawai_id)->where('status', PengajuanCuti::STATUS_APPROVED)
                ->whereYear('tanggal_mulai', now()->year)->sum('lama_cuti'),
            'riwayat' => EvaluasiKontrak::where('data_pegawai_id', $e->data_pegawai_id)->whereKeyNot($e->id)->latest('tanggal_habis_kontrak')->get(),
            'atasan' => $pegawai ? EvaluasiKontrakAlur::atasan($pegawai, $e->unit_kerja) : collect(),
            'bisaIsi' => EvaluasiKontrakAlur::bisaIsi($user, $e),
            'bisaPutuskan' => EvaluasiKontrakAlur::bisaPutuskan($user, $e),
        ]);
    }

    /** Atasan mengisi / mengubah rekomendasi & penilaian. */
    public function isi(Request $request, EvaluasiKontrak $evaluasiKontrak): RedirectResponse
    {
        $user = $this->pengguna();
        $e = $evaluasiKontrak;
        abort_unless(EvaluasiKontrakAlur::bisaIsi($user, $e), 403, 'Hanya atasan pegawai ini yang dapat mengisi evaluasi.');

        $butir = EvaluasiKontrak::butir();
        $data = $request->validate([
            'rekomendasi' => ['required', Rule::in(array_keys(EvaluasiKontrak::PILIHAN))],
            'lama_bulan' => ['nullable', 'required_if:rekomendasi,' . EvaluasiKontrak::PERPANJANG, 'integer', Rule::in(EvaluasiKontrak::LAMA_BULAN)],
            'nilai' => ['required', 'array'],
            'nilai.*' => ['integer', Rule::in(array_keys(config('penilaian_kontrak.skala')))],
            'periode_mulai' => ['nullable', 'date'],
            'periode_selesai' => ['nullable', 'date', 'after_or_equal:periode_mulai'],
            'catatan' => ['nullable', 'required_if:rekomendasi,' . EvaluasiKontrak::TIDAK, 'string', 'max:1000'],
        ], [
            'rekomendasi.required' => 'Pilih rekomendasi.',
            'lama_bulan.required_if' => 'Pilih lama perpanjangan.',
            'nilai.required' => 'Isi penilaian setiap butir.',
            'nilai.*.in' => 'Nilai hanya 1–4.',
            'periode_selesai.after_or_equal' => 'Akhir periode penilaian tidak boleh sebelum awalnya.',
            'catatan.required_if' => 'Tulis alasan bila kontrak tidak diperpanjang.',
        ]);

        // Semua butir wajib dinilai (butir di luar formulir diabaikan).
        $nilai = collect($data['nilai'])->only(array_keys($butir))->map(fn ($n) => (int) $n);
        $kosong = array_diff(array_keys($butir), $nilai->keys()->all());
        if ($kosong) {
            throw ValidationException::withMessages(['nilai' => 'Masih ada ' . count($kosong) . ' butir belum dinilai (no. ' . implode(', ', array_slice($kosong, 0, 10)) . (count($kosong) > 10 ? ', …' : '') . ').']);
        }

        // Kepala SDM yang menjadi atasan (mis. untuk staf SDM): rekomendasinya langsung menjadi keputusan.
        $langsungTetapkan = $user->isKepalaSdm();
        $baruDiisi = $e->status === EvaluasiKontrak::STATUS_MENUNGGU_ATASAN;
        $lama = $data['rekomendasi'] === EvaluasiKontrak::PERPANJANG ? (int) $data['lama_bulan'] : null;
        $e->update([
            'rekomendasi' => $data['rekomendasi'],
            'lama_bulan' => $lama,
            'nilai' => $nilai->sortKeys()->all(),
            'total_nilai' => $nilai->sum(),
            'rata_nilai' => round($nilai->avg(), 2),
            'periode_mulai' => $data['periode_mulai'] ?? null,
            'periode_selesai' => $data['periode_selesai'] ?? null,
            'catatan' => $data['catatan'] ?? null,
            'diisi_oleh' => $user->id,
            'diisi_pada' => now(),
            'status' => $langsungTetapkan ? EvaluasiKontrak::STATUS_SELESAI : ($baruDiisi ? EvaluasiKontrak::STATUS_MENUNGGU_SDM : $e->status),
            ...($langsungTetapkan ? [
                'keputusan' => $data['rekomendasi'],
                'keputusan_lama_bulan' => $lama,
                'diputuskan_oleh' => $user->id,
                'diputuskan_pada' => now(),
            ] : []),
        ]);

        if ($langsungTetapkan) {
            return redirect()->route('admin.evaluasi-kontrak.show', $e)
                ->with('success', 'Evaluasi tersimpan dan langsung ditetapkan (Anda Kepala SDM). ' . EvaluasiKontrakAlur::terapkan($e->load('dataPegawai.kepegawaian')));
        }
        if ($baruDiisi) {
            Notifikasi::evaluasiKontrakDiisi($e);
        }

        return redirect()->route('admin.evaluasi-kontrak.show', $e)
            ->with('success', $baruDiisi ? 'Evaluasi terkirim. Menunggu keputusan Kepala SDM.' : 'Evaluasi diperbarui.');
    }

    /** Kepala SDM (atau Admin) memutuskan; data kontrak pegawai ikut diperbarui. */
    public function putuskan(Request $request, EvaluasiKontrak $evaluasiKontrak): RedirectResponse
    {
        $user = $this->pengguna();
        $e = $evaluasiKontrak->load('dataPegawai.kepegawaian');
        abort_unless(EvaluasiKontrakAlur::bisaPutuskan($user, $e), 403, 'Keputusan evaluasi kontrak diambil Kepala SDM setelah atasan mengisi.');

        $data = $request->validate([
            'keputusan' => ['required', Rule::in(array_keys(EvaluasiKontrak::PILIHAN))],
            'keputusan_lama_bulan' => ['nullable', 'required_if:keputusan,' . EvaluasiKontrak::PERPANJANG, 'integer', Rule::in(EvaluasiKontrak::LAMA_BULAN)],
            'catatan_sdm' => ['nullable', 'string', 'max:1000'],
        ], [
            'keputusan.required' => 'Pilih keputusan.',
            'keputusan_lama_bulan.required_if' => 'Pilih lama perpanjangan.',
        ]);

        $e->update([
            ...$data,
            'keputusan_lama_bulan' => $data['keputusan'] === EvaluasiKontrak::PERPANJANG ? $data['keputusan_lama_bulan'] : null,
            'diputuskan_oleh' => $user->id,
            'diputuskan_pada' => now(),
            'status' => EvaluasiKontrak::STATUS_SELESAI,
        ]);
        $hasil = EvaluasiKontrakAlur::terapkan($e);
        Notifikasi::evaluasiKontrakDiputuskan($e);

        return redirect()->route('admin.evaluasi-kontrak.show', $e)->with('success', 'Keputusan tersimpan. ' . $hasil);
    }

    private function pengguna(): User
    {
        /** @var User */
        return Auth::user();
    }
}
