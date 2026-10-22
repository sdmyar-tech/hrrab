<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;

class DokumenController extends Controller
{
    public function index()
    {
        return view('pegawai.dokumen.index');
    }

    public function create()
    {
        return view('pegawai.dokumen.create');
    }

    public function store()
    {
        return redirect()->route('pegawai.dokumen.index')->with('success', 'Dokumen berhasil diunggah.');
    }
}
