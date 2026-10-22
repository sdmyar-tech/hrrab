@extends('errors.layout')
@section('kode', '500')
@section('ikon', 'bi-tools')
@section('judul', 'Terjadi kesalahan pada sistem')
@section('pesan', 'Maaf, ada gangguan di server saat memproses permintaan Anda. Data Anda aman. Silakan coba lagi beberapa saat lagi.')
@section('aksi')
    <button type="button" class="tombol utama" onclick="location.reload()"><i class="bi bi-arrow-clockwise"></i>Coba Lagi</button>
    <a href="{{ url('/beranda') }}" class="tombol"><i class="bi bi-house-door"></i>Ke Beranda</a>
@endsection
@section('catatan', 'Kesalahan ini sudah tercatat di sistem. Bila terus berulang, laporkan waktu kejadian ke admin.')