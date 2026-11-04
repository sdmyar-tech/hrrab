<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permission (hak akses fitur). Daftarnya berasal dari App\Support\Izin::KATALOG dan diisi RbacSeeder.
 * Dipegang lewat role (role_permission) atau dicentang langsung per akun (user_permission).
 */
class Permission extends Model
{
    protected $fillable = ['kode', 'nama', 'grup', 'keterangan', 'urutan'];

    /** @var array<int, list<string>>|null user_id => kode permission tambahan (satu query per proses) */
    private static ?array $tambahanMemo = null;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permission');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_permission')->withPivot(['keterangan', 'diberikan_oleh'])->withTimestamps();
    }

    /**
     * Permission tambahan (centang per akun) milik $userId. Seluruh tabel dimuat sekali karena isinya sedikit
     * (pengecualian per akun) sementara hasAbility dipanggil untuk banyak akun per request.
     *
     * @return list<string>
     */
    public static function tambahanUntuk(int $userId): array
    {
        if (self::$tambahanMemo === null) {
            self::$tambahanMemo = Schema::hasTable('user_permission')
                ? DB::table('user_permission')->join('permissions', 'permissions.id', '=', 'user_permission.permission_id')
                    ->get(['user_permission.user_id', 'permissions.kode'])
                    ->groupBy('user_id')->map(fn ($r) => $r->pluck('kode')->all())->all()
                : [];
        }

        return self::$tambahanMemo[$userId] ?? [];
    }

    /** Lupakan memo setelah centang per akun berubah. */
    public static function lupakan(): void
    {
        self::$tambahanMemo = null;
    }

    /** @return array<string, int> kode => id */
    public static function idPerKode(): array
    {
        return self::pluck('id', 'kode')->all();
    }
}
