<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\DashboardData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        return view('admin.dashboard', ['user' => $user, ...DashboardData::untuk($user, $request->query('unit'))]);
    }
}
