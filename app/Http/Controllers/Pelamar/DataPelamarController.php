<?php

namespace App\Http\Controllers\Pelamar;

use App\Http\Controllers\Controller;
use App\Models\DataPelamar;
use App\Support\RiwayatPelamar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DataPelamarController extends Controller
{
    public function edit(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        return view('pelamar.data-pelamar', [
            'pelamar' => $user->dataPelamar?->load('riwayatPekerjaan', 'riwayatPendidikan'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nik' => ['required', 'string', 'max:20', 'unique:data_pelamars,nik,' . ($user->data_pelamar_id ?? 'NULL') . ',id'],
            'npwp' => ['nullable', 'string', 'max:25'],
            'no_kk' => ['required', 'string', 'max:20'],
            'no_hp' => ['required', 'string', 'max:20'],
            'tempat_lahir' => ['required', 'string', 'max:150'],
            'tanggal_lahir' => ['required', 'date'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'agama' => ['required', 'string', 'max:50'],
            'golongan_darah' => ['nullable', 'in:O+,A+,B+,AB+,O-,A-,B-,AB-,Rh-Null,Belum Diketahui'],
            'status_pernikahan' => ['required', 'in:Belum Menikah,Menikah,Cerai Hidup,Cerai Mati'],
            'jumlah_tanggungan' => ['nullable', 'integer', 'min:0', 'max:20'],
            'nama_ibu_kandung' => ['required', 'string', 'max:150'],
            'pendidikan_terakhir' => ['required', 'string', 'max:100'],
            'alamat_ktp' => ['required', 'string', 'max:1000'],
            'alamat_domisili' => ['nullable', 'string', 'max:1000'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'cv' => ['nullable', 'mimes:pdf,doc,docx', 'max:4096'],
            'ijazah' => ['nullable', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'sertifikat' => ['nullable', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            ...RiwayatPelamar::rules(),
        ], RiwayatPelamar::messages());

        $data = collect($validated)->except(['foto', 'cv', 'ijazah', 'sertifikat', ...RiwayatPelamar::FIELDS])->toArray();
        $data['email'] = $user->email;

        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('dokumen-pelamar/foto', 'public');
        }

        if ($request->hasFile('cv')) {
            $data['cv_path'] = $request->file('cv')->store('dokumen-pelamar/cv', 'public');
        }

        if ($request->hasFile('ijazah')) {
            $data['ijazah_path'] = $request->file('ijazah')->store('dokumen-pelamar/ijazah', 'public');
        }

        if ($request->hasFile('sertifikat')) {
            $data['sertifikat_path'] = $request->file('sertifikat')->store('dokumen-pelamar/sertifikat', 'public');
        }

        if ($user->dataPelamar) {
            $pelamar = tap($user->dataPelamar)->update($data);
        } else {
            $pelamar = DataPelamar::create($data);
            $user->update(['data_pelamar_id' => $pelamar->id]);
        }

        RiwayatPelamar::simpan($pelamar, $validated);

        if ($pelamar->isLengkap() && $request->session()->has('lowongan_tujuan')) {
            return redirect($request->session()->pull('lowongan_tujuan'))
                ->with('success', 'Data pelamar berhasil disimpan. Silakan lanjutkan lamaran Anda.');
        }

        return redirect()
            ->route('pelamar.data-pelamar.edit')
            ->with('success', 'Data pelamar berhasil disimpan.');
    }
}
