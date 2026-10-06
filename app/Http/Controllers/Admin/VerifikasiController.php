<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class VerifikasiController extends Controller
{
    public function index()
    {
        return view('admin.verifikasi.index');
    }

    public function store($id = null)
    {
        return redirect()->back()->with('success', 'Data verifikasi berhasil diproses.');
    }
}
