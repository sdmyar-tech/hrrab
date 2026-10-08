<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pengumuman extends Model
{
    use \App\Models\Concerns\DicatatLog;

    protected $table = 'pengumumans';

    public const KATEGORI = [
        'kegiatan' => ['label' => 'Kegiatan', 'ikon' => 'bi-people', 'badge' => 'text-bg-primary'],
        'kajian' => ['label' => 'Kajian / Ibadah', 'ikon' => 'bi-book', 'badge' => 'text-bg-success'],
        'acara' => ['label' => 'Acara Besar', 'ikon' => 'bi-stars', 'badge' => 'text-bg-warning'],
        'duka' => ['label' => 'Kabar Duka', 'ikon' => 'bi-flower1', 'badge' => 'text-bg-secondary'],
        'info' => ['label' => 'Info', 'ikon' => 'bi-megaphone', 'badge' => 'text-bg-info'],
    ];

    public const JADWAL_SEKALI = 'sekali';
    public const JADWAL_HARIAN = 'harian';
    public const JADWAL_MINGGUAN = 'mingguan';

    public const HARI = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

    protected $fillable = [
        'judul', 'isi', 'kategori', 'jadwal', 'tanggal', 'hari', 'jam_mulai', 'jam_selesai', 'lokasi',
        'sasaran_unit', 'penting', 'tampil_sampai', 'dibuat_oleh',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'tampil_sampai' => 'date',
        'sasaran_unit' => 'array',
        'penting' => 'boolean',
        'hari' => 'integer',
    ];

    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    public function pembaca(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pengumuman_dibacas')->withPivot('dibaca_pada');
    }

    public function scopeBerlaku(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('tampil_sampai')->orWhereDate('tampil_sampai', '>=', now()));
    }

    public function scopeUntuk(Builder $query, User $user): Builder
    {
        if ($user->isAdmin() || $user->isPembina() || $user->isKepalaSdm() || $user->isGm()) {
            return $query;
        }

        $units = array_values(array_unique([...$user->unitsAktif(), ...$user->unitsDipimpin()]));

        return $query->where(function ($q) use ($user, $units) {
            $q->whereNull('sasaran_unit')->orWhere('dibuat_oleh', $user->id);
            foreach ($units as $unit) {
                $q->orWhereJsonContains('sasaran_unit', $unit);
            }
        });
    }

    public function scopeBelumDibaca(Builder $query, User $user): Builder
    {
        return $query->whereDoesntHave('pembaca', fn ($q) => $q->where('users.id', $user->id));
    }

    public function kategoriInfo(): array
    {
        return self::KATEGORI[$this->kategori] ?? self::KATEGORI['info'];
    }

    public function isRutin(): bool
    {
        return $this->jadwal !== self::JADWAL_SEKALI;
    }

    public function jadwalLabel(): ?string
    {
        $jam = $this->jam_mulai ? substr($this->jam_mulai, 0, 5) . ($this->jam_selesai ? '–' . substr($this->jam_selesai, 0, 5) : '') : null;

        $hari = match ($this->jadwal) {
            self::JADWAL_HARIAN => 'Setiap hari',
            self::JADWAL_MINGGUAN => 'Setiap ' . (self::HARI[$this->hari ?? 0] ?? '-'),
            default => $this->tanggal?->translatedFormat('l, d F Y'),
        };

        return collect([$hari, $jam])->filter()->implode(', ') ?: null;
    }

    public function berikutnya(): ?Carbon
    {
        $jam = $this->jam_mulai ? substr($this->jam_mulai, 0, 5) : '00:00';

        $kandidat = match ($this->jadwal) {
            self::JADWAL_HARIAN => now()->setTimeFromTimeString($jam),
            self::JADWAL_MINGGUAN => now()->setTimeFromTimeString($jam)->addDays(((int) $this->hari - now()->dayOfWeek + 7) % 7),
            default => $this->tanggal?->copy()->setTimeFromTimeString($jam),
        };

        if ($kandidat && $this->isRutin() && $kandidat->isPast() && ! $kandidat->isToday()) {
            $kandidat->addDays($this->jadwal === self::JADWAL_HARIAN ? 1 : 7);
        }

        return $kandidat && ($kandidat->isToday() || $kandidat->isFuture()) ? $kandidat : null;
    }

    public function sasaranLabel(): string
    {
        return $this->sasaran_unit ? implode(', ', $this->sasaran_unit) : 'Semua pegawai';
    }
}
