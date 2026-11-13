<?php

namespace App\Http\Controllers;

use App\Models\PermintaanGantiPassword;
use App\Models\User;
use App\Support\Notifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Ganti kata sandi atas persetujuan:
 * - Pemilik akun mengajukan (profil) → Admin/Kepala SDM menyetujui/menolak → dialog ganti kata sandi terbuka untuk pemilik akun.
 * - Satu persetujuan hanya untuk satu kali ganti.
 */
class PermintaanPasswordController extends Controller
{
    /** Pemilik akun: ajukan penggantian kata sandi. */
    public function ajukan(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $v = $request->validate(['alasan' => ['nullable', 'string', 'max:500']], ['alasan.max' => 'Alasan maksimal 500 karakter.']);

        $aktif = PermintaanGantiPassword::where('user_id', $user->id)
            ->whereIn('status', [PermintaanGantiPassword::STATUS_PENDING, PermintaanGantiPassword::STATUS_APPROVED])->first();
        if ($aktif) {
            return back()->with('error', $aktif->status === PermintaanGantiPassword::STATUS_APPROVED
                ? 'Permintaan Anda sudah disetujui. Gunakan tombol "Ganti kata sandi sekarang".'
                : 'Permintaan sebelumnya masih menunggu persetujuan SDM/Admin.');
        }

        $permintaan = PermintaanGantiPassword::create(['user_id' => $user->id, 'alasan' => $v['alasan'] ?? null]);

        Notifikasi::kirim(
            User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_KEPALA_SDM])->get(),
            'Permintaan ganti kata sandi',
            "{$user->name} mengajukan penggantian kata sandi" . ($permintaan->alasan ? ": {$permintaan->alasan}" : '.'),
            route('admin.permintaan-password.index'),
            ikon: 'bi-key',
        );

        return back()->with('success', 'Permintaan terkirim. Setelah disetujui SDM/Admin, form ganti kata sandi akan muncul di akun Anda.');
    }

    /** Pemilik akun: ganti kata sandi memakai persetujuan yang sudah turun (sekali pakai). */
    public function ubah(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $izin = PermintaanGantiPassword::siapDipakai($user);
        if (! $izin) {
            return back()->with('error', 'Belum ada persetujuan ganti kata sandi untuk akun Anda. Ajukan dulu di halaman Profil.');
        }

        $request->validate([
            'password_lama' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers(), 'different:password_lama'],
        ], [
            'password_lama.required' => 'Isi kata sandi saat ini.',
            'password_lama.current_password' => 'Kata sandi saat ini salah.',
            'password.required' => 'Isi kata sandi baru.',
            'password.confirmed' => 'Ulangan kata sandi baru tidak sama.',
            'password.different' => 'Kata sandi baru harus berbeda dari kata sandi lama.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.letters' => 'Kata sandi baru harus mengandung huruf.',
            'password.numbers' => 'Kata sandi baru harus mengandung angka.',
        ]);

        DB::transaction(function () use ($user, $izin, $request) {
            $user->forceFill(['password' => $request->input('password'), 'password_diganti_pada' => now()])->save();
            $izin->update(['status' => PermintaanGantiPassword::STATUS_USED, 'dipakai_pada' => now()]);
        });
        $request->session()->regenerate();

        return back()->with('success', 'Kata sandi berhasil diganti. Gunakan kata sandi baru saat masuk berikutnya.');
    }

    /** Admin/Kepala SDM: daftar permintaan. */
    public function index(): View
    {
        $permintaan = PermintaanGantiPassword::with('user', 'pemroses')
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 ELSE 2 END")
            ->latest('id')->paginate(20);

        return view('admin.permintaan-password.index', [
            'permintaan' => $permintaan,
            'menunggu' => PermintaanGantiPassword::where('status', PermintaanGantiPassword::STATUS_PENDING)->count(),
        ]);
    }

    public function setujui(PermintaanGantiPassword $permintaan): RedirectResponse
    {
        if ($galat = $this->galatProses($permintaan)) {
            return back()->with('error', $galat);
        }
        $permintaan->update([
            'status' => PermintaanGantiPassword::STATUS_APPROVED,
            'diproses_oleh' => Auth::id(),
            'diproses_pada' => now(),
        ]);
        Notifikasi::kirim($permintaan->user, 'Ganti kata sandi disetujui',
            'Permintaan ganti kata sandi Anda disetujui. Buka Profil lalu pilih "Ganti kata sandi sekarang".',
            route('profile'), ikon: 'bi-key');

        return back()->with('success', "Permintaan {$permintaan->user->name} disetujui. Form ganti kata sandi muncul di akunnya.");
    }

    public function tolak(Request $request, PermintaanGantiPassword $permintaan): RedirectResponse
    {
        if ($galat = $this->galatProses($permintaan)) {
            return back()->with('error', $galat);
        }
        $v = $request->validate(['catatan' => ['required', 'string', 'max:500']], ['catatan.required' => 'Tulis alasan penolakan.']);
        $permintaan->update([
            'status' => PermintaanGantiPassword::STATUS_REJECTED,
            'diproses_oleh' => Auth::id(),
            'diproses_pada' => now(),
            'catatan' => $v['catatan'],
        ]);
        Notifikasi::kirim($permintaan->user, 'Ganti kata sandi ditolak', "Permintaan ganti kata sandi Anda ditolak: {$v['catatan']}", route('profile'), ikon: 'bi-key');

        return back()->with('success', "Permintaan {$permintaan->user->name} ditolak.");
    }

    private function galatProses(PermintaanGantiPassword $permintaan): ?string
    {
        /** @var User $pengatur */
        $pengatur = Auth::user();

        return match (true) {
            $permintaan->status !== PermintaanGantiPassword::STATUS_PENDING => 'Permintaan ini sudah diproses.',
            $permintaan->user_id === $pengatur->id => 'Permintaan sendiri tidak bisa Anda proses.',
            $permintaan->user->isAdmin() && ! $pengatur->isAdmin() => 'Hanya Admin yang bisa memproses permintaan akun Admin.',
            default => null,
        };
    }
}
