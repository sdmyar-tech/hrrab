<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanLembur;
use App\Models\User;
use App\Support\ExcelExporter;
use App\Support\FilterTanggal;
use App\Support\Notifikasi;
use App\Support\PengajuanWorkflow;
use App\Support\Urutan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PengajuanLemburController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->filteredQuery($request);
        $urut = Urutan::terapkan($query, [
            ...Urutan::tanggal('tgl', 'Tanggal lembur', 'pengajuan_lemburs.tanggal'),
            ...Urutan::tanggal('diajukan', 'Diajukan', 'pengajuan_lemburs.created_at'),
            ...Urutan::abjad('nama', 'Nama', Urutan::namaPegawai('pengajuan_lemburs')),
        ]);
        $pengajuanLemburs = $query->paginate(15)->withQueryString();
        $bidangTabs = PengajuanWorkflow::gmTabCounts(PengajuanLembur::class, Auth::user());

        return view('admin.pengajuan-lembur.index', compact('pengajuanLemburs', 'bidangTabs') + $urut);
    }

    public function show(PengajuanLembur $pengajuanLembur): View
    {
        /** @var User $user */
        $user = Auth::user();

        $visible = PengajuanLembur::whereKey($pengajuanLembur->id);
        PengajuanWorkflow::scopeVisibleTo($visible, $user);

        if (! $visible->exists()) {
            abort(403, 'Anda tidak berwenang melihat pengajuan ini.');
        }

        $pengajuanLembur->load('dataPegawai.kepegawaian', 'approvedBy', 'kepalaApprovedBy', 'gmApprovedBy');

        return view('admin.pengajuan-lembur.show', compact('pengajuanLembur'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $pengajuanLemburs = $this->filteredQuery($request)->reorder('id')->get();

        $filename = ExcelExporter::namaFile('pengajuan-lembur');

        $headers = [
            'Nama Pegawai', 'NIP', 'Bagian', 'Tanggal', 'Hari', 'Uraian Tugas',
            'Jam Mulai', 'Jam Selesai', 'Jumlah Jam', 'Status', 'Tahap Saat Ini', 'Catatan Admin', 'Tanggal Pengajuan',
        ];

        $rows = $pengajuanLemburs->map(fn ($item) => [
            $item->dataPegawai?->nama_lengkap,
            $item->dataPegawai?->nip,
            $item->unit_kerja ?? $item->dataPegawai?->kepegawaian?->unit_kerja,
            optional($item->tanggal)->format('d-m-Y'),
            $item->hari,
            $item->uraian_tugas,
            $item->jam_mulai,
            $item->jam_selesai,
            $item->jumlah_jam,
            $item->statusLabel(),
            $item->isPending() ? $item->currentLevelLabel() : '-',
            $item->catatan_admin,
            optional($item->created_at)->format('d-m-Y H:i'),
        ]);

        return ExcelExporter::download($filename, $headers, $rows, 'Pengajuan Lembur');
    }

    private function filteredQuery(Request $request)
    {
        $query = PengajuanLembur::with('dataPegawai.kepegawaian')->latest('tanggal')->latest('id');
        PengajuanWorkflow::scopeVisibleTo($query, Auth::user());
        PengajuanWorkflow::scopeBidangGm($query, Auth::user(), $request->query('bidang', 'saya'));

        if ($search = $request->query('q')) {
            $query->whereHas('dataPegawai', function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        // Rentang tanggal (urutan terbalik ditukar, tanggal tidak sah diabaikan).
        FilterTanggal::terapkan($query, 'tanggal', $request);

        return $query;
    }

    public function approve(PengajuanLembur $pengajuanLembur): RedirectResponse
    {
        /** @var User $approver */
        $approver = Auth::user();

        if (! PengajuanWorkflow::canAct($approver, $pengajuanLembur)) {
            abort(403, 'Anda tidak berwenang menyetujui pengajuan ini pada tahap ini.');
        }

        $updates = PengajuanWorkflow::approvalUpdates($approver, $pengajuanLembur);
        $pengajuanLembur->update($updates);
        Notifikasi::pengajuanDiproses($pengajuanLembur);

        return redirect()
            ->route('admin.pengajuan-lembur.index')
            ->with('success', $updates['current_level'] === null
                ? 'Pengajuan lembur berhasil disetujui.'
                : 'Pengajuan lembur disetujui dan diteruskan ke ' . PengajuanWorkflow::levelLabel($updates['current_level']) . '.');
    }

    public function reject(Request $request, PengajuanLembur $pengajuanLembur): RedirectResponse
    {
        /** @var User $approver */
        $approver = Auth::user();

        if (! PengajuanWorkflow::canAct($approver, $pengajuanLembur)) {
            abort(403, 'Anda tidak berwenang menolak pengajuan ini pada tahap ini.');
        }

        $validated = $request->validate([
            'catatan_admin' => ['required', 'string', 'max:1000'],
        ], [
            'catatan_admin.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $pengajuanLembur->update([
            'status' => PengajuanLembur::STATUS_REJECTED,
            'catatan_admin' => $validated['catatan_admin'],
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'current_level' => null,
        ]);
        Notifikasi::pengajuanDiproses($pengajuanLembur);

        return redirect()
            ->route('admin.pengajuan-lembur.index')
            ->with('success', 'Pengajuan lembur ditolak.');
    }

    /** Admin: ubah langsung data & status pengajuan tanpa mengikuti tahapan persetujuan. */
    public function ubahAdmin(Request $request, PengajuanLembur $pengajuanLembur): RedirectResponse
    {
        $v = $request->validate([
            'status' => ['required', Rule::in([PengajuanLembur::STATUS_PENDING, PengajuanLembur::STATUS_APPROVED, PengajuanLembur::STATUS_REJECTED])],
            'current_level' => ['nullable', 'required_if:status,pending', Rule::in(array_keys(PengajuanWorkflow::LEVEL_LABELS))],
            'tanggal' => ['required', 'date'],
            'uraian_tugas' => ['required', 'string', 'max:2000'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i'],
            'jumlah_jam' => ['required', 'numeric', 'min:0', 'max:24'],
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
        ]);

        $statusLama = $pengajuanLembur->status;
        $pengajuanLembur->update([
            ...$v,
            'hari' => Carbon::parse($v['tanggal'])->translatedFormat('l'),
            ...PengajuanWorkflow::statusAdmin($pengajuanLembur, $v['status'], $v['current_level'] ?? null, Auth::user()),
        ]);

        if ($statusLama !== $pengajuanLembur->status || $pengajuanLembur->wasChanged('current_level')) {
            Notifikasi::pengajuanDiproses($pengajuanLembur);
        }

        return back()->with('success', 'Pengajuan lembur diperbarui langsung oleh Admin.');
    }

    public function destroy(PengajuanLembur $pengajuanLembur): RedirectResponse
    {
        $nama = $pengajuanLembur->dataPegawai?->nama_lengkap;
        $pengajuanLembur->delete();

        return redirect()->route('admin.pengajuan-lembur.index')->with('success', "Pengajuan lembur {$nama} dihapus.");
    }
}
