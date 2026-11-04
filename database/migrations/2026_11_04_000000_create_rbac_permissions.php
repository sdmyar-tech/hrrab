<?php

use App\Models\Penugasan;
use App\Models\User;
use App\Support\Izin;
use Database\Seeders\RbacSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perombakan RBAC: hak akses fitur menjadi permission eksplisit (tercentang di database).
 * - permissions + role_permission (centang per role) + user_permission (centang tambahan per akun).
 * - Hak lama dipindahkan TANPA dicabut: roles.hak_akses (JSON), Hak Akses Jabatan (jabatan_unit.hak_akses),
 *   dan akses bawaan staf bagian SDM (dulu di-hardcode) menjadi centang per akun.
 * - Kolom JSON lama dihapus; fitur Hak Akses Jabatan dihapus (jabatan kini hanya untuk alur persetujuan).
 */
return new class extends Migration
{
    /** Hak bawaan role sebelum perombakan (salinan User::ABILITIES lama) — untuk menghitung hak role yang dulu di-hardcode. */
    private const HAK_LAMA_BAWAAN = [
        'pembina' => ['lihat-dashboard', 'lihat-tabel-karyawan', 'lihat-laporan'],
        'gm' => ['lihat-dashboard', 'lihat-tabel-karyawan', 'lihat-laporan', 'approve', 'kelola-pengumuman', 'kirim-notifikasi'],
        'kepala_bagian' => ['lihat-dashboard', 'lihat-tabel-karyawan', 'lihat-laporan', 'approve', 'mengajukan', 'kelola-pengumuman', 'kirim-notifikasi', 'lihat-absensi'],
        'kepala_sdm' => ['lihat-dashboard', 'lihat-tabel-karyawan', 'lihat-laporan', 'approve', 'mengajukan', 'kelola-penugasan', 'kelola-pengumuman', 'kirim-notifikasi', 'kelola-pegawai', 'kelola-absensi', 'lihat-absensi'],
        'staff' => ['lihat-dashboard', 'mengajukan'],
        'admin' => ['lihat-dashboard', 'lihat-tabel-karyawan', 'lihat-laporan', 'kelola-sistem', 'kelola-penugasan', 'kelola-pengumuman', 'kirim-notifikasi', 'kelola-pegawai', 'kelola-absensi', 'lihat-absensi'],
    ];

    /** Unit yang dulu memberi staf akses kelola pegawai & absensi secara otomatis (User::hasAbility lama). */
    private const UNIT_SDM = 'SDM';

    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 60)->unique();
            $table->string('nama', 120);
            $table->string('grup', 40);
            $table->string('keterangan', 255)->nullable();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('role_permission', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('user_permission', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->string('keterangan', 255)->nullable();
            $table->foreignId('diberikan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'permission_id']);
        });

        // Katalog permission harus ada sebelum data lama dipindahkan.
        $seeder = new RbacSeeder;
        $seeder->isiKatalog();
        $id = DB::table('permissions')->pluck('id', 'kode')->all();

        DB::transaction(function () use ($id) {
            $this->pindahkanRole($id);
            $this->pindahkanHakJabatan($id);
            $this->pindahkanStafSdm($id);
        });

        Schema::table('roles', fn (Blueprint $t) => $t->dropColumn('hak_akses'));
        if (Schema::hasColumn('jabatan_unit', 'hak_akses')) {
            Schema::table('jabatan_unit', fn (Blueprint $t) => $t->dropColumn('hak_akses'));
        }
        cache()->forget('master.role');
        cache()->forget('master.hak-akses-jabatan');

        // Role sistem yang belum punya baris (mis. instalasi yang tabel roles-nya kosong) + Admin semua permission.
        $seeder->isiRole();
    }

    public function down(): void
    {
        Schema::table('roles', fn (Blueprint $t) => $t->json('hak_akses')->nullable()->after('jenis_dasar'));
        Schema::table('jabatan_unit', fn (Blueprint $t) => $t->json('hak_akses')->nullable()->after('unit_id'));

        // Kembalikan hak role ke format lama: hak lama yang seluruh permission penggantinya dipegang role.
        $kodePerRole = DB::table('role_permission')->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->get(['role_permission.role_id', 'permissions.kode'])->groupBy('role_id')->map(fn ($r) => $r->pluck('kode')->all());
        foreach (DB::table('roles')->get(['id']) as $r) {
            $milik = $kodePerRole[$r->id] ?? [];
            $lama = collect(Izin::PETA_LAMA)->filter(fn ($baru) => $baru && ! array_diff($baru, $milik))->keys()->push('lihat-dashboard')->unique()->values();
            DB::table('roles')->where('id', $r->id)->update(['hak_akses' => $lama->toJson()]);
        }

        Schema::dropIfExists('user_permission');
        Schema::dropIfExists('role_permission');
        Schema::dropIfExists('permissions');
        cache()->forget('master.role');
    }

    /** roles.hak_akses (JSON) → role_permission, ditambah hak yang dulu diberikan lewat pengecekan jenis dasar di kode. */
    private function pindahkanRole(array $id): void
    {
        foreach (DB::table('roles')->get() as $r) {
            $dasar = $r->jenis_dasar;
            $hak = Izin::dariHakLama((array) json_decode((string) $r->hak_akses, true));
            // Hak yang dulu diberikan lewat isKepalaSdm()/isGm()/... (bukan lewat JSON) tetap berlaku untuk jenis dasar yang sama.
            $dariKode = array_diff(Izin::bawaanRole($dasar), Izin::dariHakLama(self::HAK_LAMA_BAWAAN[$dasar] ?? []));
            $semua = $dasar === Izin::ROLE_PENUH ? Izin::semua() : array_unique([...$hak, ...$dariKode]);

            DB::table('role_permission')->insertOrIgnore(array_map(fn ($k) => ['role_id' => $r->id, 'permission_id' => $id[$k]], array_values(array_intersect($semua, array_keys($id)))));
        }
    }

    /** Hak Akses Jabatan → centang per akun pemegang jabatan itu (fitur dihapus; hak tidak dicabut). */
    private function pindahkanHakJabatan(array $id): void
    {
        if (! Schema::hasColumn('jabatan_unit', 'hak_akses')) {
            return;
        }
        $baris = DB::table('jabatan_unit')->join('jabatans', 'jabatans.id', '=', 'jabatan_unit.jabatan_id')->join('units', 'units.id', '=', 'jabatan_unit.unit_id')
            ->whereNotNull('jabatan_unit.hak_akses')->get(['units.nama as unit', 'jabatans.nama as jabatan', 'jabatan_unit.hak_akses']);

        foreach ($baris as $b) {
            $hak = array_diff(Izin::dariHakLama((array) json_decode((string) $b->hak_akses, true)), [Izin::AKUN_MANAGE, Izin::ROLE_MANAGE, Izin::MASTER_MANAGE, Izin::LOG_VIEW]);
            $pegawai = Penugasan::aktif()->where('unit_kerja', $b->unit)->where('jabatan', $b->jabatan)->pluck('data_pegawai_id')
                ->merge(DB::table('kepegawaians')->where('unit_kerja', $b->unit)
                    ->where(fn ($q) => $q->where('jabatan', $b->jabatan)->orWhere('jabatan_tambahan', $b->jabatan))->pluck('data_pegawai_id'))
                ->unique();
            $akun = DB::table('users')->whereIn('data_pegawai_id', $pegawai)->whereIn('role', User::INTERNAL_ROLES)->pluck('id');

            $this->beri($akun, $hak, $id, "Dipindahkan dari Hak Akses Jabatan {$b->jabatan} · {$b->unit}");
        }
    }

    /** Akses bawaan staf bagian SDM (hardcode lama: kelola pegawai & absensi) → centang per akun. */
    private function pindahkanStafSdm(array $id): void
    {
        $akun = User::where('role', User::ROLE_STAFF)->whereNotNull('data_pegawai_id')->get()
            ->filter(fn (User $u) => in_array(self::UNIT_SDM, $u->unitsAktif(), true))->pluck('id');

        $this->beri($akun, [Izin::PEGAWAI_VIEW, Izin::PEGAWAI_CREATE, Izin::PEGAWAI_UPDATE, Izin::PEGAWAI_DELETE, Izin::ABSENSI_VIEW, Izin::ABSENSI_MANAGE],
            $id, 'Dipindahkan dari akses bawaan staf bagian SDM');
    }

    private function beri(iterable $akun, array $hak, array $id, string $keterangan): void
    {
        $sekarang = now();
        foreach ($akun as $userId) {
            DB::table('user_permission')->insertOrIgnore(array_map(fn ($k) => [
                'user_id' => $userId, 'permission_id' => $id[$k], 'keterangan' => $keterangan, 'created_at' => $sekarang, 'updated_at' => $sekarang,
            ], array_values(array_intersect($hak, array_keys($id)))));
        }
    }
};
