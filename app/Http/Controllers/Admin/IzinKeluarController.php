<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PengajuanIzinKeluar;
use App\Models\User;
use App\Support\ExcelExporter;
use App\Support\FilterTanggal;
use App\Support\Notifikasi;
use App\Support\PengajuanWorkflow;
use App\Support\Urutan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Persetujuan izin keluar saat jam kerja (alur sama dengan cuti: atasan langsung → Kepala SDM). */
class IzinKeluarController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->filteredQuery($request);
        $urut = Urutan::terapkan($query, [
            ...Urutan::tanggal('tgl', 'Tanggal izin', 'pengajuan_izin_keluars.tanggal'),
            ...Urutan::tanggal('diajukan', 'Diajukan', 'pengajuan_izin_keluars.created_at'),
            ...Urutan::abjad('nama', 'Nama', Urutan::namaPegawai('pengajuan_izin_keluars')),
        ]);
        $izinKeluars = $query->paginate(15)->withQueryString();
        $bidangTabs = PengajuanWorkflow::gmTabCounts(PengajuanIzinKeluar::class, Auth::user());

        return view('admin.izin-keluar.index', compact('izinKeluars', 'bidangTabs') + $urut);
    }

    public function show(PengajuanIzinKeluar $izinKeluar): View
    {
        $visible = PengajuanIzinKeluar::whereKey($izinKeluar->id);
        PengajuanWorkflow::scopeVisibleTo($visible, Auth::user());
        abort_unless($visible->exists(), 403, 'Anda tidak berwenang melihat pengajuan ini.');

        $izinKeluar->load('dataPegawai.kepegawaian', 'approvedBy', 'kepalaApprovedBy', 'gmApprovedBy');

        return view('admin.izin-keluar.show', compact('izinKeluar'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $rows = $this->filteredQuery($request)->reorder('id')->get()->map(fn (PengajuanIzinKeluar $i) => [
            $i->dataPegawai?->nama_lengkap,
            $i->dataPegawai?->nip,
            $i->unit_kerja,
            optional($i->tanggal)->format('d-m-Y'),
            substr((string) $i->jam_keluar, 0, 5),
            $i->tidakKembali() ? 'Tidak kembali' : substr((string) $i->jam_kembali, 0, 5),
            $i->keperluanLabel(),
            $i->alasan,
            $i->statusLabel(),
            $i->isPending() ? $i->currentLevelLabel() : '-',
            $i->kode_tiket,
            $i->catatan_admin,
            optional($i->created_at)->format('d-m-Y H:i'),
        ]);

        return ExcelExporter::download(ExcelExporter::namaFile('izin-keluar'), [
            'Nama Pegawai', 'NIP', 'Unit Kerja', 'Tanggal', 'Jam Keluar', 'Jam Kembali', 'Keperluan', 'Alasan',
            'Status', 'Tahap Saat Ini', 'Kode Tiket', 'Catatan', 'Tanggal Pengajuan',
        ], $rows, 'Izin Keluar');
    }

    private function filteredQuery(Request $request)
    {
        $query = PengajuanIzinKeluar::with('dataPegawai.kepegawaian')->latest('tanggal')->latest('id');
        PengajuanWorkflow::scopeVisibleTo($query, Auth::user());
        PengajuanWorkflow::scopeBidangGm($query, Auth::user(), $request->query('bidang', 'saya'));

        if ($search = $request->query('q')) {
            $query->where(fn ($q) => $q->where('kode_tiket', 'like', "%{$search}%")
                ->orWhereHas('dataPegawai', fn ($p) => $p->where('nama_lengkap', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%")));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        // Rentang tanggal (urutan terbalik ditukar, tanggal tidak sah diabaikan).
        FilterTanggal::terapkan($query, 'tanggal', $request);

        return $query;
    }

    public function approve(PengajuanIzinKeluar $izinKeluar): RedirectResponse
    {
        /** @var User $approver */
        $approver = Auth::user();
        abort_unless(PengajuanWorkflow::canAct($approver, $izinKeluar), 403, 'Anda tidak berwenang menyetujui pengajuan ini pada tahap ini.');

        $updates = PengajuanWorkflow::approvalUpdates($approver, $izinKeluar);
        $izinKeluar->update($updates);
        Notifikasi::pengajuanDiproses($izinKeluar);

        return redirect()->route('admin.izin-keluar.index')->with('success', $updates['current_level'] === null
            ? 'Izin keluar disetujui. Tiket keluar sudah terbit untuk pegawai.'
            : 'Izin keluar disetujui dan diteruskan ke ' . PengajuanWorkflow::levelLabel($updates['current_level']) . '.');
    }

    public function reject(Request $request, PengajuanIzinKeluar $izinKeluar): RedirectResponse
    {
        /** @var User $approver */
        $approver = Auth::user();
        abort_unless(PengajuanWorkflow::canAct($approver, $izinKeluar), 403, 'Anda tidak berwenang menolak pengajuan ini pada tahap ini.');

        $validated = $request->validate(['catatan_admin' => ['required', 'string', 'max:1000']], ['catatan_admin.required' => 'Alasan penolakan wajib diisi.']);

        $izinKeluar->update([
            'status' => PengajuanIzinKeluar::STATUS_REJECTED,
            'catatan_admin' => $validated['catatan_admin'],
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'current_level' => null,
        ]);
        Notifikasi::pengajuanDiproses($izinKeluar);

        return redirect()->route('admin.izin-keluar.index')->with('success', 'Izin keluar ditolak.');
    }

    /** Admin: ubah langsung data & status tanpa mengikuti tahapan persetujuan. */
    public function ubahAdmin(Request $request, PengajuanIzinKeluar $izinKeluar): RedirectResponse
    {
        $tidakKembali = $request->boolean('tidak_kembali');
        $v = $request->validate([
            'status' => ['required', Rule::in([PengajuanIzinKeluar::STATUS_PENDING, PengajuanIzinKeluar::STATUS_APPROVED, PengajuanIzinKeluar::STATUS_REJECTED])],
            'current_level' => ['nullable', 'required_if:status,pending', Rule::in(array_keys(PengajuanWorkflow::LEVEL_LABELS))],
            'tanggal' => ['required', 'date'],
            'jam_keluar' => ['required', 'date_format:H:i'],
            'jam_kembali' => [$tidakKembali ? 'nullable' : 'required', 'date_format:H:i', 'after:jam_keluar'],
            'keperluan' => ['required', Rule::in(array_keys(PengajuanIzinKeluar::KEPERLUAN))],
            'alasan' => ['required', 'string', 'max:500'],
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
        ]);

        $statusLama = $izinKeluar->status;
        $izinKeluar->update([
            ...$v,
            'jam_kembali' => $tidakKembali ? null : $v['jam_kembali'],
            ...PengajuanWorkflow::statusAdmin($izinKeluar, $v['status'], $v['current_level'] ?? null, Auth::user()),
        ]);

        if ($statusLama !== $izinKeluar->status || $izinKeluar->wasChanged('current_level')) {
            Notifikasi::pengajuanDiproses($izinKeluar);
        }

        return back()->with('success', 'Izin keluar diperbarui langsung oleh Admin.');
    }

    public function destroy(PengajuanIzinKeluar $izinKeluar): RedirectResponse
    {
        $nama = $izinKeluar->dataPegawai?->nama_lengkap;
        $izinKeluar->delete();

        return redirect()->route('admin.izin-keluar.index')->with('success', "Izin keluar {$nama} dihapus.");
    }
}
