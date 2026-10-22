@extends('errors.layout')
@section('kode', '404')
@section('ikon', 'bi-compass')
@section('judul', 'Halaman tidak ditemukan')
@section('pesan', 'Alamat yang Anda buka tidak ada, sudah dipindahkan, atau datanya telah dihapus. Periksa kembali tautan yang Anda gunakan.')
@section('aksi')
    <a href="{{ url('/beranda') }}" class="tombol utama"><i class="bi bi-house-door"></i>Ke Beranda</a>
    <a href="{{ url('/lowongan') }}" class="tombol"><i class="bi bi-briefcase"></i>Lihat Lowongan</a>
    <button type="button" class="tombol" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/beranda') }}')"><i class="bi bi-arrow-left"></i>Kembali</button>
@endsection