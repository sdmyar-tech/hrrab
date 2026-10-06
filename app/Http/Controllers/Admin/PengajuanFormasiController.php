<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lowongan;
use App\Models\PengajuanFormasi;
use App\Support\PengajuanWorkflow;
use App\Support\RekrutmenAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Support\Notifikasi;

class PengajuanFormasiController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = PengajuanFormasi::with(['diajukanOleh.dataPegawai.kepegawaian', 'direviewOleh', 'lowongan'])->latest();

        if (! RekrutmenAccess::canLihatSemuaFormasi($user)) {
            $query->where('diajukan_oleh', $user->id);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('jabatan', 'like', "%{$search}%")
                    ->orWhere('unit_kerja', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $pengajuanFormasis = $query->paginate(15)->withQueryString();

        return view('admin.pengajuan-formasi.index', [
            'pengajuanFormasis' => $pengajuanFormasis,
            'canReview' => RekrutmenAccess::canReview($user),
            'canSubmit' => PengajuanWorkflow::submitterLevel($user) === PengajuanWorkflow::LEVEL_KEPALA_DIVISI,
        ]);
    }

    public function create(): View|RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (PengajuanWorkflow::submitterLevel($user) !== PengajuanWorkflow::LEVEL_KEPALA_DIVISI) {
            return redirect()
                ->route('admin.pengajuan-formasi.index')
                ->with('error', 'Hanya Kepala/Koordinator Divisi yang dapat mengajukan permintaan kekurangan pegawai.');
        }

        return view('admin.pengajuan-formasi.create', ['unitOptions' => $user->unitsDipimpin()]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (PengajuanWorkflow::submitterLevel($user) !== PengajuanWorkflow::LEVEL_KEPALA_DIVISI) {
            abort(403, 'Hanya Kepala/Koordinator Divisi yang dapat mengajukan permintaan kekurangan pegawai.');
        }

        $validated = $request->validate([
            'unit_kerja' => ['nullable', 'string', Rule::in($user->unitsDipimpin())],
            'jabatan' => ['required', 'string', Rule::in(config('kepegawaian.jabatan'))],
            'jumlah_dibutuhkan' => ['required', 'integer', 'min:1', 'max:50'],
            'kualifikasi' => ['required', 'string', 'max:2000'],
            'target_waktu' => ['nullable', 'date', 'after_or_equal:today'],
            'alasan' => ['required', 'string', 'max:2000'],
        ]);

        $formasi = $user->pengajuanFormasis()->create([
            'unit_kerja' => $validated['unit_kerja'] ?? $user->unitDipimpin(),
            'jabatan' => $validated['jabatan'],
            'jumlah_dibutuhkan' => $validated['jumlah_dibutuhkan'],
            'kualifikasi' => $validated['kualifikasi'],
            'target_waktu' => $validated['target_waktu'] ?? null,
            'alasan' => $validated['alasan'],
            'status' => PengajuanFormasi::STATUS_PENDING,
        ]);
        Notifikasi::formasiBaru($formasi);

        return redirect()
            ->route('admin.pengajuan-formasi.index')
            ->with('success', 'Pengajuan formasi berhasil dikirim. Menunggu review Admin/SDM.');
    }

    public function approve(PengajuanFormasi $pengajuanFormasi): RedirectResponse
    {
        /** @var \App\Models\User $reviewer */
        $reviewer = Auth::user();

        if (! RekrutmenAccess::canReviewFormasi($reviewer, $pengajuanFormasi)) {
            abort(403, 'Anda tidak berwenang mereview pengajuan formasi ini.');
        }

        if (! $pengajuanFormasi->isPending()) {
            return redirect()->back()->with('error', 'Pengajuan formasi ini sudah diproses.');
        }

        $pengajuanFormasi->update([
            'status' => PengajuanFormasi::STATUS_APPROVED,
            'direview_oleh' => $reviewer->id,
            'direview_pada' => now(),
        ]);

        $pengajuanFormasi->lowongan()->create([
            'judul' => $pengajuanFormasi->jabatan,
            'unit_kerja' => $pengajuanFormasi->unit_kerja,
            'jabatan' => $pengajuanFormasi->jabatan,
            'jumlah_dibutuhkan' => $pengajuanFormasi->jumlah_dibutuhkan,
            'deskripsi' => $pengajuanFormasi->alasan,
            'kualifikasi' => $pengajuanFormasi->kualifikasi,
            'tanggal_tutup' => $pengajuanFormasi->target_waktu,
            'status' => Lowongan::STATUS_DIPUBLIKASIKAN,
            'dipublikasikan_oleh' => $reviewer->id,
            'dipublikasikan_pada' => now(),
        ]);
        Notifikasi::formasiDiputuskan($pengajuanFormasi);

        return redirect()
            ->route('admin.pengajuan-formasi.index')
            ->with('success', 'Pengajuan formasi disetujui. Lowongan rekrutmen telah terbit di halaman publik.');
    }

    public function reject(Request $request, PengajuanFormasi $pengajuanFormasi): RedirectResponse
    {
        /** @var \App\Models\User $reviewer */
        $reviewer = Auth::user();

        if (! RekrutmenAccess::canReviewFormasi($reviewer, $pengajuanFormasi)) {
            abort(403, 'Anda tidak berwenang mereview pengajuan formasi ini.');
        }

        if (! $pengajuanFormasi->isPending()) {
            return redirect()->back()->with('error', 'Pengajuan formasi ini sudah diproses.');
        }

        $validated = $request->validate([
            'catatan_admin' => ['required', 'string', 'max:1000'],
        ], [
            'catatan_admin.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $pengajuanFormasi->update([
            'status' => PengajuanFormasi::STATUS_REJECTED,
            'catatan_admin' => $validated['catatan_admin'],
            'direview_oleh' => $reviewer->id,
            'direview_pada' => now(),
        ]);
        Notifikasi::formasiDiputuskan($pengajuanFormasi);

        return redirect()
            ->route('admin.pengajuan-formasi.index')
            ->with('success', 'Pengajuan formasi ditolak.');
    }

    public function batalkan(PengajuanFormasi $pengajuanFormasi): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($pengajuanFormasi->diajukan_oleh !== $user->id) {
            abort(403, 'Anda hanya bisa membatalkan pengajuan formasi milik sendiri.');
        }

        if (! $pengajuanFormasi->isPending()) {
            return redirect()->back()->with('error', 'Pengajuan formasi ini sudah diproses dan tidak bisa dibatalkan.');
        }

        $pengajuanFormasi->update(['status' => PengajuanFormasi::STATUS_DIBATALKAN]);

        return redirect()
            ->route('admin.pengajuan-formasi.index')
            ->with('success', 'Pengajuan formasi dibatalkan.');
    }
}
