<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DashboardData;
use App\Support\EksporDashboard;
use App\Support\ExcelExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('admin.dashboard', ['user' => $user, ...DashboardData::untuk($user, $request->query('unit'))]);
    }

    /** Ekspor isi dashboard (lingkup unit yang dipilih) — satu lembar per bagian. */
    public function export(Request $request): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $d = DashboardData::untuk($user, $request->query('unit'));

        return ExcelExporter::downloadMultiSheet(ExcelExporter::namaFile('dashboard' . ($d['unitDipilih'] ? '-' . str($d['unitDipilih'])->slug() : '')), EksporDashboard::lembar($d));
    }
}
