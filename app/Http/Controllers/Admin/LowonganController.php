<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\Lowongan;
use App\Models\User;
use App\Support\ExcelExporter;
use App\Support\FilterTanggal;
use App\Support\RekrutmenAccess;
use App\Support\Urutan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LowonganController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        Lowongan::tutupYangKedaluwarsa();

        $query = $this->filteredQuery($request, $user);
        $urut = Urutan::terapkan($query, [
            ...Urutan::tanggal('dibuat', 'Dibuat', 'lowongans.created_at'),
            ...Urutan::tanggal('tutup', 'Tanggal tutup', 'lowongans.tanggal_tutup'),
            ...Urutan::abjad('judul', 'Judul', 'lowongans.judul'),
            ...Urutan::abjad('unit', 'Bagian', 'lowongans.unit_kerja'),
        ]);
        $lowongans = $query->paginate(15)->withQueryString();

        return view('admin.lowongan.index', compact('lowongans') + $urut);
    }

    public function export(Request $request): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $rows = $this->filteredQuery($request, $user)->with('dipublikasikanOleh')->orderBy('id')->get()->map(fn (Lowongan $l) => [
            optional($l->created_at)->format('d-m-Y'),
            $l->judul,
            $l->jabatan,
            $l->unit_kerja,
            $l->jumlah_dibutuhkan,
            $l->lamarans_count,
            $l->statusLabel(),
            optional($l->dipublikasikan_pada)->format('d-m-Y'),
            optional($l->tanggal_tutup)->format('d-m-Y'),
            $l->dipublikasikanOleh?->name,
            $l->pengajuanFormasi ? 'Formasi #' . $l->pengajuanFormasi->id : 'Manual',
        ]);

        return ExcelExporter::download(ExcelExporter::namaFile('lowongan'), [
            'Dibuat', 'Judul', 'Jabatan', 'Bagian', 'Kebutuhan', 'Jumlah Pelamar', 'Status',
            'Dipublikasikan', 'Tanggal Tutup', 'Dipublikasikan Oleh', 'Sumber',
        ], $rows, 'Lowongan');
    }

    private function filteredQuery(Request $request, User $user)
    {
        $query = Lowongan::with('pengajuanFormasi')->withCount('lamarans');
        RekrutmenAccess::scopeUnitKerja($query, $user);

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('jabatan', 'like', "%{$search}%")
                    ->orWhere('unit_kerja', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        // Rentang tanggal tutup lowongan.
        FilterTanggal::terapkan($query, 'tanggal_tutup', $request);

        return $query;
    }

    public function create(): View
    {
        return view('admin.lowongan.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        Lowongan::create($validated);

        return redirect()
            ->route('admin.lowongan.index')
            ->with('success', 'Draft lowongan berhasil dibuat.');
    }

    public function edit(Lowongan $lowongan): View
    {
        $this->authorizeAccess($lowongan);

        return view('admin.lowongan.edit', compact('lowongan'));
    }

    public function update(Request $request, Lowongan $lowongan): RedirectResponse
    {
        $this->authorizeAccess($lowongan);

        $validated = $this->validated($request, $lowongan);

        if ($request->input('submit_action') === 'publish') {
            if (! $validated['deskripsi'] || ! $validated['kualifikasi']) {
                return redirect()->back()->withInput()->with('error', 'Lengkapi deskripsi & kualifikasi sebelum mempublikasikan lowongan.');
            }

            if ($this->tanggalTutupLewat($validated['tanggal_tutup'] ?? null)) {
                return redirect()->back()->withInput()->with('error', self::PESAN_TANGGAL_LEWAT);
            }

            $validated['status'] = Lowongan::STATUS_DIPUBLIKASIKAN;
            $validated['dipublikasikan_oleh'] = Auth::id();
            $validated['dipublikasikan_pada'] = now();
        }

        $lowongan->update($validated);

        return redirect()
            ->route('admin.lowongan.index')
            ->with('success', $request->input('submit_action') === 'publish'
                ? 'Lowongan berhasil disimpan & dipublikasikan.'
                : 'Lowongan berhasil diperbarui.');
    }

    public function publish(Lowongan $lowongan): RedirectResponse
    {
        $this->authorizeAccess($lowongan);

        if (! $lowongan->deskripsi || ! $lowongan->kualifikasi) {
            return redirect()->back()->with('error', 'Lengkapi deskripsi & kualifikasi sebelum mempublikasikan lowongan.');
        }

        if ($this->tanggalTutupLewat($lowongan->tanggal_tutup)) {
            return redirect()->back()->with('error', self::PESAN_TANGGAL_LEWAT);
        }

        $lowongan->update([
            'status' => Lowongan::STATUS_DIPUBLIKASIKAN,
            'dipublikasikan_oleh' => Auth::id(),
            'dipublikasikan_pada' => now(),
        ]);

        return redirect()
            ->route('admin.lowongan.index')
            ->with('success', 'Lowongan berhasil dipublikasikan.');
    }

    public function reopen(Request $request, Lowongan $lowongan): RedirectResponse
    {
        $this->authorizeAccess($lowongan);

        if ($lowongan->status !== Lowongan::STATUS_DITUTUP) {
            return redirect()->back()->with('error', 'Hanya lowongan berstatus Ditutup yang bisa dibuka kembali.');
        }

        if ($lowongan->isKuotaPenuh() && ! Auth::user()->isAdmin()) {
            return redirect()->back()->with('error', 'Kuota lowongan ini sudah terpenuhi, tidak bisa dibuka kembali.');
        }

        if (! $lowongan->deskripsi || ! $lowongan->kualifikasi) {
            return redirect()->back()->with('error', 'Lengkapi deskripsi & kualifikasi (Edit) sebelum membuka kembali lowongan.');
        }

        $validated = $request->validate([
            'tanggal_tutup' => ['required', 'date', ...(Auth::user()->isAdmin() ? [] : ['after_or_equal:today'])],
        ], [
            'tanggal_tutup.required' => 'Tanggal tutup baru wajib diisi.',
            'tanggal_tutup.after_or_equal' => 'Tanggal tutup baru minimal hari ini.',
        ]);

        $lowongan->update([
            'status' => Lowongan::STATUS_DIPUBLIKASIKAN,
            'tanggal_tutup' => $validated['tanggal_tutup'],
        ]);

        return redirect()->back()->with('success', 'Lowongan dibuka kembali sampai ' . $lowongan->tanggal_tutup->translatedFormat('d F Y') . '.');
    }

    public function close(Lowongan $lowongan): RedirectResponse
    {
        $this->authorizeAccess($lowongan);

        $lowongan->update(['status' => Lowongan::STATUS_DITUTUP]);

        return redirect()
            ->route('admin.lowongan.index')
            ->with('success', 'Lowongan ditutup.');
    }

    private const PESAN_TANGGAL_LEWAT = 'Tanggal tutup sudah lewat. Perpanjang tanggal tutup dulu (Edit) sebelum mempublikasikan lowongan.';

    private function tanggalTutupLewat($tanggalTutup): bool
    {
        return $tanggalTutup && Carbon::parse($tanggalTutup)->lt(now()->startOfDay());
    }

    private function authorizeAccess(Lowongan $lowongan): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (RekrutmenAccess::canKelolaSemuaLowongan($user)) {
            return;
        }

        if (! $user->memimpinUnit($lowongan->unit_kerja)) {
            abort(403, 'Anda hanya bisa mengelola lowongan divisi Anda sendiri.');
        }
    }

    private function validated(Request $request, ?Lowongan $lowongan = null): array
    {
        $tanggalTutupBerubah = $lowongan === null
            || $request->input('tanggal_tutup') !== optional($lowongan->tanggal_tutup)->format('Y-m-d');
        $sudahDitutup = $lowongan?->status === Lowongan::STATUS_DITUTUP;

        $tanggalTutupRules = ['nullable', 'date'];
        if ($tanggalTutupBerubah && ! $sudahDitutup && ! Auth::user()->isAdmin()) {
            $tanggalTutupRules[] = 'after_or_equal:today';
        }

        return $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'unit_kerja' => ['required', 'string', Rule::in(config('kepegawaian.unit_kerja'))],
            'jabatan' => ['required', 'string', Rule::in(Jabatan::daftar())],
            'jumlah_dibutuhkan' => ['required', 'integer', 'min:1', 'max:50'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'kualifikasi' => ['nullable', 'string', 'max:5000'],
            'lokasi_kerja' => ['nullable', 'string', Rule::in(config('kepegawaian.lokasi_kerja'))],
            'tipe_kerja' => ['nullable', 'string', 'max:50'],
            'tanggal_tutup' => $tanggalTutupRules,
        ]);
    }
}
