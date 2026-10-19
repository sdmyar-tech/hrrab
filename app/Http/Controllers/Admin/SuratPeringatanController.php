<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataPegawai;
use App\Models\SuratPeringatan;
use App\Models\User;
use App\Support\Notifikasi;
use App\Support\SuratPeringatanAkses;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Surat peringatan bulanan: SP1 → SP2 → SP3 bertahap dalam satu bulan; bulan berikutnya mulai lagi dari SP1. */
class SuratPeringatanController extends Controller
{
    public function index(Request $request): View
    {
        $user = $this->pengguna();
        abort_unless(SuratPeringatanAkses::bolehMembuka($user), 403, 'Anda tidak berwenang membuka surat peringatan.');

        $bulan = $this->bulan($request->query('bulan'));
        $bolehDiberi = SuratPeringatanAkses::pegawaiBoleh($user);
        $lingkup = SuratPeringatanAkses::lihatSemua($user) ? null : $bolehDiberi->pluck('id');
        $kata = trim((string) $request->query('q'));

        $daftar = SuratPeringatan::with('dataPegawai.kepegawaian', 'pemberi')
            ->whereBetween('tanggal', [$bulan->copy()->startOfMonth()->toDateString(), $bulan->copy()->endOfMonth()->toDateString()])
            ->when($lingkup, fn ($q) => $q->whereIn('data_pegawai_id', $lingkup))
            ->when($kata !== '', fn ($q) => $q->whereHas('dataPegawai', fn ($p) => $p->where('nama_lengkap', 'like', "%{$kata}%")))
            ->orderByDesc('tanggal')->orderByDesc('id')->get();

        // Tingkat SP tertinggi bulan ini per pegawai → pratinjau "akan menjadi SP berapa" di form.
        $tingkatBulanIni = SuratPeringatan::whereBetween('tanggal', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->whereIn('data_pegawai_id', $bolehDiberi->pluck('id'))
            ->selectRaw('data_pegawai_id, max(tingkat) t')->groupBy('data_pegawai_id')->pluck('t', 'data_pegawai_id');

        return view('admin.surat-peringatan.index', [
            'bulan' => $bulan,
            'daftar' => $daftar,
            'pegawai' => $bolehDiberi,
            'tingkatBulanIni' => $tingkatBulanIni,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->pengguna();
        $data = $request->validate([
            'data_pegawai_id' => ['required', 'integer', 'exists:data_pegawais,id'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'alasan' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:2000'],
        ], [
            'data_pegawai_id.required' => 'Pilih pegawai.',
            'tanggal.before_or_equal' => 'Tanggal SP tidak boleh di masa depan.',
            'alasan.required' => 'Tulis alasan / pelanggaran.',
        ]);
        $pegawai = DataPegawai::with('kepegawaian')->findOrFail($data['data_pegawai_id']);
        abort_unless(SuratPeringatanAkses::bisaBeri($user, $pegawai), 403, 'Anda tidak berwenang memberi SP untuk pegawai ini.');

        $tanggal = Carbon::parse($data['tanggal']);
        $tingkat = SuratPeringatan::tingkatBerikut($pegawai->id, $tanggal);
        if (! $tingkat) {
            throw ValidationException::withMessages(['data_pegawai_id' => "{$pegawai->nama_lengkap} sudah menerima SP3 pada " . $tanggal->translatedFormat('F Y') . ' (maksimal 3 SP per bulan).']);
        }

        $sp = SuratPeringatan::create([
            ...$data,
            'unit_kerja' => $pegawai->kepegawaian?->unit_kerja,
            'tingkat' => $tingkat,
            'diberikan_oleh' => $user->id,
        ]);
        Notifikasi::spDiberikan($sp);

        return redirect()->route('admin.surat-peringatan.index', ['bulan' => $tanggal->format('Y-m')])
            ->with('success', "SP{$tingkat} untuk {$pegawai->nama_lengkap} tersimpan (" . $tanggal->translatedFormat('F Y') . ').');
    }

    public function destroy(SuratPeringatan $suratPeringatan): RedirectResponse
    {
        abort_unless(SuratPeringatanAkses::bisaHapus($this->pengguna(), $suratPeringatan), 403, 'Hanya SP terakhir di bulannya yang bisa dihapus (oleh pemberi, Kepala SDM, atau Admin).');
        $bulan = $suratPeringatan->tanggal->format('Y-m');
        $suratPeringatan->delete();

        return redirect()->route('admin.surat-peringatan.index', ['bulan' => $bulan])->with('success', 'Surat peringatan dihapus.');
    }

    /** Bulan dari "YYYY-MM" (bawaan bulan ini). */
    private function bulan(?string $nilai): Carbon
    {
        return $nilai && preg_match('/^\d{4}-\d{2}$/', $nilai) ? Carbon::createFromFormat('Y-m-d', $nilai . '-01')->startOfDay() : now()->startOfMonth();
    }

    private function pengguna(): User
    {
        /** @var User */
        return Auth::user();
    }
}
