<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlurPegawai;
use App\Models\DataPegawai;
use App\Models\User;
use App\Support\PengajuanWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Kepala SDM & Admin: ubah alur persetujuan cuti/lembur dan atasan seorang pegawai. */
class AlurPegawaiController extends Controller
{
    /**
     * Akun yang boleh ditunjuk sebagai atasan / GM (punya hak menyetujui), selain pegawai itu sendiri.
     *
     * @return array{atasan: Collection<int, User>, gm: Collection<int, User>}
     */
    public static function pilihan(DataPegawai $pegawai): array
    {
        $calon = User::with('dataPegawai.penugasanAktif')
            ->whereIn('role', [User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM, User::ROLE_GM])
            ->where(fn ($q) => $q->whereNull('data_pegawai_id')->orWhere('data_pegawai_id', '!=', $pegawai->id))
            ->orderBy('name')
            ->get()
            ->filter(fn (User $u) => $u->hasAbility(User::ABILITY_APPROVE))
            ->values();

        return ['atasan' => $calon, 'gm' => $calon->filter->isGm()->values()];
    }

    public function update(Request $request, DataPegawai $pegawai): RedirectResponse
    {
        $pilihan = self::pilihan($pegawai);

        $v = $request->validate([
            'tahap' => ['required', 'array', 'min:1'],
            'tahap.*' => [Rule::in(AlurPegawai::URUTAN)],
            'atasan_user_id' => ['nullable', 'integer', Rule::in($pilihan['atasan']->pluck('id')->all())],
            'gm_user_id' => ['nullable', 'integer', Rule::in($pilihan['gm']->pluck('id')->all())],
            'catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'tahap.required' => 'Pilih minimal satu tahap persetujuan.',
            'tahap.min' => 'Pilih minimal satu tahap persetujuan.',
            'atasan_user_id.in' => 'Atasan yang dipilih tidak bisa menyetujui pengajuan.',
            'gm_user_id.in' => 'GM yang dipilih tidak valid.',
        ]);

        $tahap = array_values(array_intersect(AlurPegawai::URUTAN, $v['tahap']));
        $pakaiAtasan = in_array(PengajuanWorkflow::LEVEL_KEPALA_DIVISI, $tahap, true);
        $pakaiGm = in_array(PengajuanWorkflow::LEVEL_GM, $tahap, true);

        AlurPegawai::updateOrCreate(['data_pegawai_id' => $pegawai->id], [
            'tahap' => $tahap,
            // Pilihan orang hanya disimpan bila tahapnya dipakai.
            'atasan_user_id' => $pakaiAtasan ? ($v['atasan_user_id'] ?? null) : null,
            'gm_user_id' => $pakaiGm ? ($v['gm_user_id'] ?? null) : null,
            'catatan' => $v['catatan'] ?? null,
            'diubah_oleh' => Auth::id(),
        ]);

        return redirect()->to(route('admin.pegawai.show', [$pegawai, 'tab' => 'penugasan']) . '#alur-approval')
            ->with('success', "Alur persetujuan {$pegawai->nama_lengkap} diperbarui. Berlaku untuk pengajuan berikutnya dan tahap yang belum diproses.");
    }

    /** Kembali ke alur otomatis (berdasarkan unit & jabatan). */
    public function destroy(DataPegawai $pegawai): RedirectResponse
    {
        AlurPegawai::where('data_pegawai_id', $pegawai->id)->delete();

        return redirect()->to(route('admin.pegawai.show', [$pegawai, 'tab' => 'penugasan']) . '#alur-approval')
            ->with('success', "Alur persetujuan {$pegawai->nama_lengkap} kembali otomatis.");
    }
}
