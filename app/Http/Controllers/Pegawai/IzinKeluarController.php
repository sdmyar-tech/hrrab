<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\PengajuanIzinKeluar;
use App\Models\User;
use App\Support\Notifikasi;
use App\Support\PengajuanWorkflow;
use App\Support\RingkasanPengajuan;
use App\Support\Urutan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Izin keluar saat jam kerja: pengajuan pegawai & tiket yang ditunjukkan ke security gerbang. */
class IzinKeluarController extends Controller
{
    /** Izin keluar bisa diajukan paling jauh sekian hari ke depan. */
    private const MAKS_HARI_KE_DEPAN = 14;

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $pegawai = $user->dataPegawai;

        $urut = ['urutOpsi' => [], 'urutAktif' => null];
        $riwayat = collect();
        if ($pegawai) {
            $query = $pegawai->pengajuanIzinKeluars();
            $urut = Urutan::terapkan($query, [
                ...Urutan::tanggal('tgl', 'Tanggal izin', 'pengajuan_izin_keluars.tanggal'),
                ...Urutan::tanggal('diajukan', 'Diajukan', 'pengajuan_izin_keluars.created_at'),
            ]);
            $riwayat = $query->get();
        }

        return view('pegawai.izin-keluar', [
            ...$urut,
            'riwayat' => $riwayat,
            'keperluanOptions' => PengajuanIzinKeluar::KEPERLUAN,
            'maksHari' => self::MAKS_HARI_KE_DEPAN,
            'rute' => PengajuanWorkflow::rute($user, $user->unitKerja()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $pegawai = $user->dataPegawai;

        if (! $pegawai) {
            return redirect()->route('pegawai.izin-keluar')->with('error', 'Data pegawai Anda belum tersedia. Hubungi bagian SDM.');
        }

        $tidakKembali = $request->boolean('tidak_kembali');
        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(self::MAKS_HARI_KE_DEPAN)->toDateString()],
            'jam_keluar' => ['required', 'date_format:H:i'],
            'jam_kembali' => [$tidakKembali ? 'nullable' : 'required', 'date_format:H:i', 'after:jam_keluar'],
            'keperluan' => ['required', Rule::in(array_keys(PengajuanIzinKeluar::KEPERLUAN))],
            'alasan' => ['required', 'string', 'max:500'],
        ], [
            'tanggal.after_or_equal' => 'Tanggal izin tidak boleh sebelum hari ini.',
            'tanggal.before_or_equal' => 'Izin keluar paling jauh diajukan ' . self::MAKS_HARI_KE_DEPAN . ' hari ke depan.',
            'jam_keluar.required' => 'Jam keluar wajib diisi.',
            'jam_kembali.required' => 'Isi perkiraan jam kembali, atau centang "Tidak kembali lagi hari ini".',
            'jam_kembali.after' => 'Jam kembali harus setelah jam keluar.',
            'keperluan.required' => 'Pilih keperluan.',
            'alasan.required' => 'Alasan wajib diisi.',
        ]);
        $jamKembali = $tidakKembali ? null : $validated['jam_kembali'];

        // Hari ini: jam keluar tidak boleh sudah lewat (beri kelonggaran 15 menit).
        if (Carbon::parse($validated['tanggal'])->isToday() && Carbon::parse($validated['jam_keluar'])->lt(now()->subMinutes(15))) {
            throw ValidationException::withMessages(['jam_keluar' => 'Jam keluar hari ini sudah lewat. Izin keluar diajukan sebelum keluar.']);
        }

        // Tidak boleh tumpang tindih dengan izin keluar lain (menunggu/disetujui) di hari yang sama.
        $selesai = $jamKembali ?? '23:59';
        $bentrok = $pegawai->pengajuanIzinKeluars()->whereDate('tanggal', $validated['tanggal'])
            ->where('status', '!=', PengajuanIzinKeluar::STATUS_REJECTED)
            ->where('jam_keluar', '<', $selesai)
            ->where(fn ($q) => $q->whereNull('jam_kembali')->orWhere('jam_kembali', '>', $validated['jam_keluar']))
            ->exists();
        if ($bentrok) {
            return back()->withInput()->with('error', 'Sudah ada izin keluar lain di jam yang bertumpuk pada tanggal itu.');
        }

        $unit = $user->unitKerja();
        if ($error = PengajuanWorkflow::cekApprover($user, $unit)) {
            return back()->withInput()->with('error', $error);
        }

        $izin = $pegawai->pengajuanIzinKeluars()->create([
            'unit_kerja' => $unit,
            'tanggal' => $validated['tanggal'],
            'jam_keluar' => $validated['jam_keluar'],
            'jam_kembali' => $jamKembali,
            'keperluan' => $validated['keperluan'],
            'alasan' => $validated['alasan'],
            'status' => PengajuanIzinKeluar::STATUS_PENDING,
            'current_level' => PengajuanWorkflow::initialLevel($user, $unit),
        ]);
        Notifikasi::pengajuanMenunggu($izin);

        return redirect()->route('pegawai.izin-keluar')
            ->with('success', 'Izin keluar dikirim. Menunggu persetujuan ' . PengajuanWorkflow::levelLabel($izin->current_level) . '. Tiket muncul setelah disetujui.');
    }

    /** Tiket keluar (hanya izin yang sudah disetujui akhir): dibuka pemilik, atau penyetuju/Admin yang berhak melihatnya. */
    public function tiket(PengajuanIzinKeluar $izin): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $milikSendiri = $user->data_pegawai_id && $user->data_pegawai_id === $izin->data_pegawai_id;
        if (! $milikSendiri) {
            $terlihat = PengajuanIzinKeluar::whereKey($izin->id);
            PengajuanWorkflow::scopeVisibleTo($terlihat, $user);
            abort_unless($terlihat->exists(), 403, 'Anda tidak berwenang melihat tiket ini.');
        }

        if (! $izin->isApproved()) {
            return redirect()->to($milikSendiri ? route('pegawai.izin-keluar') : route('admin.izin-keluar.show', $izin))
                ->with('error', 'Tiket baru tersedia setelah izin keluar disetujui sampai tahap akhir.');
        }

        $izin->load('dataPegawai.kepegawaian', 'dataPegawai.dokumen');
        // Penyetuju yang sudah menyetujui (atasan langsung / GM / Kepala SDM), dari alur yang sama dengan rincian.
        $penyetuju = collect(RingkasanPengajuan::izinKeluar($izin)['alur'])->skip(1)->where('status', 'selesai')->values();

        return view('pegawai.izin-keluar-tiket', [
            'izin' => $izin,
            'penyetuju' => $penyetuju,
            'milikSendiri' => $milikSendiri,
            'masaBerlaku' => $izin->masaBerlaku(),
        ]);
    }
}
