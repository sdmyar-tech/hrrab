<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataPegawai;
use App\Models\KontrakPegawai;
use App\Models\User;
use App\Support\ExcelExporter;
use App\Support\RiwayatKontrak;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Riwayat kontrak pegawai (input manual untuk data lama): Masa Training → PKWT 1 → PKWT 2 … → PKWTT.
 * Daftar semua pegawai + halaman per pegawai (tambah banyak baris sekaligus, ubah, hapus).
 */
class RiwayatKontrakController extends Controller
{
    public const TAB = ['semua' => 'Semua', 'belum' => 'Belum ada riwayat', 'sudah' => 'Sudah ada riwayat'];

    public function index(Request $request): View
    {
        $tab = array_key_exists($request->query('tab'), self::TAB) ? $request->query('tab') : 'semua';

        return view('admin.riwayat-kontrak.index', [
            'pegawais' => $this->daftar($request, $tab, true)->paginate(20)->withQueryString(),
            'tab' => $tab,
            'jumlahTab' => [
                'semua' => $this->daftar($request, 'semua')->count(),
                'belum' => $this->daftar($request, 'belum')->count(),
                'sudah' => $this->daftar($request, 'sudah')->count(),
            ],
            'unitOptions' => $this->lingkupUnit() ?? config('kepegawaian.unit_kerja'),
            'bolehUbah' => Auth::user()->can('pegawai.update'),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $semua = KontrakPegawai::with('dataPegawai.kepegawaian')
            ->whereIn('data_pegawai_id', $this->daftar($request, 'semua')->select('data_pegawais.id'))
            ->orderBy('data_pegawai_id')->orderBy('tanggal_mulai')->get();

        return ExcelExporter::download(ExcelExporter::namaFile('riwayat-kontrak'),
            ['Pegawai', 'NIP', 'Bagian', 'Jenis', 'Mulai', 'Selesai', 'Nomor Kontrak', 'Keterangan'],
            $semua->map(fn (KontrakPegawai $k) => [
                $k->dataPegawai?->nama_lengkap, $k->dataPegawai?->nip, $k->dataPegawai?->kepegawaian?->unit_kerja, $k->jenis,
                $k->tanggal_mulai->format('d-m-Y'), $k->tanggal_selesai?->format('d-m-Y'), $k->nomor_kontrak, $k->keterangan,
            ]), 'Riwayat Kontrak');
    }

    public function show(DataPegawai $pegawai): View
    {
        $this->pastikanBolehLihat($pegawai);
        $pegawai->load('kepegawaian');
        $kontrak = $pegawai->kontrak()->get();
        $berlaku = $kontrak->first(fn (KontrakPegawai $k) => $k->berlaku());

        return view('admin.riwayat-kontrak.show', [
            'pegawai' => $pegawai,
            'kontrak' => $kontrak,
            'jumlahPkwt' => $kontrak->filter->dihitung()->count(),
            'berlaku' => $berlaku,
            'bolehUbah' => Auth::user()->can('pegawai.update'),
        ]);
    }

    /** Isi jendela "Detail" pada kolom Aksi daftar (potongan HTML, dimuat lewat fetch). */
    public function detail(DataPegawai $pegawai): View
    {
        $this->pastikanBolehLihat($pegawai);
        $pegawai->load('kepegawaian');

        return view('admin.riwayat-kontrak._detail', [
            'pegawai' => $pegawai,
            'rows' => RiwayatKontrak::untuk($pegawai),
            'urlLengkap' => route('admin.riwayat-kontrak.show', $pegawai),
        ]);
    }

    /** Tambah satu atau beberapa baris riwayat sekaligus (mis. Masa Training + PKWT 1 + PKWT 2). */
    public function store(Request $request, DataPegawai $pegawai): RedirectResponse
    {
        $this->pastikanBolehLihat($pegawai);

        // Baris yang jenisnya kosong dianggap baris kosong (form menyediakan beberapa baris cadangan).
        // Kunci (indeks) asli dipertahankan agar pesan galat tampil di baris yang benar.
        $baris = collect($request->input('baris', []))->filter(fn ($b) => is_array($b) && filled($b['jenis'] ?? null));
        $request->merge(['baris' => $baris->all()]);
        if ($baris->isEmpty()) {
            return back()->withInput()->withErrors(['baris' => 'Isi minimal satu baris riwayat kontrak (pilih jenisnya).']);
        }

        $validator = Validator::make($request->all(), [
            'baris' => ['required', 'array', 'max:20'],
            'baris.*.jenis' => ['required', 'in:' . implode(',', KontrakPegawai::JENIS)],
            'baris.*.tanggal_mulai' => ['required', 'date'],
            'baris.*.tanggal_selesai' => ['nullable', 'date'],
            'baris.*.nomor_kontrak' => ['nullable', 'string', 'max:100'],
            'baris.*.keterangan' => ['nullable', 'string', 'max:500'],
        ], $this->pesan());
        $validator->after(function ($v) use ($baris, $pegawai) {
            $sudah = $pegawai->kontrak()->pluck('jenis')->all();
            $dipakai = [];
            foreach ($baris as $i => $b) {
                $this->periksaBaris($v, "baris.$i", $b, $sudah, $dipakai);
            }
        });
        $data = $validator->validate();

        $oleh = Auth::id();
        foreach ($data['baris'] as $b) {
            KontrakPegawai::create($this->bersih($b) + ['data_pegawai_id' => $pegawai->id, 'dibuat_oleh' => $oleh]);
        }

        return redirect()->route('admin.riwayat-kontrak.show', $pegawai)
            ->with('success', count($data['baris']) . ' riwayat kontrak ' . $pegawai->nama_lengkap . ' ditambahkan.');
    }

    public function update(Request $request, DataPegawai $pegawai, KontrakPegawai $kontrak): RedirectResponse
    {
        $this->pastikanBolehLihat($pegawai);
        abort_unless($kontrak->data_pegawai_id === $pegawai->id, 404);

        $validator = Validator::make($request->all(), [
            'jenis' => ['required', 'in:' . implode(',', KontrakPegawai::JENIS)],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
            'nomor_kontrak' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ], $this->pesan());
        $validator->after(function ($v) use ($request, $pegawai, $kontrak) {
            $sudah = $pegawai->kontrak()->whereKeyNot($kontrak->id)->pluck('jenis')->all();
            $dipakai = [];
            $this->periksaBaris($v, '', $request->all(), $sudah, $dipakai);
        });

        $kontrak->update($this->bersih($validator->validate()));

        return redirect()->route('admin.riwayat-kontrak.show', $pegawai)->with('success', "Riwayat kontrak {$kontrak->jenis} diperbarui.");
    }

    public function destroy(DataPegawai $pegawai, KontrakPegawai $kontrak): RedirectResponse
    {
        $this->pastikanBolehLihat($pegawai);
        abort_unless($kontrak->data_pegawai_id === $pegawai->id, 404);
        $kontrak->delete();

        return redirect()->route('admin.riwayat-kontrak.show', $pegawai)->with('success', "Riwayat kontrak {$kontrak->jenis} dihapus.");
    }

    /** Aturan lintas-kolom satu baris: selesai wajib (kecuali PKWTT/Lainnya), selesai ≥ mulai, jenis tunggal tidak ganda. */
    private function periksaBaris($v, string $awalan, array $b, array $sudah, array &$dipakai): void
    {
        $kunci = fn (string $f) => $awalan === '' ? $f : "$awalan.$f";
        $jenis = $b['jenis'] ?? null;
        if (! $jenis) {
            return;
        }
        if (! in_array($jenis, KontrakPegawai::TANPA_BATAS, true) && blank($b['tanggal_selesai'] ?? null)) {
            $v->errors()->add($kunci('tanggal_selesai'), "Tanggal selesai {$jenis} wajib diisi.");
        }
        if (filled($b['tanggal_mulai'] ?? null) && filled($b['tanggal_selesai'] ?? null) && $b['tanggal_selesai'] < $b['tanggal_mulai']) {
            $v->errors()->add($kunci('tanggal_selesai'), "Tanggal selesai {$jenis} tidak boleh sebelum tanggal mulainya.");
        }
        if (in_array($jenis, KontrakPegawai::TUNGGAL, true)) {
            if (in_array($jenis, $sudah, true) || in_array($jenis, $dipakai, true)) {
                $v->errors()->add($kunci('jenis'), "{$jenis} sudah ada di riwayat pegawai ini. Ubah baris yang ada, atau pilih jenis lain.");
            }
            $dipakai[] = $jenis;
        }
    }

    private function bersih(array $b): array
    {
        $tanpaBatas = in_array($b['jenis'], KontrakPegawai::TANPA_BATAS, true);

        return [
            'jenis' => $b['jenis'],
            'tanggal_mulai' => $b['tanggal_mulai'],
            'tanggal_selesai' => $tanpaBatas && blank($b['tanggal_selesai'] ?? null) ? null : ($b['tanggal_selesai'] ?? null),
            'nomor_kontrak' => filled($b['nomor_kontrak'] ?? null) ? trim($b['nomor_kontrak']) : null,
            'keterangan' => filled($b['keterangan'] ?? null) ? trim($b['keterangan']) : null,
        ];
    }

    private function pesan(): array
    {
        return [
            'required' => 'Kolom :attribute wajib diisi.',
            'date' => 'Format tanggal pada :attribute tidak valid.',
            'in' => 'Jenis kontrak tidak dikenal.',
            'baris.*.jenis.required' => 'Pilih jenis kontrak.',
            'baris.*.tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'tanggal_mulai.required' => 'Tanggal mulai wajib diisi.',
            'jenis.required' => 'Pilih jenis kontrak.',
        ];
    }

    /** Pegawai + riwayatnya, dibatasi bagian yang boleh dilihat (kepala bagian: bagian yang dipimpin). */
    private function daftar(Request $request, string $tab, bool $hitung = false)
    {
        $lingkup = $this->lingkupUnit();
        $status = $request->query('status', 'Aktif');
        $kata = trim((string) $request->query('q'));

        return DataPegawai::query()->with('kepegawaian')->when($hitung, fn ($q) => $q->withCount('kontrak'))
            ->when($lingkup !== null, fn ($q) => $q->whereHas('kepegawaian', fn ($k) => $k->whereIn('unit_kerja', $lingkup)))
            ->when($status !== 'semua', fn ($q) => $q->whereHas('kepegawaian', fn ($k) => $k->where('status_rekrutmen', 'Aktif')))
            ->when($request->filled('unit_kerja'), fn ($q) => $q->whereHas('kepegawaian', fn ($k) => $k->where('unit_kerja', $request->query('unit_kerja'))))
            ->when($kata !== '', fn ($q) => $q->where(fn ($w) => $w->where('nama_lengkap', 'like', "%{$kata}%")->orWhere('nip', 'like', "%{$kata}%")))
            ->when($tab === 'belum', fn ($q) => $q->whereDoesntHave('kontrak'))
            ->when($tab === 'sudah', fn ($q) => $q->whereHas('kontrak'))
            ->orderBy('nama_lengkap');
    }

    private function lingkupUnit(): ?array
    {
        $user = Auth::user();

        return $user instanceof User && $user->isKepalaBagian() ? $user->unitsDipimpin() : null;
    }

    private function pastikanBolehLihat(DataPegawai $pegawai): void
    {
        $lingkup = $this->lingkupUnit();
        abort_if($lingkup !== null && ! in_array($pegawai->kepegawaian?->unit_kerja, $lingkup, true), 403, 'Pegawai ini di luar bagian yang Anda pimpin.');
    }
}
