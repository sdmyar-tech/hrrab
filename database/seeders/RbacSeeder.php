<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\Izin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Isi katalog permission dan role sistem. Aman dijalankan berulang (idempotent):
 * - permission baru di Izin::KATALOG ditambahkan, nama/keterangan diperbarui;
 * - role sistem yang belum punya baris dibuat dengan permission bawaannya (Izin::BAWAAN_ROLE);
 *   role yang sudah ada TIDAK diubah centangnya (pengaturan Admin dipertahankan);
 * - Admin selalu memegang semua permission (dikunci).
 *
 * Dipanggil oleh migrasi RBAC dan DatabaseSeeder. Saat menambah permission baru, buat migrasi yang memanggil seeder ini.
 */
class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $this->isiKatalog();
        $this->isiRole();
    }

    /** Tabel permissions mengikuti Izin::KATALOG (tambah/perbarui; yang sudah dihapus dari katalog ikut dihapus). */
    public function isiKatalog(): void
    {
        $urutan = 0;
        foreach (Izin::KATALOG as $grup => [, $daftar]) {
            foreach ($daftar as $kode => [$nama, $keterangan]) {
                Permission::updateOrCreate(['kode' => $kode], ['nama' => $nama, 'grup' => $grup, 'keterangan' => $keterangan, 'urutan' => ++$urutan]);
            }
        }
        DB::table('permissions')->whereNotIn('kode', Izin::semua())->delete();
    }

    /** Baris role sistem yang belum ada + Admin memegang semua permission. */
    public function isiRole(): void
    {
        $id = Permission::idPerKode();

        foreach (User::INTERNAL_ROLES as $kode) {
            $role = Role::where('kode', $kode)->first();
            if (! $role) {
                $role = Role::create(['kode' => $kode, 'nama' => User::LABEL_ROLE[$kode], 'jenis_dasar' => $kode, 'bawaan' => true]);
                $role->permissions()->sync(array_values(array_intersect_key($id, array_flip(Izin::bawaanRole($kode)))));
            }
        }

        // Admin: semua permission, termasuk yang baru ditambahkan.
        Role::where('kode', Izin::ROLE_PENUH)->first()?->permissions()->syncWithoutDetaching(array_values($id));

        Role::lupakanCache();
    }
}
