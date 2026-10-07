<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanCuti;
use App\Support\ExcelExporter;
use App\Support\PengajuanWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Support\Notifikasi;

class PengajuanCutiController extends Controller
{
    public function index(Request $request): View
    {
        $pengajuanCutis = $this->filteredQuery($request)->paginate(15)->withQueryString();
        $bidangTabs = PengajuanWorkflow::gmTabCounts(PengajuanCuti::class, Auth::user());

        return view('admin.pengajuan-cuti.index', compact('pengajuanCutis', 'bidangTabs'));
    }

    public function show(PengajuanCuti $pengajuanCuti): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $visible = PengajuanCuti::whereKey($pengajuanCuti->id);
        PengajuanWorkflow::scopeVisibleTo($visible, $user);

        if (! $visible->exists()) {
            abort(403, 'Anda tidak berwenang melihat pengajuan ini.');
        }

        $pengajuanCuti->load('dataPegawai.kepegawaian', 'approvedBy', 'kepalaApprovedBy', 'gmApprovedBy');

        return view('admin.pengajuan-cuti.show', compact('pengajuanCuti'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $pengajuanCutis = $this->filteredQuery($request)->reorder('id')->get();

        $filename = ExcelExporter::namaFile('pengajuan-cuti');

        $headers = [
            'Nama Pegawai', 'NIP', 'Divisi', 'Jenis Cuti', 'Kategori Cuti Besar',
            'Tanggal Mulai', 'Tanggal Selesai', 'Lama (hari)', 'Pengganti Tugas',
            'Status', 'Tahap Saat Ini', 'Catatan Admin', 'Tanggal Pengajuan',
        ];

        $rows = $pengajuanCutis->map(fn ($item) => [
            $item->dataPegawai?->nama_lengkap,
            $item->dataPegawai?->nip,
            $item->unit_kerja ?? $item->dataPegawai?->kepegawaian?->unit_kerja,
            $item->jenisLabel(),
            $item->kategoriBesarLabel(),
            optional($item->tanggal_mulai)->format('d-m-Y'),
            optional($item->tanggal_selesai)->format('d-m-Y'),
            $item->lama_cuti,
            $item->pengganti_nama,
            $item->statusLabel(),
            $item->isPending() ? $item->currentLevelLabel() : '-',
            $item->catatan_admin,
            optional($item->created_at)->format('d-m-Y'),
        ]);

        return ExcelExporter::download($filename, $headers, $rows, 'Pengajuan Cuti');
    }

    private function filteredQuery(Request $request)
    {
        $query = PengajuanCuti::with('dataPegawai.kepegawaian')->latest('tanggal_mulai')->latest('id');
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

        if ($request->filled('jenis_cuti')) {
            $query->where('jenis_cuti', $request->string('jenis_cuti'));
        }

        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal_mulai', '>=', $request->date('tanggal_dari'));
        }

        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal_mulai', '<=', $request->date('tanggal_sampai'));
        }

        return $query;
    }

    public function approve(Request $request, PengajuanCuti $pengajuanCuti): RedirectResponse
    {
        /** @var \App\Models\User $approver */
        $approver = Auth::user();

        if (! PengajuanWorkflow::canAct($approver, $pengajuanCuti)) {
            abort(403, 'Anda tidak berwenang menyetujui pengajuan ini pada tahap ini.');
        }

        $updates = PengajuanWorkflow::approvalUpdates($approver, $pengajuanCuti);

        if ($pengajuanCuti->current_level === PengajuanWorkflow::LEVEL_KEPALA_DIVISI) {
            $validated = $request->validate([
                'pengganti_nama' => ['required', 'string', 'max:150'],
            ], [
                'pengganti_nama.required' => 'Nama pengganti tugas wajib dipilih sebelum pengajuan diteruskan.',
            ]);

            if (! $pengajuanCuti->substituteOptions()->contains($validated['pengganti_nama'])) {
                return redirect()->back()->with('error', 'Nama pengganti harus berasal dari divisi yang sama dengan pemohon.');
            }

            $updates['pengganti_nama'] = $validated['pengganti_nama'];
        }

        $pengajuanCuti->update($updates);
        Notifikasi::pengajuanDiproses($pengajuanCuti);

        return redirect()
            ->route('admin.pengajuan-cuti.index')
            ->with('success', $updates['current_level'] === null
                ? 'Pengajuan cuti berhasil disetujui.'
                : 'Pengajuan cuti disetujui dan diteruskan ke ' . PengajuanWorkflow::levelLabel($updates['current_level']) . '.');
    }

    public function reject(Request $request, PengajuanCuti $pengajuanCuti): RedirectResponse
    {
        /** @var \App\Models\User $approver */
        $approver = Auth::user();

        if (! PengajuanWorkflow::canAct($approver, $pengajuanCuti)) {
            abort(403, 'Anda tidak berwenang menolak pengajuan ini pada tahap ini.');
        }

        $validated = $request->validate([
            'catatan_admin' => ['required', 'string', 'max:1000'],
        ], [
            'catatan_admin.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $pengajuanCuti->update([
            'status' => PengajuanCuti::STATUS_REJECTED,
            'catatan_admin' => $validated['catatan_admin'],
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'current_level' => null,
        ]);
        Notifikasi::pengajuanDiproses($pengajuanCuti);

        return redirect()
            ->route('admin.pengajuan-cuti.index')
            ->with('success', 'Pengajuan cuti ditolak.');
    }

    /** Admin: ubah langsung data & status pengajuan tanpa mengikuti tahapan persetujuan. */
    public function ubahAdmin(Request $request, PengajuanCuti $pengajuanCuti): RedirectResponse
    {
        $v = $request->validate([
            'status' => ['required', Rule::in([PengajuanCuti::STATUS_PENDING, PengajuanCuti::STATUS_APPROVED, PengajuanCuti::STATUS_REJECTED])],
            'current_level' => ['nullable', 'required_if:status,pending', Rule::in(array_keys(PengajuanWorkflow::LEVEL_LABELS))],
            'jenis_cuti' => ['required', Rule::in([PengajuanCuti::JENIS_TAHUNAN, PengajuanCuti::JENIS_BESAR])],
            'kategori_cuti_besar' => ['nullable', 'required_if:jenis_cuti,besar', Rule::in(array_keys(PengajuanCuti::KATEGORI_BESAR_OPTIONS))],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'lama_cuti' => ['required', 'integer', 'min:1', 'max:365'],
            'pengganti_nama' => ['nullable', 'string', 'max:150'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
        ]);

        $statusLama = $pengajuanCuti->status;
        $pengajuanCuti->update([
            ...$v,
            'kategori_cuti_besar' => $v['jenis_cuti'] === PengajuanCuti::JENIS_BESAR ? $v['kategori_cuti_besar'] : null,
            ...PengajuanWorkflow::statusAdmin($pengajuanCuti, $v['status'], $v['current_level'] ?? null, Auth::user()),
        ]);

        if ($statusLama !== $pengajuanCuti->status || $pengajuanCuti->wasChanged('current_level')) {
            Notifikasi::pengajuanDiproses($pengajuanCuti);
        }

        return back()->with('success', 'Pengajuan cuti diperbarui langsung oleh Admin.');
    }

    public function destroy(PengajuanCuti $pengajuanCuti): RedirectResponse
    {
        $nama = $pengajuanCuti->dataPegawai?->nama_lengkap;
        $pengajuanCuti->delete();

        return redirect()->route('admin.pengajuan-cuti.index')->with('success', "Pengajuan cuti {$nama} dihapus.");
    }
}