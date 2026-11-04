<?php

namespace App\Support;

use App\Models\AlurPegawai;
use App\Models\Jabatan;
use App\Models\Kepegawaian;
use App\Models\PengajuanCuti;
use App\Models\PengajuanIzinKeluar;
use App\Models\PengajuanLembur;
use App\Models\Penugasan;
use App\Models\PersetujuanTahap;
use App\Models\TanggaAlur;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Mesin Tangga Alur Pengajuan: tangga mana yang berlaku, siapa penyetuju tiap tahap, dan kemajuan persetujuannya.
 * Dipakai PengajuanWorkflow saat current_level = tangga; alur bawaan (Kepala Bagian → GM → Kepala SDM) tidak berubah.
 */
class TanggaAlurWorkflow
{
    /** @var array<string, mixed> hasil hitung per request (penyetuju per tahap, id terlihat, nama unit/jabatan) */
    private static array $memo = [];

    public static function lupakan(): void
    {
        self::$memo = [];
    }

    /**
     * Tangga untuk pengajuan baru $user di $unit; null bila pegawai punya alur khusus, pengajunya GM, atau bagian belum punya tangga.
     * Koordinator bagian itu memakai tangga "koordinator", pegawai lain tangga "pegawai biasa".
     */
    public static function untuk(User $user, ?string $unit): ?TanggaAlur
    {
        if (! $unit || ! $user->data_pegawai_id || AlurPegawai::milik($user->data_pegawai_id)
            || PengajuanWorkflow::submitterLevel($user) === PengajuanWorkflow::LEVEL_GM
            || ! ($unitId = array_search($unit, self::namaUnit(), true))) {
            return null;
        }
        $sasaran = PengajuanWorkflow::memimpin($user->data_pegawai_id, $unit) ? TanggaAlur::SASARAN_KOORDINATOR : TanggaAlur::SASARAN_STAF;

        return TanggaAlur::where('unit_id', $unitId)->where('sasaran', $sasaran)->first();
    }

    /** Salinan tahap yang disimpan di pengajuan; nama tahap dibekukan saat diajukan. */
    public static function salinan(TanggaAlur $tangga, ?string $unitPengaju): array
    {
        return array_map(fn (array $t) => [
            'nama' => self::namaTahap($t, $unitPengaju),
            'semua' => (bool) ($t['semua'] ?? false),
            'penyetuju' => array_values($t['penyetuju'] ?? []),
        ], $tangga->daftarTahap());
    }

    /**
     * Rute tangga untuk pengajuan baru (format sama dengan PengajuanWorkflow::rute).
     *
     * @return list<array{level: string, label: string, approvers: Collection<int, User>, final: bool, khusus: bool, tangga: bool, semua: bool}>
     */
    public static function rute(TanggaAlur $tangga, User $user, ?string $unit): array
    {
        $tahap = $tangga->daftarTahap();

        return array_map(fn (array $t, int $i) => [
            'level' => PengajuanWorkflow::LEVEL_TANGGA,
            'label' => self::namaTahap($t, $unit),
            'approvers' => self::penyetuju($t, $unit, $user->data_pegawai_id),
            'final' => $i === count($tahap) - 1,
            'khusus' => false,
            'tangga' => true,
            'semua' => (bool) ($t['semua'] ?? false),
        ], $tahap, array_keys($tahap));
    }

    /**
     * Akun yang berhak menyetujui satu tahap: gabungan semua sumbernya, tanpa pengaju sendiri & Admin,
     * hanya yang punya hak menyetujui. Satu orang dengan beberapa akun dihitung sekali.
     *
     * @return Collection<int, User>
     */
    public static function penyetuju(array $tahap, ?string $unitPengaju, ?int $pegawaiId): Collection
    {
        $kunci = 'penyetuju|' . md5(json_encode([$tahap['penyetuju'] ?? [], $unitPengaju, $pegawaiId]));

        return self::$memo[$kunci] ??= collect($tahap['penyetuju'] ?? [])
            ->flatMap(fn (array $sumber) => self::dariSumber($sumber, $unitPengaju))
            ->filter(fn (User $u) => ! ($pegawaiId && $u->data_pegawai_id === $pegawaiId) && $u->adalahPenyetuju())
            ->unique(fn (User $u) => self::kunciOrang($u))
            ->values();
    }

    /** Nama tahap: nama yang diisi, atau gabungan label sumbernya (mis. "Koordinator SDM / Kepala SDM"). */
    public static function namaTahap(array $tahap, ?string $unitPengaju = null): string
    {
        $nama = trim((string) ($tahap['nama'] ?? ''));

        return $nama !== '' ? $nama
            : (collect($tahap['penyetuju'] ?? [])->map(fn ($s) => self::labelSumber($s, $unitPengaju))->implode(' / ') ?: 'Tahap tanpa penyetuju');
    }

    public static function labelSumber(array $sumber, ?string $unitPengaju = null): string
    {
        $unit = $sumber['unit'] ?? TanggaAlur::UNIT_PENGAJU;
        $semua = $unit === TanggaAlur::UNIT_SEMUA;
        $namaUnit = match (true) {
            $semua => 'semua bagian',
            $unit === TanggaAlur::UNIT_PENGAJU => $unitPengaju ?? 'bagian pengaju',
            default => self::namaUnit()[(int) $unit] ?? 'bagian terhapus',
        };

        return match ($sumber['tipe'] ?? null) {
            TanggaAlur::TIPE_KOORDINATOR => "Koordinator {$namaUnit}",
            TanggaAlur::TIPE_GM => $semua ? 'GM mana pun' : "GM bidang {$namaUnit}",
            TanggaAlur::TIPE_KEPALA_SDM => 'Kepala SDM',
            TanggaAlur::TIPE_JABATAN => (self::namaJabatan()[(int) ($sumber['jabatan_id'] ?? 0)] ?? 'Jabatan terhapus') . ($semua ? '' : " {$namaUnit}"),
            TanggaAlur::TIPE_AKUN => self::namaAkun((int) ($sumber['user_id'] ?? 0)),
            default => '-',
        };
    }

    /** Tahap tangga yang sedang berjalan pada pengajuan, atau null. */
    public static function tahapBerjalan(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): ?array
    {
        return $pengajuan->current_level === PengajuanWorkflow::LEVEL_TANGGA ? ($pengajuan->tangga[(int) $pengajuan->tahap_ke] ?? null) : null;
    }

    /**
     * Penyetuju tahap berjalan yang belum menyetujui ($kecuali dianggap sudah, mis. orang yang sedang menyetujui).
     *
     * @return Collection<int, User>
     */
    public static function penyetujuMenunggu(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan, ?User $kecuali = null): Collection
    {
        $tahap = self::tahapBerjalan($pengajuan);
        if (! $tahap || ! $pengajuan->isPending()) {
            return collect();
        }
        $sudah = self::sudahSetuju($pengajuan, (int) $pengajuan->tahap_ke);
        if ($kecuali) {
            $sudah->push(self::kunciOrang($kecuali));
        }

        return self::penyetuju($tahap, PengajuanWorkflow::unitPengajuan($pengajuan), $pengajuan->data_pegawai_id)
            ->reject(fn (User $u) => $sudah->contains(self::kunciOrang($u)))
            ->values();
    }

    /** $approver termasuk penyetuju tahap berjalan & belum menyetujuinya (Admin ditangani PengajuanWorkflow::canAct). */
    public static function bisaSetujui(User $approver, PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): bool
    {
        $orang = self::kunciOrang($approver);

        return self::penyetujuMenunggu($pengajuan)->contains(fn (User $u) => self::kunciOrang($u) === $orang);
    }

    /**
     * Kolom yang berubah saat $approver menyetujui tahap berjalan. Tahap "semua harus setuju" baru selesai
     * setelah penyetuju terakhir; Admin langsung menyelesaikan tahap. Tahap terakhir selesai = disetujui final.
     */
    public static function updateSetuju(User $approver, PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): array
    {
        $ke = (int) $pengajuan->tahap_ke;
        $semua = (bool) ($pengajuan->tangga[$ke]['semua'] ?? false);

        if ($semua && ! $approver->can(Izin::OVERRIDE) && self::penyetujuMenunggu($pengajuan, $approver)->isNotEmpty()) {
            return ['current_level' => PengajuanWorkflow::LEVEL_TANGGA];
        }
        if ($ke + 1 < count((array) $pengajuan->tangga)) {
            return ['current_level' => PengajuanWorkflow::LEVEL_TANGGA, 'tahap_ke' => $ke + 1];
        }

        return [
            'current_level' => null,
            'status' => $pengajuan::STATUS_APPROVED,
            'catatan_admin' => null,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ];
    }

    /** Catat persetujuan $approver pada tahap $ke (dipanggil PengajuanWorkflow::setujui sebelum kolom pengajuan berubah). */
    public static function catat(User $approver, PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan, int $ke): void
    {
        $pengajuan->persetujuanTahaps()->firstOrCreate(['tahap_ke' => $ke, 'user_id' => $approver->id]);
    }

    /** Admin mengembalikan pengajuan ke tahap $ke: persetujuan tahap itu & sesudahnya dihapus agar diulang. */
    public static function ulangDari(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan, int $ke): void
    {
        $pengajuan->persetujuanTahaps()->where('tahap_ke', '>=', $ke)->delete();
    }

    /** @return array<string, string> pilihan tahap untuk form Ubah Langsung (Admin): "tangga:indeks" => label */
    public static function pilihanAdmin(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): array
    {
        return collect((array) $pengajuan->tangga)
            ->mapWithKeys(fn (array $t, int $i) => [PengajuanWorkflow::LEVEL_TANGGA . ':' . $i => ($i + 1) . '. ' . ($t['nama'] ?? 'Tahap ' . ($i + 1))])
            ->all();
    }

    /**
     * Id pengajuan tangga yang boleh dilihat $viewer selain lingkup perannya: yang menunggu persetujuannya & yang pernah ia setujui.
     *
     * @return list<int>
     */
    public static function idTerlihat(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $model, User $viewer): array
    {
        return self::$memo['lihat|' . $model::class . '|' . $viewer->id] ??= $model::query()
            ->where('status', $model::STATUS_PENDING)->where('current_level', PengajuanWorkflow::LEVEL_TANGGA)->get()
            ->filter(fn ($p) => self::bisaSetujui($viewer, $p))
            ->pluck('id')
            ->merge(PersetujuanTahap::where('pengajuan_type', $model->getMorphClass())->whereIn('user_id', self::akunOrang($viewer))->pluck('pengajuan_id'))
            ->unique()->values()->all();
    }

    /**
     * Persetujuan yang tercatat, dikelompokkan per indeks tahap (urut waktu).
     *
     * @return Collection<int, Collection<int, PersetujuanTahap>>
     */
    public static function riwayat(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): Collection
    {
        return $pengajuan->persetujuanTahaps()->with('user:id,name')->orderBy('created_at')->get()->groupBy('tahap_ke');
    }

    /** Satu orang bisa punya beberapa akun (mis. akun GM & akun pegawai): disamakan lewat data pegawai. */
    public static function kunciOrang(User $user): string
    {
        return $user->data_pegawai_id ? 'p' . $user->data_pegawai_id : 'u' . $user->id;
    }

    /** @return list<int> semua akun milik orang yang sama dengan $user */
    private static function akunOrang(User $user): array
    {
        return $user->data_pegawai_id
            ? User::where('data_pegawai_id', $user->data_pegawai_id)->pluck('id')->push($user->id)->unique()->values()->all()
            : [$user->id];
    }

    /** @return Collection<int, string> orang (kunci) yang sudah menyetujui tahap $ke */
    private static function sudahSetuju(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan, int $ke): Collection
    {
        return $pengajuan->persetujuanTahaps()->where('tahap_ke', $ke)->with('user')->get()
            ->pluck('user')->filter()->map(fn (User $u) => self::kunciOrang($u))->unique()->values();
    }

    /** @return Collection<int, User> */
    private static function dariSumber(array $sumber, ?string $unitPengaju): Collection
    {
        $unit = self::namaUnitSumber($sumber, $unitPengaju);
        if ($unit === false) {
            return collect();
        }

        return match ($sumber['tipe'] ?? null) {
            TanggaAlur::TIPE_KOORDINATOR => $unit ? PengajuanWorkflow::kepalaUnit($unit) : collect(),
            TanggaAlur::TIPE_GM => self::gmBidang($unit),
            TanggaAlur::TIPE_KEPALA_SDM => PengajuanWorkflow::kepalaSdmTersedia(null),
            TanggaAlur::TIPE_JABATAN => self::pemegangJabatan((int) ($sumber['jabatan_id'] ?? 0), $unit),
            TanggaAlur::TIPE_AKUN => User::whereKey((int) ($sumber['user_id'] ?? 0))->get(),
            default => collect(),
        };
    }

    /** Nama bagian sumber: bagian pengaju / bagian tertentu; null = semua bagian; false = bagiannya sudah dihapus. */
    private static function namaUnitSumber(array $sumber, ?string $unitPengaju): string|false|null
    {
        $unit = $sumber['unit'] ?? TanggaAlur::UNIT_PENGAJU;

        return match (true) {
            $unit === TanggaAlur::UNIT_SEMUA => null,
            $unit === TanggaAlur::UNIT_PENGAJU => $unitPengaju ?? false,
            default => self::namaUnit()[(int) $unit] ?? false,
        };
    }

    /** GM yang memegang bidang $unit; bila tidak ada, GM tanpa batas bidang. $unit null = semua GM. */
    private static function gmBidang(?string $unit): Collection
    {
        $semua = self::$memo['gm'] ??= User::where('role', User::ROLE_GM)->orderBy('name')->get();
        if ($unit === null) {
            return $semua;
        }
        $utama = User::gmUtamaUntuk($unit);

        return $utama->isNotEmpty() ? $utama : $semua->filter(fn (User $gm) => empty($gm->divisi))->values();
    }

    /** Akun pegawai yang sedang memegang jabatan $jabatanId di $unit (null = di bagian mana pun), termasuk lewat rangkap & jabatan tambahan. */
    private static function pemegangJabatan(int $jabatanId, ?string $unit): Collection
    {
        $nama = self::namaJabatan()[$jabatanId] ?? null;
        if (! $nama) {
            return collect();
        }
        $pegawai = Penugasan::aktif()->where('jabatan', $nama)->when($unit, fn ($q) => $q->where('unit_kerja', $unit))->pluck('data_pegawai_id')
            ->merge(Kepegawaian::where('status_rekrutmen', 'Aktif')
                ->where(fn ($q) => $q->where('jabatan', $nama)->orWhere('jabatan_tambahan', $nama))
                ->when($unit, fn ($q) => $q->where('unit_kerja', $unit))
                ->pluck('data_pegawai_id'))
            ->unique()->values();

        return $pegawai->isEmpty() ? collect()
            : User::whereIn('data_pegawai_id', $pegawai)->whereIn('role', User::INTERNAL_ROLES)->orderBy('name')->get();
    }

    private static function namaAkun(int $id): string
    {
        return self::$memo['akun|' . $id] ??= User::whereKey($id)->value('name') ?? 'Akun terhapus';
    }

    /** @return array<int, string> id => nama bagian */
    private static function namaUnit(): array
    {
        return self::$memo['unit'] ??= Unit::pluck('nama', 'id')->all();
    }

    /** @return array<int, string> id => nama jabatan */
    private static function namaJabatan(): array
    {
        return self::$memo['jabatan'] ??= Jabatan::pluck('nama', 'id')->all();
    }
}
