@extends('errors.layout')
@section('kode', '401')
@section('ikon', 'bi-person-lock')
@section('judul', 'Silakan masuk terlebih dahulu')
@section('pesan', 'Halaman ini hanya bisa dibuka setelah Anda login. Sesi Anda mungkin sudah berakhir.')
@section('aksi')
    <a href="{{ route('pelamar.login') }}" class="tombol utama"><i class="bi bi-box-arrow-in-right"></i>Masuk sebagai Pelamar</a>
    <a href="{{ url('/beranda') }}" class="tombol"><i class="bi bi-house-door"></i>Ke Beranda</a>
@endsection
@section('catatan', 'Pegawai: gunakan alamat login internal yang diberikan bagian SDM.')