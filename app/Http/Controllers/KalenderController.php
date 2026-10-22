<?php

namespace App\Http\Controllers;

use App\Models\HariLibur;
use App\Models\PengajuanCuti;
use App\Models\PengajuanIzinKeluar;
use App\Models\User;
use App\Support\Kalender;
use App\Support\SinkronLibur;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

/**
 * Kalender pegawai: tanggal merah (manual + hasil sinkron API), hari kerja, serta cuti & izin keluar milik pegawai yang login.
 * Semua akun internal melihat; hanya Super Admin & Kepala SDM (kelola-kalender) yang mengatur tanggal merah.
 */
class KalenderController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $bulan = rescue(fn () => Carbon::createFromFormat('Y-m', (string) $request->query('bulan'))->startOfMonth(), now()->startOfMonth(), false);
        $awalGrid = $bulan->copy()->startOfWeek(Carbon::MONDAY);
        $akhirGrid = $bulan->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $libur = HariLibur::whereBetween('tanggal', [$awalGrid->toDateString(), $akhirGrid->toDateString()])->get()
            ->keyBy(fn (HariLibur $h) => $h->tanggal->toDateString());

        // Agenda pribadi: cuti (menunggu/disetujui) & izin keluar milik pegawai yang login.
        $agenda = [];
        if ($pegawai = $user->dataPegawai) {
            PengajuanCuti::where('data_pegawai_id', $pegawai->id)->whereIn('status', [PengajuanCuti::STATUS_PENDING, PengajuanCuti::STATUS_APPROVED])
                ->whereDate('tanggal_mulai', '<=', $akhirGrid)->whereDate('tanggal_selesai', '>=', $awalGrid)->get()
                ->each(function (PengajuanCuti $c) use (&$agenda, $awalGrid, $akhirGrid) {
                    foreach (CarbonPeriod::create($c->tanggal_mulai->max($awalGrid), $c->tanggal_selesai->min($akhirGrid)) as $t) {
                        $agenda[$t->toDateString()][] = ['jenis' => 'cuti', 'teks' => $c->jenisLabel(), 'menunggu' => $c->isPending()];
                    }
                });
            PengajuanIzinKeluar::where('data_pegawai_id', $pegawai->id)->where('status', '!=', PengajuanIzinKeluar::STATUS_REJECTED)
                ->whereBetween('tanggal', [$awalGrid->toDateString(), $akhirGrid->toDateString()])->get()
                ->each(function (PengajuanIzinKeluar $i) use (&$agenda) {
                    $agenda[$i->tanggal->toDateString()][] = ['jenis' => 'izin', 'teks' => 'Izin keluar ' . substr((string) $i->jam_keluar, 0, 5), 'menunggu' => $i->isPending()];
                });
        }

        $tahun = (int) $request->query('tahun', $bulan->year);

        return view('kalender.index', [
            'bulan' => $bulan,
            'hari' => CarbonPeriod::create($awalGrid, $akhirGrid),
            'libur' => $libur,
            'agenda' => $agenda,
            'hariKerjaBulan' => Kalender::jumlahHariKerja($bulan, $bulan->copy()->endOfMonth()),
            'tahun' => $tahun,
            'daftarTahun' => HariLibur::whereYear('tanggal', $tahun)->orderBy('tanggal')->get(),
            'bisaKelola' => $user->can('kelola-kalender'),
            'sinkronTerakhir' => SinkronLibur::terakhir(),
        ]);
    }

    /** Tambah tanggal merah manual (satu tanggal atau rentang); tanggal yang sudah ada diambil alih menjadi manual. */
    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'mulai' => ['required', 'date'],
            'selesai' => ['nullable', 'date', 'after_or_equal:mulai'],
            'nama' => ['required', 'string', 'max:150'],
            'jenis' => ['required', Rule::in(array_keys(HariLibur::JENIS))],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], ['selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.']);

        $periode = CarbonPeriod::create($v['mulai'], $v['selesai'] ?? $v['mulai']);
        if ($periode->count() > 31) {
            return back()->withInput()->with('error', 'Rentang tanggal merah paling panjang 31 hari.');
        }
        DB::transaction(function () use ($periode, $v) {
            foreach ($periode as $t) {
                HariLibur::updateOrCreate(['tanggal' => $t->toDateString()], [
                    'nama' => $v['nama'], 'jenis' => $v['jenis'], 'keterangan' => $v['keterangan'] ?? null,
                    'sumber' => HariLibur::SUMBER_MANUAL, 'aktif' => true, 'dibuat_oleh' => Auth::id(),
                ]);
            }
        });

        return $this->kembali($periode->getStartDate(), "Tanggal merah \"{$v['nama']}\" ditambahkan ({$periode->count()} hari).");
    }

    /** Ubah tanggal merah; libur hasil API yang diubah menjadi manual agar tidak tertimpa sinkron berikutnya. */
    public function update(Request $request, HariLibur $hariLibur): RedirectResponse
    {
        $v = $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'jenis' => ['required', Rule::in(array_keys(HariLibur::JENIS))],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);
        $hariLibur->update($v + ['sumber' => HariLibur::SUMBER_MANUAL]);

        return $this->kembali($hariLibur->tanggal, "Tanggal merah {$hariLibur->tanggal->translatedFormat('d M Y')} diperbarui.");
    }

    /** Aktif/nonaktifkan (mis. yayasan tetap masuk saat cuti bersama). Status ini tidak diubah sinkron API. */
    public function alih(HariLibur $hariLibur): RedirectResponse
    {
        $hariLibur->update(['aktif' => ! $hariLibur->aktif]);

        return $this->kembali($hariLibur->tanggal, "{$hariLibur->nama} ({$hariLibur->tanggal->translatedFormat('d M Y')}) "
            . ($hariLibur->aktif ? 'kembali menjadi tanggal merah.' : 'dinonaktifkan — dihitung hari kerja biasa.'));
    }

    /** Hanya tanggal merah manual yang bisa dihapus; libur API cukup dinonaktifkan (kalau dihapus akan muncul lagi saat sinkron). */
    public function destroy(HariLibur $hariLibur): RedirectResponse
    {
        abort_if($hariLibur->dariApi(), 422, 'Libur hasil sinkron tidak dihapus — nonaktifkan saja.');
        $hariLibur->delete();

        return $this->kembali($hariLibur->tanggal, "Tanggal merah {$hariLibur->nama} dihapus.");
    }

    public function sinkron(): RedirectResponse
    {
        try {
            $h = SinkronLibur::jalankan();
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Sinkron dari {$h['sumber']} (" . implode(', ', $h['tahun']) . "): {$h['tambah']} baru, {$h['ubah']} diperbarui, "
            . "{$h['hapus']} dihapus" . ($h['dilewati'] ? ", {$h['dilewati']} dilewati karena diatur manual" : '') . '.');
    }

    private function kembali(CarbonInterface $tanggal, string $pesan): RedirectResponse
    {
        return redirect()->route('kalender.index', ['bulan' => $tanggal->format('Y-m'), 'tahun' => $tanggal->year])->with('success', $pesan);
    }
}
