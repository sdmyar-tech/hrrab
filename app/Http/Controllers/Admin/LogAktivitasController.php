<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogAktivitas;
use App\Models\User;
use App\Support\ExcelExporter;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogAktivitasController extends Controller
{
    public function index(Request $request): View
    {
        $hariIni = LogAktivitas::whereDate('created_at', now()->toDateString());

        return view('admin.log.index', [
            'logs' => $this->filteredQuery($request)->latest('created_at')->latest('id')->paginate(30)->withQueryString(),
            'modulOpsi' => LogAktivitas::query()->distinct()->orderBy('modul')->pluck('modul'),
            'ringkas' => [
                'hari_ini' => (clone $hariIni)->count(),
                'pengguna_hari_ini' => (clone $hariIni)->distinct('user_id')->count('user_id'),
                'gagal_hari_ini' => (clone $hariIni)->where('status', 'gagal')->count(),
                'login_hari_ini' => (clone $hariIni)->where('aksi', 'login')->where('status', 'berhasil')->count(),
            ],
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $rows = $this->filteredQuery($request)->orderBy('id')->limit(20000)->get()->map(fn (LogAktivitas $l) => [
            $l->created_at->format('d-m-Y H:i:s'),
            $l->nama_pengguna,
            User::roleLabelFor($l->role),
            $l->aksiInfo()[0],
            $l->modul,
            $l->deskripsi,
            $l->status === 'gagal' ? 'Gagal' : 'Berhasil',
            collect($l->perubahan ?? [])->map(fn ($p) => "{$p['model']}: " . collect($p['kolom'] ?? [])
                ->map(fn ($v, $k) => "{$k} " . json_encode($v[0], JSON_UNESCAPED_UNICODE) . ' → ' . json_encode($v[1], JSON_UNESCAPED_UNICODE))->implode('; '))->implode(' | '),
            $l->ip,
        ]);

        return ExcelExporter::download(ExcelExporter::namaFile('log-aktivitas'), [
            'Waktu', 'Pengguna', 'Role', 'Aksi', 'Modul', 'Deskripsi', 'Status', 'Perubahan', 'IP',
        ], $rows, 'Log Aktivitas');
    }

    private function filteredQuery(Request $request)
    {
        return LogAktivitas::query()
            ->when($request->query('q'), fn ($q, $cari) => $q->where(fn ($w) => $w->where('nama_pengguna', 'like', "%{$cari}%")
                ->orWhere('deskripsi', 'like', "%{$cari}%")))
            ->when($request->query('modul'), fn ($q, $modul) => $q->where('modul', $modul))
            ->when($request->query('aksi'), fn ($q, $aksi) => $q->where('aksi', $aksi))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->query('dari'), fn ($q, $dari) => $q->whereDate('created_at', '>=', $dari))
            ->when($request->query('sampai'), fn ($q, $sampai) => $q->whereDate('created_at', '<=', $sampai));
    }
}
