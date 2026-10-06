<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use App\Support\KelengkapanData;
use App\Support\Notifikasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotifikasiController extends Controller
{
    public const TARGET_BELUM_LENGKAP = 'belum_lengkap';
    public const TARGET_UNIT = 'unit';
    public const TARGET_ROLE = 'role';
    public const TARGET_PEGAWAI = 'pegawai';
    public const TARGET_SEMUA = 'semua';

    public function index(Request $request): View
    {
        $user = $this->user();
        $belum = $request->query('tab') === 'belum';

        $notifikasi = ($belum ? $user->unreadNotifications() : $user->notifications())->paginate(15)->withQueryString();

        return view('notifikasi.index', [
            'layout' => $user->panelLayout(),
            'notifikasi' => $notifikasi,
            'belum' => $belum,
            'jumlahBelum' => $user->unreadNotifications()->count(),
        ]);
    }

    public function buka(string $id): RedirectResponse
    {
        $notif = $this->user()->notifications()->findOrFail($id);
        $notif->markAsRead();

        return $notif->data['url'] ?? null
            ? redirect()->to($notif->data['url'])
            : redirect()->route('notifikasi.index');
    }

    public function bacaSemua(): RedirectResponse
    {
        $this->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Semua notifikasi ditandai sudah dibaca.');
    }

    public function ringkas(): JsonResponse
    {
        $user = $this->user();
        $notif = $user->notifications()->latest()->take(8)->get();

        return response()->json([
            'notifikasi_belum' => $user->unreadNotifications()->count(),
            'pengumuman_belum' => Pengumuman::untuk($user)->berlaku()->belumDibaca($user)->count(),
            'terbaru' => $notif->map(fn ($n) => [
                'id' => $n->id,
                'judul' => $n->data['judul'] ?? '',
                'pesan' => $n->data['pesan'] ?? '',
                'ikon' => $n->data['ikon'] ?? 'bi-bell',
                'baru' => $n->read_at === null,
                'waktu' => $n->created_at->diffForHumans(),
                'url' => route('notifikasi.buka', $n->id),
            ]),
        ]);
    }

public function kirimForm(): View
    {
        $user = $this->user();
        abort_unless($user->can(User::ABILITY_KIRIM_NOTIFIKASI), 403);

        $lingkup = $this->lingkup($user)->with('dataPegawai.kepegawaian')->orderBy('name')->get();

        return view('notifikasi.kirim', [
            'layout' => $user->panelLayout(),
            'pilihanPegawai' => $lingkup,
            'pilihanUnit' => $this->unitLingkup($user),
            'bisaPilihRole' => $user->isAdmin() || $user->isKepalaSdm(),
            'belumLengkap' => $this->belumLengkap($user),
        ]);
    }

    public function kirim(Request $request): RedirectResponse
    {
        $user = $this->user();
        abort_unless($user->can(User::ABILITY_KIRIM_NOTIFIKASI), 403);

        $targets = [self::TARGET_BELUM_LENGKAP, self::TARGET_UNIT, self::TARGET_PEGAWAI, self::TARGET_SEMUA];
        if ($user->isAdmin() || $user->isKepalaSdm()) {
            $targets[] = self::TARGET_ROLE;
        }

        $v = $request->validate([
            'target' => ['required', Rule::in($targets)],
            'unit' => ['required_if:target,unit', 'array'],
            'unit.*' => [Rule::in($this->unitLingkup($user))],
            'role' => ['required_if:target,role', 'array'],
            'role.*' => [Rule::in(User::INTERNAL_ROLES)],
            'pegawai' => ['required_if:target,pegawai', 'array'],
            'pegawai.*' => ['integer'],
            'judul' => ['required', 'string', 'max:120'],
            'pesan' => ['required', 'string', 'max:1000'],
            'jenis' => ['required', Rule::in([Pemberitahuan::JENIS_PENGINGAT, Pemberitahuan::JENIS_TUGAS, Pemberitahuan::JENIS_INFO])],
        ], [
            'unit.required_if' => 'Pilih minimal satu unit.',
            'role.required_if' => 'Pilih minimal satu role.',
            'pegawai.required_if' => 'Pilih minimal satu pegawai.',
        ]);

        $lingkup = $this->lingkup($user);
        $penerima = match ($v['target']) {
            self::TARGET_BELUM_LENGKAP => $this->belumLengkap($user)->pluck('user'),
            self::TARGET_UNIT => $lingkup->whereHas('dataPegawai.penugasanAktif', fn ($q) => $q->whereIn('unit_kerja', $v['unit']))->get(),
            self::TARGET_ROLE => $lingkup->whereIn('role', $v['role'])->get(),
            self::TARGET_PEGAWAI => $lingkup->whereIn('id', $v['pegawai'])->get(),
            default => $lingkup->get(),
        };

        $jumlah = 0;
        foreach ($penerima->groupBy(fn (User $u) => $v['target'] === self::TARGET_BELUM_LENGKAP && $u->isStaff()) as $keStaff => $kelompok) {
            $jumlah += Notifikasi::kirim($kelompok, $v['judul'], $v['pesan'], $keStaff ? route('pegawai.pengkinian-data') : null,
                $v['jenis'], $v['target'] === self::TARGET_BELUM_LENGKAP ? 'bi-person-vcard' : 'bi-megaphone');
        }

        return back()->with($jumlah ? 'success' : 'error', $jumlah
            ? "Notifikasi terkirim ke {$jumlah} akun."
            : 'Tidak ada akun penerima yang cocok.');
    }

    private function lingkup(User $user): Builder
    {
        $units = $this->unitTerbatas($user);

        return User::whereIn('role', User::INTERNAL_ROLES)->where('id', '!=', $user->id)
            ->when($units, fn ($q) => $q->whereHas('dataPegawai.penugasanAktif', fn ($p) => $p->whereIn('unit_kerja', $units)));
    }

    private function unitTerbatas(User $user): ?array
    {
        return match (true) {
            $user->isKepalaBagian() => $user->unitsDipimpinUntukQuery(),
            $user->isGm() && ! empty($user->divisi) => array_values($user->divisi),
            default => null,
        };
    }

    private function unitLingkup(User $user): array
    {
        return $this->unitTerbatas($user) ?? config('kepegawaian.unit_kerja');
    }

    /**
     * @return Collection<int, array{user: User, kurang: list<string>}>
     */
    private function belumLengkap(User $user): Collection
    {
        return $this->lingkup($user)->whereNotNull('data_pegawai_id')
            ->with('dataPegawai.kepegawaian', 'dataPegawai.kontakDarurat', 'dataPegawai.dokumen')
            ->orderBy('name')->get()
            ->map(fn (User $u) => ['user' => $u, 'kurang' => KelengkapanData::kurang($u->dataPegawai)])
            ->filter(fn ($r) => $r['kurang'] !== [])
            ->values();
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
