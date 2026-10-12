<?php

namespace App\Http\Controllers;

use App\Models\Pengumuman;
use App\Models\PengumumanPeserta;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Support\AbsenAcara;
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
            ->withExists(['pembaca as sudah_dibaca' => fn ($q) => $q->where('users.id', $user->id)])
            // Baris absen milik pengguna ini (untuk tombol 'Absen Sekarang') + rekap untuk pengelola.
            ->with(['peserta' => fn ($q) => $q->where('user_id', $user->id)])
            ->withCount(['peserta as peserta_total', 'peserta as peserta_sudah' => fn ($q) => $q->where('status', '!=', PengumumanPeserta::STATUS_BELUM)]);

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
            'bisaPantauAbsen' => AbsenAcara::bolehKelola($user),
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $user = $this->user();
        abort_unless($user->can(User::ABILITY_KELOLA_PENGUMUMAN), 403);
        $kategori = array_key_exists($request->query('kategori'), Pengumuman::KATEGORI) ? $request->query('kategori') : null;

        $rows = Pengumuman::with('pembuat')->withCount(['pembaca', 'peserta as peserta_total',
            'peserta as peserta_sudah' => fn ($q) => $q->where('status', '!=', PengumumanPeserta::STATUS_BELUM)])->untuk($user)
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
                $p->absen_aktif ? "{$p->peserta_sudah}/{$p->peserta_total} hadir" : '-',
                $p->pembuat?->name,
                $p->isi,
            ]);

        return ExcelExporter::download(ExcelExporter::namaFile('pengumuman'), [
            'Dibuat', 'Judul', 'Kategori', 'Jadwal', 'Lokasi', 'Sasaran', 'Penting', 'Tampil Sampai', 'Dibaca', 'Absen Acara', 'Pembuat', 'Isi',
        ], $rows, 'Pengumuman');
    }

    public function create(): View
    {
        $user = $this->user();
        abort_unless($user->can(User::ABILITY_KELOLA_PENGUMUMAN), 403);

        return view('pengumuman.form', ['layout' => $user->panelLayout(), 'pengumuman' => new Pengumuman(['kategori' => 'kegiatan', 'jadwal' => 'sekali']),
            'pilihanUnit' => $this->pilihanUnit($user), 'wajibUnit' => $user->isKepalaBagian(), 'absen' => $this->dataFormAbsen($user)]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->user();
        abort_unless($user->can(User::ABILITY_KELOLA_PENGUMUMAN), 403);

        $data = $this->validasi($request, $user);
        $absen = $this->validasiAbsen($request, $user, null, $data); // bisa memperpanjang tampil_sampai
        $pengumuman = Pengumuman::create([...$data, ...$absen, 'dibuat_oleh' => $user->id]);

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman diterbitkan.' . $this->setelahSimpanAbsen($pengumuman, $user));
    }

    public function edit(Pengumuman $pengumuman): View
    {
        $user = $this->user();
        $this->bolehUbah($user, $pengumuman);

        return view('pengumuman.form', ['layout' => $user->panelLayout(), 'pengumuman' => $pengumuman,
            'pilihanUnit' => $this->pilihanUnit($user), 'wajibUnit' => $user->isKepalaBagian(), 'absen' => $this->dataFormAbsen($user)]);
    }

    public function update(Request $request, Pengumuman $pengumuman): RedirectResponse
    {
        $user = $this->user();
        $this->bolehUbah($user, $pengumuman);

        $data = $this->validasi($request, $user);
        $absen = $this->validasiAbsen($request, $user, $pengumuman, $data);
        $pengumuman->update([...$data, ...$absen]);

        return redirect()->route('pengumuman.index')->with('success', 'Pengumuman diperbarui.' . $this->setelahSimpanAbsen($pengumuman, $user));
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

    /**
     * Data form "Absen Acara" (hanya untuk SDM/Admin/GM).
     *
     * @return array{kandidat: \Illuminate\Support\Collection, unit: array<string, int>, jabatan: array<string, int>}|null
     */
    private function dataFormAbsen(User $user): ?array
    {
        return AbsenAcara::bolehKelola($user) ? AbsenAcara::pilihan($user) : null;
    }

    /**
     * Validasi bagian absen acara. Pengguna tanpa hak absen tidak bisa mengubah pengaturan absen yang ada.
     *
     * @param  array<string, mixed>  $data  hasil validasi pengumuman (untuk menyesuaikan tampil_sampai)
     * @return array<string, mixed>
     */
    private function validasiAbsen(Request $request, User $user, ?Pengumuman $lama, array &$data): array
    {
        if (! AbsenAcara::bolehKelola($user)) {
            return [];
        }
        if (! $request->boolean('absen_aktif')) {
            return ['absen_aktif' => false];
        }

        $pilihan = AbsenAcara::pilihan($user);
        $tutupLama = $lama?->absen_tutup?->format('Y-m-d\TH:i');
        // Jam tutup boleh di masa lalu hanya bila tidak diubah (mengedit acara yang sudah selesai).
        $tutupBerubah = $request->input('absen_tutup') !== $tutupLama;

        $v = $request->validate([
            'absen_buka' => ['required', 'date_format:Y-m-d\TH:i'],
            'absen_tutup' => ['required', 'date_format:Y-m-d\TH:i', 'after:absen_buka', ...($tutupBerubah ? ['after:now'] : [])],
            'absen_lat' => ['required', 'numeric', 'between:-90,90'],
            'absen_lng' => ['required', 'numeric', 'between:-180,180'],
            'absen_radius' => ['required', 'integer', 'between:' . AbsenAcara::RADIUS_MIN . ',' . AbsenAcara::RADIUS_MAKS],
            'absen_semua' => ['nullable', 'boolean'],
            'absen_unit' => ['nullable', 'array'],
            'absen_unit.*' => [Rule::in(array_keys($pilihan['unit']))],
            'absen_jabatan' => ['nullable', 'array'],
            'absen_jabatan.*' => [Rule::in(array_keys($pilihan['jabatan']))],
            'absen_user' => ['nullable', 'array'],
            'absen_user.*' => ['integer', Rule::in($pilihan['kandidat']->pluck('id')->all())],
        ], [
            'absen_buka.required' => 'Isi jam absen dibuka.',
            'absen_tutup.required' => 'Isi jam absen ditutup.',
            'absen_tutup.after' => 'Jam absen ditutup harus setelah jam dibuka dan belum lewat.',
            'absen_lat.required' => 'Tentukan titik lokasi acara di peta.',
            'absen_lng.required' => 'Tentukan titik lokasi acara di peta.',
            'absen_radius.between' => 'Radius absen ' . AbsenAcara::RADIUS_MIN . '–' . AbsenAcara::RADIUS_MAKS . ' meter.',
            'absen_unit.*.in' => 'Unit peserta tidak valid.',
            'absen_jabatan.*.in' => 'Jabatan peserta tidak valid.',
            'absen_user.*.in' => 'Ada pegawai yang tidak bisa dipilih.',
        ]);

        $kriteria = [
            'semua' => $request->boolean('absen_semua'),
            'unit' => array_values(array_unique($v['absen_unit'] ?? [])),
            'jabatan' => array_values(array_unique($v['absen_jabatan'] ?? [])),
            'user' => array_values(array_unique(array_map('intval', $v['absen_user'] ?? []))),
        ];
        if (AbsenAcara::terapkanKriteria($kriteria, $pilihan['kandidat'])->isEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages(['absen_peserta' => 'Pilih peserta absen: semua, per unit, per jabatan, atau nama pegawai.']);
        }

        // Pengumuman harus tetap tampil sampai absen ditutup.
        $tutup = \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $v['absen_tutup']);
        if (! empty($data['tampil_sampai']) && $tutup->toDateString() > $data['tampil_sampai']) {
            $data['tampil_sampai'] = $tutup->toDateString();
        }

        return [
            'absen_aktif' => true,
            'absen_buka' => \Carbon\Carbon::createFromFormat('Y-m-d\TH:i', $v['absen_buka']),
            'absen_tutup' => $tutup,
            'absen_lat' => round((float) $v['absen_lat'], 7),
            'absen_lng' => round((float) $v['absen_lng'], 7),
            'absen_radius' => (int) $v['absen_radius'],
            'absen_kriteria' => $kriteria,
        ];
    }

    /** Buat kunci QR, sinkronkan peserta, dan undang peserta baru. Mengembalikan keterangan untuk pesan sukses. */
    private function setelahSimpanAbsen(Pengumuman $pengumuman, User $user): string
    {
        if (! $pengumuman->absen_aktif || ! AbsenAcara::bolehKelola($user)) {
            return '';
        }

        AbsenAcara::pastikanKunci($pengumuman);
        $baru = AbsenAcara::sinkronPeserta($pengumuman, $user);
        AbsenAcara::undang($pengumuman, $baru);
        $total = $pengumuman->peserta()->count();

        return " Absen acara aktif untuk {$total} pegawai" . ($baru->isNotEmpty() ? " ({$baru->count()} diberi notifikasi)." : '.');
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
