<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\DataPegawai;
use App\Models\JadwalKerjaPegawai;
use App\Support\Absensi\AksesAbsensi;
use App\Support\Absensi\ImportAbsensi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Pengaturan absensi: finger ID, ikut/tidak ikut absensi, jam kerja per orang (satuan & massal). */
class AbsensiPengaturanController extends Controller
{
    public function index(Request $request): View
    {
        $query = $this->filter($request);
        $tanpaFinger = AksesAbsensi::aktif(DataPegawai::whereNull('finger_id'))->with('kepegawaian:id,data_pegawai_id,unit_kerja')
            ->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'nip', 'ikut_absensi']);
        $belumDipetakan = Absensi::whereNull('data_pegawai_id')->whereNotNull('finger_id')
            ->selectRaw('finger_id, max(nama_mesin) as nama_mesin, count(*) as hari, max(tanggal) as terakhir')
            ->groupBy('finger_id')->orderByRaw('cast(finger_id as unsigned)')->get()
            ->each(fn ($b) => $b->saran = self::saranPegawai($b->nama_mesin, $tanpaFinger));

        return view('admin.absensi.pengaturan', [
            'layout' => $request->user()->panelLayout(),
            'tab' => in_array($request->query('tab'), ['pegawai', 'pemetaan'], true) ? $request->query('tab') : 'pegawai',
            'pegawais' => (clone $query)->with(['kepegawaian', 'jadwalKerja'])->orderByDesc('ikut_absensi')->orderBy('nama_lengkap')->paginate(25)->withQueryString(),
            'jumlahFilter' => (clone $query)->count(),
            'unitOpsi' => DB::table('kepegawaians')->whereNotNull('unit_kerja')->distinct()->orderBy('unit_kerja')->pluck('unit_kerja'),
            'statistik' => [
                'ikut' => AksesAbsensi::aktif(DataPegawai::where('ikut_absensi', true))->count(),
                'tanpa_finger' => AksesAbsensi::aktif(DataPegawai::where('ikut_absensi', true)->whereNull('finger_id'))->count(),
                'tanpa_jadwal' => AksesAbsensi::aktif(DataPegawai::where('ikut_absensi', true)->doesntHave('jadwalKerja'))->count(),
            ],
            'belumDipetakan' => $belumDipetakan,
            'pegawaiTanpaFinger' => $tanpaFinger,
            'bawaan' => [
                'toleransi' => (int) config('hrrab.absensi.toleransi_menit', 15),
                'hari_kerja' => config('hrrab.hari_kerja', [1, 2, 3, 4, 5]),
            ],
        ]);
    }

    /**
     * Tebak pegawai dari nama di mesin (nama di mesin sering disingkat, mis. "Devy Putri" → "Devy Putri Syahriani Lubis").
     * Hanya disarankan bila cukup yakin; SDM tetap memilih/menyetujui.
     */
    private static function saranPegawai(?string $namaMesin, \Illuminate\Support\Collection $kandidat): ?int
    {
        $bersih = fn ($s) => trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\s]/u', ' ', mb_strtolower(preg_replace('/,.*$/', '', (string) $s)))));
        $mesin = $bersih($namaMesin);
        if (mb_strlen($mesin) < 3) {
            return null;
        }

        $terbaik = null;
        $skorTerbaik = 0;
        foreach ($kandidat as $p) {
            $nama = $bersih($p->nama_lengkap);
            // Awalan cocok (nama mesin terpotong) dianggap sangat mirip.
            $skor = str_starts_with($nama, $mesin) || str_starts_with($mesin, $nama) ? 95 : (similar_text($mesin, $nama, $persen) ? $persen : 0);
            if ($skor > $skorTerbaik) {
                [$terbaik, $skorTerbaik] = [$p->id, $skor];
            }
        }

        return $skorTerbaik >= 80 ? $terbaik : null;
    }

    /** Ubah satu pegawai: finger ID, ikut absensi, dan jadwal kerjanya. */
    public function updatePegawai(Request $request, DataPegawai $pegawai): RedirectResponse
    {
        $v = $request->validate([
            'finger_id' => ['nullable', 'regex:/^\d{1,12}$/', Rule::unique('data_pegawais', 'finger_id')->ignore($pegawai->id)],
            'ikut_absensi' => ['nullable', 'boolean'],
            ...$this->aturanJadwal(false),
        ], $this->pesan());

        // Nol di depan dibuang agar sama dengan AC-No. hasil import ("0013" = "13").
        $finger = (isset($v['finger_id']) && $v['finger_id'] !== '') ? (ltrim($v['finger_id'], '0') ?: null) : null;
        $terhubung = $finger !== $pegawai->finger_id ? ImportAbsensi::petakan($pegawai, $finger) : 0;
        $pegawai->forceFill(['ikut_absensi' => $request->boolean('ikut_absensi')])->save();

        if ($request->filled('jam_masuk')) {
            $this->simpanJadwal(collect([$pegawai->id]), $v);
        } elseif ($request->boolean('hapus_jadwal')) {
            JadwalKerjaPegawai::where('data_pegawai_id', $pegawai->id)->delete();
        }

        return back()->with('success', 'Pengaturan absensi ' . $pegawai->nama_lengkap . ' disimpan.'
            . ($terhubung ? " {$terhubung} hari data mesin ikut terhubung." : ''));
    }

    /** Saklar cepat ikut / tidak ikut absensi untuk satu pegawai. */
    public function ikut(Request $request, DataPegawai $pegawai): RedirectResponse
    {
        $ikut = $request->boolean('ikut');
        $pegawai->forceFill(['ikut_absensi' => $ikut])->save();

        return back()->with('success', $pegawai->nama_lengkap . ($ikut ? ' sekarang ikut absensi.' : ' tidak lagi ikut absensi.'));
    }

    /** Aksi massal untuk pegawai terpilih atau semua hasil filter. */
    public function massal(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'aksi' => ['required', Rule::in(['ikut', 'tidak_ikut', 'jadwal'])],
            'sasaran' => ['required', Rule::in(['terpilih', 'filter'])],
            'pegawai_ids' => ['required_if:sasaran,terpilih', 'array'],
            'pegawai_ids.*' => ['integer', 'exists:data_pegawais,id'],
            ...$this->aturanJadwal($request->input('aksi') === 'jadwal'),
        ], $this->pesan() + ['pegawai_ids.required_if' => 'Centang minimal satu pegawai.']);

        $ids = $v['sasaran'] === 'terpilih'
            ? collect($v['pegawai_ids'])
            : $this->filter($request)->pluck('data_pegawais.id');

        if ($ids->isEmpty()) {
            return back()->with('error', 'Tidak ada pegawai yang cocok.');
        }

        match ($v['aksi']) {
            'ikut' => DataPegawai::whereIn('id', $ids)->update(['ikut_absensi' => true]),
            'tidak_ikut' => DataPegawai::whereIn('id', $ids)->update(['ikut_absensi' => false]),
            'jadwal' => $this->simpanJadwal($ids, $v),
        };

        $teks = ['ikut' => 'diikutkan absensi', 'tidak_ikut' => 'dikeluarkan dari absensi', 'jadwal' => 'diatur jam kerjanya'][$v['aksi']];

        return back()->with('success', $ids->count() . " pegawai {$teks}.");
    }

    /** Hubungkan ID mesin yang belum dikenal ke pegawai. */
    public function petakan(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'finger_id' => ['required', 'regex:/^\d{1,12}$/', Rule::unique('data_pegawais', 'finger_id')],
            'data_pegawai_id' => ['required', 'exists:data_pegawais,id'],
            'ikut_absensi' => ['nullable', 'boolean'],
        ], ['finger_id.unique' => 'ID mesin ini sudah dipakai pegawai lain.', 'data_pegawai_id.required' => 'Pilih pegawai.']);

        $pegawai = DataPegawai::findOrFail($v['data_pegawai_id']);
        if ($pegawai->finger_id) {
            return back()->with('error', "{$pegawai->nama_lengkap} sudah punya ID mesin {$pegawai->finger_id}. Ubah lewat tombol atur pada daftar pegawai.");
        }

        $jumlah = ImportAbsensi::petakan($pegawai, $v['finger_id']);
        if ($request->boolean('ikut_absensi')) {
            $pegawai->forceFill(['ikut_absensi' => true])->save();
        }

        return back()->with('success', "ID {$v['finger_id']} dihubungkan ke {$pegawai->nama_lengkap} ({$jumlah} hari data absensi).");
    }

    /**
     * Centang "ikut absensi" dari daftar tempel (satu pegawai per baris, NIP dan/atau nama).
     * Cocok lewat NIP dulu, lalu nama (gelar diabaikan). Baris yang ragu/tidak ketemu dilaporkan.
     */
    public function aktifkanDaftar(Request $request): RedirectResponse
    {
        $v = $request->validate(['daftar' => ['required', 'string', 'max:20000']], ['daftar.required' => 'Tempel daftar NIP/nama pegawai.']);

        $semua = DataPegawai::get(['id', 'nip', 'nama_lengkap']);
        $angka = fn ($s) => preg_replace('/\D/', '', (string) $s);
        $nama = fn ($s) => trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\s]/u', ' ', mb_strtolower(
            preg_replace('/(^|\s)(drs?|dr|apt|hj?|ir)\.\s*|,.*$/i', ' ', (string) $s)
        ))));

        $cocok = collect();
        $gagal = [];
        foreach (preg_split('/\R/', $v['daftar']) as $baris) {
            $baris = trim($baris);
            if ($baris === '' || str_starts_with($baris, '#')) { // baris kosong & komentar diabaikan
                continue;
            }
            $nip = preg_match('/\d[\d.\s]{5,}\d/', $baris, $m) ? $angka($m[0]) : null;
            $teksNama = trim(preg_replace('/\d[\d.\s]{5,}\d/', '', $baris), " \t;|,-");

            $p = $nip ? $semua->first(fn ($x) => $x->nip && $angka($x->nip) === $nip) : null;
            if (! $p && $teksNama !== '') {
                $kandidat = $semua->filter(fn ($x) => $nama($x->nama_lengkap) === $nama($teksNama));
                $p = $kandidat->count() === 1 ? $kandidat->first() : null;
                if ($kandidat->count() > 1) {
                    $gagal[] = "{$baris} (nama ganda)";
                    continue;
                }
            }
            if ($p) {
                $cocok->push($p->id);
            } else {
                $gagal[] = $baris;
            }
        }

        $jumlah = DataPegawai::whereIn('id', $cocok->unique())->update(['ikut_absensi' => true]);

        return back()
            ->with('success', "{$jumlah} pegawai dicentang ikut absensi.")
            ->with('daftar_gagal', $gagal);
    }

    private function filter(Request $request): Builder
    {
        return DataPegawai::query()
            ->when($request->input('ikut') === 'ya', fn ($q) => $q->where('ikut_absensi', true))
            ->when($request->input('ikut') === 'tidak', fn ($q) => $q->where('ikut_absensi', false))
            ->when($request->input('masalah') === 'tanpa_finger', fn ($q) => $q->where('ikut_absensi', true)->whereNull('finger_id'))
            ->when($request->input('masalah') === 'tanpa_jadwal', fn ($q) => $q->where('ikut_absensi', true)->doesntHave('jadwalKerja'))
            ->when($request->filled('unit'), fn ($q) => $q->whereHas('kepegawaian', fn ($k) => $k->where('unit_kerja', $request->input('unit'))))
            ->when($request->filled('q'), function ($q) use ($request) {
                $kata = '%' . $request->input('q') . '%';
                $q->where(fn ($w) => $w->where('nama_lengkap', 'like', $kata)->orWhere('nip', 'like', $kata)->orWhere('finger_id', $request->input('q')));
            })
            // Default: pegawai aktif saja (pegawai keluar tidak perlu diatur absensinya).
            ->when($request->input('status') !== 'semua', fn ($q) => AksesAbsensi::aktif($q));
    }

    private function aturanJadwal(bool $wajib): array
    {
        $perlu = $wajib ? 'required' : 'nullable';

        return [
            'jam_masuk' => [$perlu, 'date_format:H:i'],
            'jam_pulang' => [$wajib ? 'required' : 'required_with:jam_masuk', 'nullable', 'date_format:H:i', 'after:jam_masuk'],
            'toleransi_menit' => ['nullable', 'integer', 'min:0', 'max:240'],
            'hari_kerja' => ['nullable', 'array'],
            'hari_kerja.*' => ['integer', 'between:1,7'],
            'khusus' => ['nullable', 'array'],
            'khusus.*.masuk' => ['nullable', 'date_format:H:i'],
            'khusus.*.pulang' => ['nullable', 'date_format:H:i'],
        ];
    }

    private function pesan(): array
    {
        return [
            'jam_masuk.required' => 'Isi jam masuk.',
            'jam_pulang.required' => 'Isi jam pulang.',
            'jam_pulang.required_with' => 'Isi jam pulang.',
            'jam_pulang.after' => 'Jam pulang harus setelah jam masuk.',
            'finger_id.regex' => 'ID mesin hanya angka (AC-No. di mesin).',
            'finger_id.unique' => 'ID mesin ini sudah dipakai pegawai lain.',
        ];
    }

    /** @param \Illuminate\Support\Collection<int, int> $ids */
    private function simpanJadwal(\Illuminate\Support\Collection $ids, array $v): void
    {
        $hari = array_values(array_unique(array_map('intval', $v['hari_kerja'] ?? config('hrrab.hari_kerja', [1, 2, 3, 4, 5]))));
        sort($hari);
        // Jam khusus per hari hanya disimpan bila diisi & berbeda dari jam biasa.
        $khusus = collect($v['khusus'] ?? [])
            ->filter(fn ($j, $h) => in_array((int) $h, $hari, true) && (($j['masuk'] ?? null) || ($j['pulang'] ?? null)))
            ->map(fn ($j) => array_filter(['masuk' => $j['masuk'] ?? null, 'pulang' => $j['pulang'] ?? null]))
            ->filter(fn ($j) => ($j['masuk'] ?? $v['jam_masuk']) !== $v['jam_masuk'] || ($j['pulang'] ?? $v['jam_pulang']) !== $v['jam_pulang'])
            ->all();

        foreach ($ids as $id) {
            JadwalKerjaPegawai::updateOrCreate(['data_pegawai_id' => $id], [
                'jam_masuk' => $v['jam_masuk'],
                'jam_pulang' => $v['jam_pulang'],
                'toleransi_menit' => $v['toleransi_menit'] ?? null,
                'hari_kerja' => $hari === config('hrrab.hari_kerja', [1, 2, 3, 4, 5]) ? null : $hari,
                'jam_per_hari' => $khusus ?: null,
            ]);
        }
    }
}
