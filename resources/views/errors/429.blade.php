@extends('errors.layout')
@section('kode', '429')
@section('ikon', 'bi-speedometer')
@section('judul', 'Terlalu banyak percobaan')
@section('pesan', 'Anda melakukan terlalu banyak permintaan dalam waktu singkat. Tunggu sekitar satu menit, lalu coba lagi.')
@section('aksi')
    <button type="button" class="tombol utama" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i>Coba Lagi</button>
    <a href="{{ url('/beranda') }}" class="tombol"><i class="bi bi-house-door"></i>Ke Beranda</a>
@endsection
@section('catatan', 'Pembatasan ini melindungi akun dari percobaan login berulang.')