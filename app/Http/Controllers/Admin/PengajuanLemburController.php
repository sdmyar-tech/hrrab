<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanLembur;
use App\Support\ExcelExporter;
use App\Support\PengajuanWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Support\Notifikasi;

class PengajuanLemburController extends Controller
{
    public function index(Request $request): View
    {
        $pengajuanLemburs = $this->filteredQuery($request)->paginate(15)->withQueryString();
        $bidangTabs = PengajuanWorkflow::gmTabCounts(PengajuanLembur::class, Auth::user());

        return view('admin.pengajuan-lembur.index', compact('pengajuanLemburs', 'bidangTabs'));
    }

    public function show(PengajuanLembur $pengajuanLembur): View
    {
        /** @var \App\Models\User $user */
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
            'Nama Pegawai', 'NIP', 'Unit Kerja', 'Tanggal', 'Hari', 'Uraian Tugas',
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

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal', '>=', $request->date('tanggal_dari'));
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal', '<=', $request->date('tanggal_sampai'));
        }

        return $query;
    }

    public function approve(PengajuanLembur $pengajuanLembur): RedirectResponse
    {
        /** @var \App\Models\User $approver */
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
        /** @var \App\Models\User $approver */
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
}
