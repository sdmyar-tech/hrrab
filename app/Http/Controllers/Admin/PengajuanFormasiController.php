<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\Lowongan;
use App\Models\PengajuanFormasi;
use App\Models\User;
use App\Support\ExcelExporter;
use App\Support\Notifikasi;
use App\Support\RekrutmenAccess;
use App\Support\Urutan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PengajuanFormasiController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $query = $this->filteredQuery($request, $user);
        $urut = Urutan::terapkan($query, [
            ...Urutan::tanggal('diajukan', 'Diajukan', 'pengajuan_formasis.created_at'),
            ...Urutan::tanggal('target', 'Target waktu', 'pengajuan_formasis.target_waktu'),
            ...Urutan::abjad('jabatan', 'Jabatan', 'pengajuan_formasis.jabatan'),
            ...Urutan::abjad('unit', 'Unit', 'pengajuan_formasis.unit_kerja'),
        ]);
        $pengajuanFormasis = $query->paginate(15)->withQueryString();

        return view('admin.pengajuan-formasi.index', [
            'pengajuanFormasis' => $pengajuanFormasis,
            'canReview' => RekrutmenAccess::canReview($user),
            'canSubmit' => RekrutmenAccess::bisaAjukanFormasi($user),
            ...$urut,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $rows = $this->filteredQuery($request, $user)->orderBy('id')->get()->map(fn (PengajuanFormasi $f) => [
            optional($f->created_at)->format('d-m-Y H:i'),
            $f->diajukanOleh?->name,
            $f->unit_kerja,
            $f->jenisLabel(),
            $f->jabatan,
            $f->jumlah_dibutuhkan,
            $f->jumlah_laki,
            $f->jumlah_perempuan,
            $f->jumlah_bebas,
            optional($f->target_waktu)->format('d-m-Y'),
            $f->min_pendidikan,
            $f->min_usia,
            $f->kualifikasi,
            $f->atasan_langsung,
            $f->tugas,
            $f->alasan,
            $f->statusLabel(),
            $f->gmPenyetuju?->name,
            $f->ownerPenyetuju?->name,
            $f->direviewOleh?->name,
            optional($f->direview_pada)->format('d-m-Y'),
            $f->catatan_admin,
            $f->lowongan?->judul,
        ]);

        return ExcelExporter::download(ExcelExporter::namaFile('pengajuan-formasi'), [
            'Tanggal Pengajuan', 'Diajukan Oleh', 'Unit', 'Kebutuhan Karena', 'Jabatan', 'Jumlah', 'Laki-laki', 'Perempuan', 'Bebas (L/P)',
            'Tanggal Dibutuhkan', 'Min. Pendidikan', 'Min. Usia', 'Kemampuan/Keahlian', 'Atasan Langsung', 'Tugas & Tanggung Jawab', 'Keterangan',
            'Status', 'Disetujui GM', 'Disetujui Owner', 'Direview Oleh', 'Tanggal Review', 'Catatan', 'Lowongan Terbit',
        ], $rows, 'Pengajuan Formasi');
    }

    private function filteredQuery(Request $request, User $user)
    {
        $query = PengajuanFormasi::with(['diajukanOleh.dataPegawai.kepegawaian', 'direviewOleh', 'lowongan', 'gmPenyetuju', 'ownerPenyetuju']);

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

        return $query;
    }

    public function create(): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! RekrutmenAccess::bisaAjukanFormasi($user)) {
            return redirect()
                ->route('admin.pengajuan-formasi.index')
                ->with('error', 'Hanya Kepala Bagian/Koordinator dan Kepala SDM yang dapat mengajukan permintaan kekurangan pegawai.');
        }

        $units = RekrutmenAccess::unitAjuanFormasi($user);

        return view('admin.pengajuan-formasi.create', [
            'unitOptions' => $units,
            // Jabatan per unit: dropdown jabatan hanya menampilkan jabatan yang ada di unit terpilih.
            'jabatanPerUnit' => collect($units)->mapWithKeys(fn ($u) => [$u => $user->isAdmin() ? Jabatan::daftar() : RekrutmenAccess::jabatanUnit($u)])->all(),
            // Atasan langsung = pengaju (readonly); Admin memilih dari daftar kepala & GM.
            'atasanBawaan' => $this->namaAtasan($user),
            'pilihanAtasan' => $user->isAdmin() ? $this->pilihanAtasan() : [],
            // Bagan unit (kepala + pegawai per jabatan) untuk memilih pegawai yang digantikan / posisi baru.
            'baganPerUnit' => $bagan = collect($units)->mapWithKeys(fn ($u) => [$u => RekrutmenAccess::baganUnit($u)])->all(),
            // Replacement: klik pegawai di bagan → jabatan terisi otomatis (nama jabatan bagan → nama di Master Jabatan).
            'jabatanBaganPerUnit' => collect($bagan)->map(fn ($b) => $this->jabatanBagan($b))->all(),
        ]);
    }

    /**
     * Jabatan cabang bagan (huruf besar) yang terdaftar di Master Jabatan.
     *
     * @param  array{grup: array<string, mixed>}  $bagan
     * @return array<string, string> jabatan bagan => nama di Master Jabatan
     */
    private function jabatanBagan(array $bagan): array
    {
        $master = collect(Jabatan::daftar())->keyBy(fn ($j) => mb_strtoupper(trim($j)));

        return collect(array_keys($bagan['grup']))->filter(fn ($j) => $master->has($j))
            ->mapWithKeys(fn ($j) => [$j => $master[$j]])->all();
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (! RekrutmenAccess::bisaAjukanFormasi($user)) {
            abort(403, 'Hanya Kepala Bagian/Koordinator dan Kepala SDM yang dapat mengajukan permintaan kekurangan pegawai.');
        }

        $unitBoleh = RekrutmenAccess::unitAjuanFormasi($user);
        // Satu unit → terkunci ke unit itu; memimpin beberapa unit → pilih salah satunya (divalidasi di bawah).
        if (count($unitBoleh) === 1 && ! $user->isAdmin()) {
            $request->merge(['unit_kerja' => $unitBoleh[0]]);
        }
        $unit = (string) $request->input('unit_kerja');
        // Atasan langsung tidak bisa diubah pengaju (selalu dirinya); hanya Admin yang memilih.
        if (! $user->isAdmin()) {
            $request->merge(['atasan_langsung' => $this->namaAtasan($user)]);
        }
        $bagan = in_array($unit, $unitBoleh, true) ? RekrutmenAccess::baganUnit($unit) : ['grup' => []];
        $idBagan = collect($bagan['grup'])->flatten(1)->pluck('id')->all();
        $jabatanBoleh = $user->isAdmin() ? Jabatan::daftar() : (in_array($unit, $unitBoleh, true) ? RekrutmenAccess::jabatanUnit($unit) : []);
        // Replacement: jabatan pegawai yang digantikan (termasuk yang sudah tidak aktif) juga sah.
        if ($request->input('jenis_kebutuhan') === PengajuanFormasi::JENIS_REPLACEMENT) {
            $jabatanBoleh = array_values(array_unique([...$jabatanBoleh, ...array_values($this->jabatanBagan($bagan))]));
        }

        $validated = $request->validate([
            'unit_kerja' => ['required', 'string', Rule::in($unitBoleh)],
            'jenis_kebutuhan' => ['required', Rule::in(array_keys(PengajuanFormasi::JENIS_LABEL))],
            'jumlah_laki' => ['required', 'integer', 'min:0', 'max:50'],
            'jumlah_perempuan' => ['required', 'integer', 'min:0', 'max:50'],
            'jumlah_bebas' => ['nullable', 'integer', 'min:0', 'max:50'],
            // Replacement: pegawai lama yang digantikan (klik di bagan). Penambahan: keterangan tiap posisi baru.
            'diganti' => ['required_if:jenis_kebutuhan,replacement', 'array'],
            'diganti.*' => ['integer', Rule::in($idBagan)],
            'slot_tambahan' => ['nullable', 'array', 'max:50'],
            'slot_tambahan.*' => ['nullable', 'string', 'max:150'],
            'target_waktu' => ['required', 'date', ...($user->isAdmin() ? [] : ['after_or_equal:today'])],
            'min_pendidikan' => ['required', Rule::in(PengajuanFormasi::PENDIDIKAN)],
            'min_usia' => ['nullable', 'integer', 'min:15', 'max:70'],
            'kualifikasi' => ['required', 'string', 'max:2000'],
            'jabatan' => ['required', 'string', Rule::in($jabatanBoleh)],
            'atasan_langsung' => ['required', 'string', 'max:150', ...($user->isAdmin() ? [Rule::in(array_keys($this->pilihanAtasan()))] : [])],
            // Daftar tugas (satu baris per tugas dari formulir); disimpan sebagai teks per baris.
            'tugas' => ['required', 'array', 'min:1', 'max:30'],
            'tugas.*' => ['nullable', 'string', 'max:500'],
        ], [
            'unit_kerja.required' => 'Pilih unit yang membutuhkan pegawai.',
            'unit_kerja.in' => 'Unit harus unit yang Anda pimpin.',
            'diganti.required_if' => 'Pilih pegawai yang digantikan pada bagan unit.',
            'diganti.*.in' => 'Pegawai yang digantikan harus dari unit ini.',
            'jenis_kebutuhan.required' => 'Pilih alasan kebutuhan: replacement atau penambahan karyawan.',
            'jabatan.in' => 'Jabatan harus jabatan yang ada di unit ' . ($unit ?: 'Anda') . '.',
            'target_waktu.required' => 'Isi tanggal dibutuhkan.',
            'target_waktu.after_or_equal' => 'Tanggal dibutuhkan tidak boleh sebelum hari ini.',
            'min_pendidikan.required' => 'Pilih minimal pendidikan.',
            'kualifikasi.required' => 'Isi minimal kemampuan / keahlian yang dibutuhkan.',
            'atasan_langsung.required' => 'Isi atasan langsung.',
            'atasan_langsung.in' => 'Atasan langsung harus kepala atau GM yang terdaftar.',
            'tugas.required' => 'Isi tugas dan tanggung jawab.',
            'tugas.max' => 'Maksimal 30 tugas.',
            'tugas.*.max' => 'Satu tugas maksimal 500 karakter.',
        ]);

        $tugas = array_values(array_filter(array_map(fn ($t) => trim((string) $t), $validated['tugas']), 'strlen'));
        if (! $tugas) {
            throw ValidationException::withMessages(['tugas' => 'Isi minimal satu tugas dan tanggung jawab.']);
        }
        $validated['tugas'] = implode("\n", $tugas);

        $jumlah = (int) $validated['jumlah_laki'] + (int) $validated['jumlah_perempuan'] + (int) ($validated['jumlah_bebas'] ?? 0);
        if ($jumlah < 1 || $jumlah > 50) {
            throw ValidationException::withMessages(['jumlah_laki' => 'Jumlah kebutuhan (laki-laki + perempuan + bebas) harus 1–50 orang.']);
        }

        // Isi keterangan dari bagan: siapa yang digantikan / keterangan posisi baru.
        $diganti = [];
        $slot = [];
        if ($validated['jenis_kebutuhan'] === PengajuanFormasi::JENIS_REPLACEMENT) {
            $peta = collect($bagan['grup'])->flatMap(fn ($g, $jab) => collect($g)->map(fn ($o) => $o + ['jabatan' => $jab]))->keyBy('id');
            $diganti = collect($validated['diganti'] ?? [])->unique()->map(fn ($id) => $peta[$id])->values()->all();
            if (count($diganti) !== $jumlah) {
                throw ValidationException::withMessages(['diganti' => 'Jumlah pegawai yang digantikan (' . count($diganti) . ") harus sama dengan jumlah kebutuhan ({$jumlah} orang)."]);
            }
            // Satu pengajuan untuk satu jabatan: pegawai yang digantikan sejabatan, dan jabatan pengajuan = jabatan mereka
            // (bila jabatannya terdaftar di Master Jabatan). Admin bebas menyesuaikan.
            if (! $user->isAdmin()) {
                $jabatanDiganti = collect($diganti)->pluck('jabatan')->unique();
                if ($jabatanDiganti->count() > 1) {
                    throw ValidationException::withMessages(['diganti' => 'Pegawai yang digantikan harus dari satu jabatan (sekarang: ' . $jabatanDiganti->implode(', ') . ').']);
                }
                $namaMaster = $this->jabatanBagan($bagan)[$jabatanDiganti->first()] ?? null;
                if ($namaMaster && mb_strtoupper($validated['jabatan']) !== mb_strtoupper($namaMaster)) {
                    throw ValidationException::withMessages(['jabatan' => "Jabatan harus sesuai jabatan pegawai yang digantikan ({$namaMaster})."]);
                }
            }
        } else {
            $slot = array_slice(array_pad(array_map(fn ($s) => trim((string) $s), $validated['slot_tambahan'] ?? []), $jumlah, ''), 0, $jumlah);
        }
        $ringkas = $diganti
            ? 'Menggantikan: ' . collect($diganti)->map(fn ($o) => $o['nama'] . ' (' . ($o['aktif'] ? 'aktif' : 'tidak aktif') . ')')->implode(', ')
            : "Penambahan {$jumlah} posisi baru" . (array_filter($slot) ? ': ' . implode('; ', array_filter($slot)) : '');

        $formasi = $user->pengajuanFormasis()->create([
            'unit_kerja' => $unit,
            'jumlah_dibutuhkan' => $jumlah,
            'jumlah_bebas' => (int) ($validated['jumlah_bebas'] ?? 0),
            'diganti' => $diganti ?: null,
            'slot_tambahan' => $slot ?: null,
            'alasan' => $ringkas,
            'status' => PengajuanFormasi::STATUS_PENDING,
        ] + collect($validated)->except(['unit_kerja', 'diganti', 'slot_tambahan', 'alasan', 'jumlah_bebas'])->all());
        Notifikasi::formasiBaru($formasi);

        $penyetuju = $formasi->butuhDuaPersetujuan()
            ? (RekrutmenAccess::perluGm($formasi) ? 'GM unit, lalu Owner' : 'Owner')
            : ($user->isKepalaSdm() ? 'GM' : 'Kepala SDM/GM');

        return redirect()
            ->route('admin.pengajuan-formasi.index')
            ->with('success', "Pengajuan formasi berhasil dikirim. Menunggu persetujuan {$penyetuju}.");
    }

    public function approve(PengajuanFormasi $pengajuanFormasi): RedirectResponse
    {
        /** @var User $reviewer */
        $reviewer = Auth::user();

        if (! RekrutmenAccess::canReviewFormasi($reviewer, $pengajuanFormasi)) {
            abort(403, 'Anda tidak berwenang mereview pengajuan formasi ini.');
        }

        if (! $pengajuanFormasi->isPending() && ! $reviewer->isAdmin()) {
            return redirect()->back()->with('error', 'Pengajuan formasi ini sudah diproses.');
        }

        // Penambahan karyawan bertahap: GM unit → Owner. Final (lowongan terbit) setelah tahap Owner.
        if ($pengajuanFormasi->butuhDuaPersetujuan() && ! $reviewer->isAdmin()) {
            $sisi = RekrutmenAccess::sisiPersetujuan($reviewer, $pengajuanFormasi);
            $pengajuanFormasi->update([
                "{$sisi}_disetujui_oleh" => $reviewer->id,
                "{$sisi}_disetujui_pada" => now(),
            ]);

            if (RekrutmenAccess::tahapPersetujuan($pengajuanFormasi) === 'owner') {
                Notifikasi::formasiBaru($pengajuanFormasi, 'Formasi menunggu persetujuan Owner');

                return redirect()->route('admin.pengajuan-formasi.index')
                    ->with('success', 'Persetujuan GM tercatat. Diteruskan ke Owner untuk persetujuan akhir.');
            }
        }

        $pengajuanFormasi->update([
            'status' => PengajuanFormasi::STATUS_APPROVED,
            'direview_oleh' => $reviewer->id,
            'direview_pada' => now(),
        ]);

        // Admin bisa menyetujui ulang formasi yang sudah diproses; lowongan tidak dibuat dobel.
        if (! $pengajuanFormasi->lowongan()->exists()) {
            $pengajuanFormasi->lowongan()->create([
                'judul' => $pengajuanFormasi->jabatan,
                'unit_kerja' => $pengajuanFormasi->unit_kerja,
                'jabatan' => $pengajuanFormasi->jabatan,
                'jumlah_dibutuhkan' => $pengajuanFormasi->jumlah_dibutuhkan,
                'deskripsi' => $pengajuanFormasi->tugas ?: $pengajuanFormasi->alasan,
                'kualifikasi' => $this->kualifikasiLowongan($pengajuanFormasi),
                'tanggal_tutup' => $pengajuanFormasi->target_waktu,
                'status' => Lowongan::STATUS_DIPUBLIKASIKAN,
                'dipublikasikan_oleh' => $reviewer->id,
                'dipublikasikan_pada' => now(),
            ]);
        }
        Notifikasi::formasiDiputuskan($pengajuanFormasi);

        return redirect()
            ->route('admin.pengajuan-formasi.index')
            ->with('success', 'Pengajuan formasi disetujui. Lowongan rekrutmen telah terbit di halaman publik.');
    }

    /** Nama atasan yang tercatat untuk akun: nama di Data Pegawai, atau nama akun bila tidak tertaut. */
    private function namaAtasan(User $user): string
    {
        return $user->dataPegawai?->nama_lengkap ?? $user->name;
    }

    /**
     * Pilihan atasan langsung untuk Admin: semua Kepala Bagian, Kepala SDM, dan GM.
     *
     * @return array<string, string> nama => label (nama — peran · unit dipimpin)
     */
    private function pilihanAtasan(): array
    {
        return once(fn () => User::with('dataPegawai:id,nama_lengkap')
            ->whereIn('role', [User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM, User::ROLE_GM])
            ->get()
            ->mapWithKeys(fn (User $u) => [$this->namaAtasan($u) => $this->namaAtasan($u) . ' — ' . $u->roleLabel()
                . (($unit = $u->unitsDipimpin()) ? ' · ' . implode(', ', $unit) : '')])
            ->sortKeys()
            ->all());
    }

    /** Kualifikasi lowongan dari persyaratan formulir (pendidikan, usia, keahlian). */
    private function kualifikasiLowongan(PengajuanFormasi $f): ?string
    {
        $baris = array_filter([
            $f->min_pendidikan ? "Pendidikan minimal {$f->min_pendidikan}" : null,
            $f->min_usia ? "Usia minimal {$f->min_usia} tahun" : null,
            $f->jumlah_laki || $f->jumlah_perempuan ? 'Dibutuhkan: ' . $f->jumlahLabel() : null,
            $f->kualifikasi,
        ]);

        return $baris ? implode("\n", $baris) : null;
    }

    public function reject(Request $request, PengajuanFormasi $pengajuanFormasi): RedirectResponse
    {
        /** @var User $reviewer */
        $reviewer = Auth::user();

        if (! RekrutmenAccess::canReviewFormasi($reviewer, $pengajuanFormasi)) {
            abort(403, 'Anda tidak berwenang mereview pengajuan formasi ini.');
        }

        if (! $pengajuanFormasi->isPending() && ! $reviewer->isAdmin()) {
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

    public function destroy(PengajuanFormasi $pengajuanFormasi): RedirectResponse
    {
        $pengajuanFormasi->delete();

        return redirect()->route('admin.pengajuan-formasi.index')->with('success', "Pengajuan formasi {$pengajuanFormasi->jabatan} ({$pengajuanFormasi->unit_kerja}) dihapus.");
    }

    public function batalkan(PengajuanFormasi $pengajuanFormasi): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if ($pengajuanFormasi->diajukan_oleh !== $user->id && ! $user->isAdmin()) {
            abort(403, 'Anda hanya bisa membatalkan pengajuan formasi milik sendiri.');
        }

        if (! $pengajuanFormasi->isPending() && ! $user->isAdmin()) {
            return redirect()->back()->with('error', 'Pengajuan formasi ini sudah diproses dan tidak bisa dibatalkan.');
        }

        $pengajuanFormasi->update(['status' => PengajuanFormasi::STATUS_DIBATALKAN]);

        return redirect()
            ->route('admin.pengajuan-formasi.index')
            ->with('success', 'Pengajuan formasi dibatalkan.');
    }
}
