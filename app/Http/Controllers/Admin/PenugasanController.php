<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataPegawai;
use App\Models\PengajuanCuti;
use App\Models\PengajuanLembur;
use App\Models\Penugasan;
use App\Support\Notifikasi;
use App\Support\PenugasanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Support\ExcelExporter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PenugasanController extends Controller
{
    private const JENIS_FORM = ['mutasi', 'rangkap'];

    public function index(Request $request): View
    {
        $hariIni = now()->toDateString();
        $query = $this->filteredQuery($request)->latest('updated_at')->latest('id');

        return view('admin.penugasan.index', [
            'penugasans' => $query->paginate(20)->withQueryString(),
            'unitsTanpaKepala' => PenugasanService::unitsTanpaKepala(),
            'jumlahRangkapAktif' => Penugasan::aktif()->where('is_utama', false)->count(),
            'jumlahTerjadwal' => Penugasan::where('tanggal_mulai', '>', $hariIni)->count(),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $rows = $this->filteredQuery($request)->orderBy('id')->get()->map(fn (Penugasan $p) => [
            optional($p->created_at)->format('d-m-Y H:i'),
            $p->dataPegawai?->nip,
            $p->dataPegawai?->nama_lengkap,
            $p->jenisLabel(),
            $p->unit_kerja,
            $p->jabatan,
            $p->peranLabel(),
            optional($p->tanggal_mulai)->format('d-m-Y'),
            optional($p->tanggal_selesai)->format('d-m-Y'),
            $p->statusLabel(),
            $p->dibuatOleh?->name,
            $p->keterangan,
        ]);

        return ExcelExporter::download(ExcelExporter::namaFile('mutasi-rangkap'), [
            'Dicatat', 'NIP', 'Nama Pegawai', 'Jenis', 'Unit', 'Jabatan', 'Peran',
            'Mulai', 'Selesai', 'Status', 'Dicatat Oleh', 'Keterangan',
        ], $rows, 'Mutasi & Rangkap');
    }

    private function filteredQuery(Request $request)
    {
        $query = Penugasan::with(['dataPegawai.kepegawaian', 'dibuatOleh'])
            ->where('jenis', '!=', Penugasan::JENIS_AWAL);

        if (in_array($jenis = $request->query('jenis'), [Penugasan::JENIS_MUTASI, Penugasan::JENIS_RANGKAP], true)) {
            $query->where('jenis', $jenis);
        }

        $hariIni = now()->toDateString();
        match ($request->query('status')) {
            'berlaku' => $query->aktif(),
            'terjadwal' => $query->where('tanggal_mulai', '>', $hariIni),
            'selesai' => $query->where('tanggal_selesai', '<', $hariIni),
            default => null,
        };

        if ($search = $request->query('q')) {
            $query->where(fn ($q) => $q->where('unit_kerja', 'like', "%{$search}%")
                ->orWhereHas('dataPegawai', fn ($p) => $p->where('nama_lengkap', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%")));
        }

        return $query;
    }

    public function create(Request $request, DataPegawai $pegawai): View
    {
        $jenis = in_array($request->query('jenis'), self::JENIS_FORM, true) ? $request->query('jenis') : 'mutasi';
        $pegawai->load(['kepegawaian', 'penugasanAktif']);

        $pending = fn (string $model) => $model::where('data_pegawai_id', $pegawai->id)->where('status', $model::STATUS_PENDING)->count();

        return view('admin.penugasan.create', [
            'pegawai' => $pegawai,
            'jenis' => $jenis,
            'utama' => $pegawai->penugasanAktif->firstWhere('is_utama', true),
            'jumlahPending' => $pending(PengajuanCuti::class) + $pending(PengajuanLembur::class),
        ]);
    }

    public function store(Request $request, DataPegawai $pegawai): RedirectResponse
    {
        $jenis = $request->input('jenis');
        abort_unless(in_array($jenis, self::JENIS_FORM, true), 422);

        $units = config('kepegawaian.unit_kerja');
        $umum = [
            'unit_kerja' => ['required', 'string', Rule::in($units)],
            'jabatan' => [$jenis === 'mutasi' ? 'required' : 'nullable', 'string', 'max:255'],
            'peran' => ['required', Rule::in([Penugasan::PERAN_ANGGOTA, Penugasan::PERAN_KEPALA])],
            'no_sk' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];

        $validated = $request->validate($jenis === 'mutasi'
            ? [...$umum,
                'tanggal_efektif' => ['required', 'date'],
                'pengajuan_pending' => ['required', Rule::in([PenugasanService::PENDING_LANJUT, PenugasanService::PENDING_TOLAK])]]
            : [...$umum,
                'tanggal_mulai' => ['required', 'date'],
                'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai']],
            ['tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.']);

        $validated['jabatan'] = isset($validated['jabatan']) ? mb_strtoupper(trim($validated['jabatan'])) : null;

        $peringatan = $jenis === 'mutasi'
            ? PenugasanService::mutasi($pegawai, $validated, Auth::user())
            : PenugasanService::tambahRangkap($pegawai, $validated, Auth::user());

        $pesan = $jenis === 'mutasi'
            ? "{$pegawai->nama_lengkap} dimutasi ke {$validated['unit_kerja']} mulai " . \Carbon\Carbon::parse($validated['tanggal_efektif'])->format('d-m-Y') . '.'
            : "Rangkap tugas {$pegawai->nama_lengkap} di {$validated['unit_kerja']} ditambahkan.";

        Notifikasi::penugasanBerubah($pegawai->id,
            $jenis === 'mutasi' ? "Anda dimutasi ke {$validated['unit_kerja']}" : "Rangkap tugas di {$validated['unit_kerja']}",
            $jenis === 'mutasi'
                ? 'Berlaku mulai ' . \Carbon\Carbon::parse($validated['tanggal_efektif'])->translatedFormat('d F Y') . ($validated['jabatan'] ? " sebagai {$validated['jabatan']}." : '.')
                : 'Mulai ' . \Carbon\Carbon::parse($validated['tanggal_mulai'])->translatedFormat('d F Y') . '. Lembur di unit ini diajukan dengan memilih unit tersebut.');

        return $this->kembali($pegawai, $pesan, $peringatan);
    }

    public function edit(Penugasan $penugasan): View
    {
        return view('admin.penugasan.edit', ['penugasan' => $penugasan->load('dataPegawai')]);
    }

    /** Admin: koreksi langsung satu baris penugasan tanpa aturan mutasi/rangkap. */
    public function update(Request $request, Penugasan $penugasan): RedirectResponse
    {
        $v = $request->validate([
            'unit_kerja' => ['required', Rule::in(config('kepegawaian.unit_kerja'))],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'peran' => ['required', Rule::in([Penugasan::PERAN_ANGGOTA, Penugasan::PERAN_KEPALA])],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'no_sk' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], ['tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.']);

        $v['jabatan'] = filled($v['jabatan'] ?? null) ? mb_strtoupper(trim($v['jabatan'])) : null;
        $peringatan = PenugasanService::ubahLangsung($penugasan, $v);

        return $this->kembali($penugasan->dataPegawai, "{$penugasan->jenisLabel()} di {$penugasan->unit_kerja} diperbarui langsung oleh Admin.", $peringatan);
    }

    public function akhiri(Request $request, Penugasan $penugasan): RedirectResponse
    {
        $validated = $request->validate(['tanggal_selesai' => ['required', 'date']]);

        $peringatan = PenugasanService::akhiri($penugasan, $validated['tanggal_selesai']);

        return $this->kembali($penugasan->dataPegawai, "Rangkap tugas di {$penugasan->unit_kerja} diakhiri.", $peringatan);
    }

    public function destroy(Request $request, Penugasan $penugasan): RedirectResponse
    {
        $pegawai = $penugasan->dataPegawai;

        try {
            $peringatan = PenugasanService::hapus($penugasan, (bool) Auth::user()?->isAdmin());
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        $pesan = "{$penugasan->jenisLabel()} {$pegawai?->nama_lengkap} ke {$penugasan->unit_kerja} dihapus."
            . ($penugasan->is_utama ? ' Pegawai dikembalikan ke penempatan sebelumnya.' : '');

        // Dari halaman daftar Mutasi & Rangkap: tetap di daftar, peringatan digabung ke pesan.
        if ($request->input('kembali') === 'daftar') {
            return back()->with('success', trim($pesan . ' ' . implode(' ', $peringatan)));
        }

        return $this->kembali($pegawai, $pesan, $peringatan);
    }

    /**
     * @param  list<string>  $peringatan
     */
    private function kembali(DataPegawai $pegawai, string $pesan, array $peringatan): RedirectResponse
    {
        return redirect()
            ->route('admin.pegawai.show', ['pegawai' => $pegawai, 'tab' => 'penugasan'])
            ->with('success', $pesan)
            ->with('peringatan_penugasan', $peringatan);
    }
}
