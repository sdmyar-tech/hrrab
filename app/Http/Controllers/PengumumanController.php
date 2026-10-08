<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Support\ExcelExporter;
use App\Support\Urutan;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PengumumanController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->user();
        $kategori = array_key_exists($request->query('kategori'), Pengumuman::KATEGORI) ? $request->query('kategori') : null;
        $arsip = $request->boolean('arsip');

        $query = Pengumuman::with('pembuat')->untuk($user)
            ->when(! $arsip, fn ($q) => $q->berlaku())
            ->when($arsip, fn ($q) => $q->whereNotNull('tampil_sampai')->whereDate('tampil_sampai', '<', now()))
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori))
            ->withExists(['pembaca as sudah_dibaca' => fn ($q) => $q->where('users.id', $user->id)]);

        $urut = Urutan::terapkan($query, [
            // Bawaan: yang disematkan penting tetap di atas, lalu terbaru.
            'penting' => ['Penting dulu, terbaru', fn ($q, $arah) => $q->orderByDesc('pengumumans.penting')->orderBy('pengumumans.created_at', $arah), 'desc'],
            ...Urutan::tanggal('dibuat', 'Diterbitkan', 'pengumumans.created_at'),
            ...Urutan::tanggal('acara', 'Tanggal acara', 'pengumumans.tanggal'),
            ...Urutan::abjad('judul', 'Judul', 'pengumumans.judul'),
        ]);
        $pengumumans = $query->paginate(12)->withQueryString();

        $this->tandaiDibaca($user, $pengumumans->getCollection()->where('sudah_dibaca', false)->pluck('id')->all());

        return view('pengumuman.index', [
            ...$urut,
            'layout' => $user->panelLayout(),
            'pengumumans' => $pengumumans,
            'kategori' => $kategori,
            'arsip' => $arsip,
            'bisaKelola' => $user->can(User::ABILITY_KELOLA_PENGUMUMAN),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $user = $this->user();
        abort_unless($user->can(User::ABILITY_KELOLA_PENGUMUMAN), 403);
        $kategori = array_key_exists($request->query('kategori'), Pengumuman::KATEGORI) ? $request->query('kategori') : null;

        $rows = Pengumuman::with('pembuat')->withCount('pembaca')->untuk($user)
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori))
            ->orderBy('id')->get()
            ->map(fn (Pengumuman $p) => [
                optional($p->created_at)->format('d-m-Y H:i'),
                $p->judul,
                $p->kategoriInfo()['label'],
                $p->jadwalLabel(),
                $p->lokasi,
                $p->sasaranLabel(),
                $p->penting ? 'Ya' : 'Tidak',
                optional($p->tampil_sampai)->format('d-m-Y'),
                $p->pembaca_count,
                $p->pembuat?->name,
                $p->isi,
            ]);

        return ExcelExporter::download(ExcelExporter::namaFile('pengumuman'), [
            'Dibuat', 'Judul', 'Kategori', 'Jadwal', 'Lokasi', 'Sasaran', 'Penting', 'Tampil Sampai', 'Dibaca', 'Pembuat', 'Isi',
        ], $rows, 'Pengumuman');
    }

    public function create(): View
    {
        $user = $this->user();
        abort_unless($user->can(User::ABILITY_KELOLA_PENGUMUMAN), 403);

        return view('pengumuman.form', ['layout' => $user->panelLayout(), 'pengumuman' => new Pengumuman(['kategori' => 'kegiatan', 'jadwal' => 'sekali']),
            'pilihanUnit' => $this->pilihanUnit($user), 'wajibUnit' => $user->isKepalaBagian()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->user();
        abort_unless($user->can(User::ABILITY_KELOLA_PENGUMUMAN), 403);

        Pengumuman::create([...$this->validasi($request, $user), 'dibuat_oleh' => $user->id]);

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman diterbitkan.');
    }

    public function edit(Pengumuman $pengumuman): View
    {
        $user = $this->user();
        $this->bolehUbah($user, $pengumuman);

        return view('pengumuman.form', ['layout' => $user->panelLayout(), 'pengumuman' => $pengumuman,
            'pilihanUnit' => $this->pilihanUnit($user), 'wajibUnit' => $user->isKepalaBagian()]);
    }

    public function update(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        $user = $this->user();
        $this->bolehUbah($user, $pengumuman);

        $pengumuman->update($this->validasi($request, $user));

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman diperbarui.');
    }

    public function destroy(Pengumuman $pengumuman): RedirectResponse
    {
        $user = $this->user();
        $this->bolehUbah($user, $pengumuman);

        $pengumuman->delete();

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman dihapus.');
    }

    public function bacaSemua(): RedirectResponse
    {
        $user = $this->user();
        $this->tandaiDibaca($user, Pengumuman::untuk($user)->berlaku()->belumDibaca($user)->pluck('id')->all());

        return back()->with('success', 'Semua pengumuman ditandai sudah dibaca.');
    }

private function validasi(Request $request, User $user): array
    {
        $pilihan = $this->pilihanUnit($user);

        $v = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'isi' => ['required', 'string', 'max:3000'],
            'kategori' => ['required', Rule::in(array_keys(Pengumuman::KATEGORI))],
            'jadwal' => ['required', Rule::in([Pengumuman::JADWAL_SEKALI, Pengumuman::JADWAL_HARIAN, Pengumuman::JADWAL_MINGGUAN])],
            'tanggal' => ['nullable', 'date', 'after_or_equal:today'],
            'hari' => ['required_if:jadwal,mingguan', 'nullable', 'integer', 'between:0,6'],
            'jam_mulai' => ['nullable', 'date_format:H:i'],
            'jam_selesai' => ['nullable', 'date_format:H:i', 'after:jam_mulai'],
            'lokasi' => ['nullable', 'string', 'max:150'],
            'sasaran_unit' => [$user->isKepalaBagian() ? 'required' : 'nullable', 'array'],
            'sasaran_unit.*' => [Rule::in($pilihan)],
            'penting' => ['nullable', 'boolean'],
            'tampil_sampai' => ['nullable', 'date', 'after_or_equal:today'],
        ], [
            'hari.required_if' => 'Pilih hari untuk kegiatan mingguan.',
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
            'sasaran_unit.required' => 'Pilih unit yang dituju.',
            'tanggal.after_or_equal' => 'Tanggal kegiatan tidak boleh di masa lalu.',
        ]);

        $sekali = $v['jadwal'] === Pengumuman::JADWAL_SEKALI;

        return [
            ...$v,
            'tanggal' => $sekali ? ($v['tanggal'] ?? null) : null,
            'hari' => $v['jadwal'] === Pengumuman::JADWAL_MINGGUAN ? $v['hari'] : null,
            'sasaran_unit' => array_values(array_unique($v['sasaran_unit'] ?? [])) ?: null,
            'penting' => $request->boolean('penting'),
            'tampil_sampai' => $v['tampil_sampai'] ?? ($sekali ? ($v['tanggal'] ?? now()->addDays(14)->toDateString()) : null),
        ];
    }

    private function pilihanUnit(User $user): array
    {
        return $user->isKepalaBagian() ? $user->unitsDipimpin() : config('kepegawaian.unit_kerja');
    }

    private function bolehUbah(User $user, Pengumuman $pengumuman): void
    {
        abort_unless($user->can(User::ABILITY_KELOLA_PENGUMUMAN)
            && ($pengumuman->dibuat_oleh === $user->id || $user->isAdmin() || $user->isKepalaSdm()), 403);
    }

    private function tandaiDibaca(User $user, array $ids): void
    {
        if ($ids) {
            DB::table('pengumuman_dibacas')->insertOrIgnore(array_map(fn ($id) => ['pengumuman_id' => $id, 'user_id' => $user->id, 'dibaca_pada' => now()], $ids));
        }
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
