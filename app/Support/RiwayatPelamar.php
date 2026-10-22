<?php

namespace App\Support;

use App\Models\DataPelamar;
use App\Models\RiwayatPendidikanPelamar;
use Illuminate\Validation\Rule;

/** Validasi & penyimpanan daftar berulang di data pelamar: riwayat pendidikan, riwayat pekerjaan, sosial media. */
class RiwayatPelamar
{
    public static function rules(): array
    {
        $tahun = ['nullable', 'integer', 'min:1950', 'max:' . (now()->year + 10)];

        return [
            'riwayat_pendidikan' => ['nullable', 'array', 'max:15'],
            'riwayat_pendidikan.*.jenjang' => ['required', Rule::in(RiwayatPendidikanPelamar::JENJANG)],
            'riwayat_pendidikan.*.nama_institusi' => ['required', 'string', 'max:150'],
            'riwayat_pendidikan.*.jurusan' => ['nullable', 'string', 'max:150'],
            'riwayat_pendidikan.*.tahun_masuk' => $tahun,
            'riwayat_pendidikan.*.tahun_lulus' => [...$tahun, 'gte:riwayat_pendidikan.*.tahun_masuk'],
            'riwayat_pendidikan.*.nilai' => ['nullable', 'string', 'max:20'],

            'riwayat_pekerjaan' => ['nullable', 'array', 'max:20'],
            'riwayat_pekerjaan.*.nama_perusahaan' => ['required', 'string', 'max:150'],
            'riwayat_pekerjaan.*.posisi' => ['required', 'string', 'max:150'],
            'riwayat_pekerjaan.*.tanggal_mulai' => ['required', 'date'],
            'riwayat_pekerjaan.*.tanggal_selesai' => ['nullable', 'date', 'after_or_equal:riwayat_pekerjaan.*.tanggal_mulai'],
            'riwayat_pekerjaan.*.deskripsi' => ['nullable', 'string', 'max:1000'],

            'sosial_media' => ['nullable', 'array', 'max:10'],
            'sosial_media.*.platform' => ['required', 'string', 'max:50'],
            'sosial_media.*.url' => ['required', 'url:http,https', 'max:255'],
        ];
    }

    public static function messages(): array
    {
        return [
            'riwayat_pendidikan.*.jenjang.required' => 'Jenjang pendidikan wajib dipilih.',
            'riwayat_pendidikan.*.nama_institusi.required' => 'Nama sekolah/kampus wajib diisi.',
            'riwayat_pendidikan.*.tahun_lulus.gte' => 'Tahun lulus tidak boleh sebelum tahun masuk.',
            'sosial_media.*.platform.required' => 'Nama platform wajib diisi.',
            'sosial_media.*.url.required' => 'Link sosial media wajib diisi.',
            'sosial_media.*.url.url' => 'Link sosial media harus diawali http:// atau https://.',
        ];
    }

    public const FIELDS = ['riwayat_pendidikan', 'riwayat_pekerjaan', 'sosial_media'];

    public static function simpan(DataPelamar $pelamar, array $validated, bool $timpa = true): void
    {
        $sosmed = collect($validated['sosial_media'] ?? [])
            ->map(fn ($s) => ['platform' => trim($s['platform']), 'url' => trim($s['url'])])
            ->unique('url')->values()->all();

        if ($timpa || empty($pelamar->sosial_media)) {
            $pelamar->update(['sosial_media' => $sosmed ?: null]);
        }

        foreach (['riwayatPendidikan' => 'riwayat_pendidikan', 'riwayatPekerjaan' => 'riwayat_pekerjaan'] as $relasi => $field) {
            if (! $timpa && $pelamar->{$relasi}()->exists()) {
                continue;
            }

            $pelamar->{$relasi}()->delete();
            foreach ($validated[$field] ?? [] as $baris) {
                $pelamar->{$relasi}()->create($baris);
            }
        }
    }
}
